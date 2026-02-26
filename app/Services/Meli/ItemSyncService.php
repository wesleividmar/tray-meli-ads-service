<?php

namespace App\Services\Meli;

use App\Jobs\SyncMeliItemDetailJob;
use App\Models\Item;
use App\Models\Seller;
use App\Services\Meli\Exceptions\MeliApiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ItemSyncService
{
    public function __construct(private readonly MeliClient $client)
    {
    }

    public function syncSellerItems(int $sellerId): array
    {
        $sellerDto = null;

        try {
            $sellerDto = $this->client->getActiveSellerToken($sellerId);
        } catch (MeliApiException $e) {
            Log::warning('Failed to obtain active access token.', [
                'seller_id' => $sellerId,
                'status' => $e->getCode(),
                'context' => $e->context ?? null,
            ]);

            return [
                'seller_id' => $sellerId,
                'count' => 0,
                'queued' => 0,
                'error' => 'Unable to obtain active access token.',
            ];
        }

        Seller::updateOrCreate(
            ['seller_id' => $sellerId],
            [
                'store_id' => $sellerDto->storeId,
                'user_id' => $sellerDto->userId,
                'last_token' => $sellerDto->accessToken,
                'token_inactive' => $sellerDto->inactiveToken,
            ]
        );

        $ids = $this->fetchAllItemIds($sellerDto->accessToken, $sellerId);

        $queued = 0;

        foreach ($ids as $id) {
            Item::updateOrCreate(
                ['item_id' => $id],
                [
                    'seller_id' => $sellerId,
                    'sync_status' => 'queued',
                    'last_error' => null,
                ]
            );

            SyncMeliItemDetailJob::dispatch($sellerDto->accessToken, $sellerId, $id)
                ->onConnection((string) config('queue.default'))
                ->onQueue((string) config('queue.connections.rabbitmq.queue', env('RABBITMQ_QUEUE', 'meli-items')));

            $queued++;
        }

        return [
            'seller_id' => $sellerId,
            'count' => $ids->count(),
            'queued' => $queued,
        ];
    }

    private function fetchAllItemIds(string $accessToken, int $sellerId): Collection
    {
        $limit = (int) config('meli.search_limit', 5);
        $offset = 0;

        $all = collect();
        $total = null;

        while ($offset <= 25) {
            try {
                $page = $this->client->searchItems($accessToken, $sellerId, $offset, $limit);
            } catch (MeliApiException $e) {
                Log::warning('Search items request failed.', [
                    'seller_id' => $sellerId,
                    'offset' => $offset,
                    'limit' => $limit,
                    'status' => $e->getCode(),
                    'context' => $e->context ?? null,
                ]);
                break;
            }

            if ($total === null) {
                $total = (int) ($page['paging']['total'] ?? 0);
            }

            $results = $page['results'] ?? [];

            if (empty($results)) {
                break;
            }

            foreach ($results as $dto) {
                $all->push($dto->id);
            }

            if ($total > 0 && $all->count() >= $total) {
                break;
            }

            $offset += $limit;
        }

        return $all->values()->unique();
    }
}