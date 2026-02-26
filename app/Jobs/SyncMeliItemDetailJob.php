<?php

namespace App\Jobs;

use App\Models\Item;
use App\Services\Meli\Exceptions\MeliApiException;
use App\Services\Meli\MeliClient;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncMeliItemDetailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $accessToken,
        public readonly int $sellerId,
        public readonly string $itemId
    ) {
    }

    public function handle(MeliClient $client): void
    {
        /** @var Item|null $item */
        $item = Item::query()
            ->where('item_id', $this->itemId)
            ->first();

        if ($item === null) {
            return;
        }

        $item->forceFill([
            'sync_status' => 'processing',
            'last_error' => null,
        ])->save();

        try {
            $raw = $client->getItemDetailRaw($this->accessToken, $this->itemId);

            $item->forceFill([
                'title' => (string) ($raw['title'] ?? $item->title),
                'status' => (string) ($raw['status'] ?? $item->status),
                'meli_created_at' => isset($raw['created'])
                    ? CarbonImmutable::parse((string) $raw['created'])
                    : $item->meli_created_at,
                'meli_updated_at' => isset($raw['updated'])
                    ? CarbonImmutable::parse((string) $raw['updated'])
                    : $item->meli_updated_at,
                'raw' => $raw,
                'fetched_at' => now(),
                'sync_status' => 'synced',
                'synced_at' => now(),
                'last_error' => null,
            ])->save();


            Cache::tags(['items'])->flush();
        } catch (MeliApiException $e) {
            Log::warning('Meli item detail sync failed', [
                'seller_id' => $this->sellerId,
                'item_id' => $this->itemId,
                'status' => $e->statusCode,
                'context' => $e->context,
            ]);

            $item->forceFill([
                'sync_status' => 'failed',
                'last_error' => $e->getMessage(),
                'fetched_at' => now(),
            ])->save();

            Cache::tags(['items'])->flush();
        }
    }
}