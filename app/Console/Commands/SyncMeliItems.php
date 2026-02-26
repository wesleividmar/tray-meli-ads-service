<?php

namespace App\Console\Commands;

use App\Services\Meli\ItemSyncService;
use App\Services\Meli\Exceptions\MeliApiException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncMeliItems extends Command
{
    protected $signature = 'meli:sync-items {--seller_id=}';

    protected $description = 'Sync Mercado Livre items for a seller using TrayMeli Auth token and queue item detail jobs.';

    public function handle(ItemSyncService $service): int
    {
        $sellerId = (int) ($this->option('seller_id') ?: (int) env('MELI_SELLER_ID'));

        try {
            $result = $service->syncSellerItems($sellerId);

            Cache::tags(['items'])->flush();

            $this->info(json_encode($result, JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (MeliApiException $e) {
            Log::warning('Meli sync failed', [
                'seller_id' => $sellerId,
                'status' => $e->statusCode,
                'context' => $e->context,
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}