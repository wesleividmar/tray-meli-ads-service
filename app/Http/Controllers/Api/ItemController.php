<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ItemController extends Controller
{
    public function __invoke(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $queryParams = $request->query();
        $queryParams['per_page'] = $perPage;

        ksort($queryParams);

        $cacheKey = 'items:' . md5(json_encode($queryParams, JSON_UNESCAPED_SLASHES));
        $ttl = (int) (config('meli.items_cache_ttl') ?? 30);

        return Cache::tags(['items'])->remember($cacheKey, $ttl, function () use ($request, $perPage) {
            $query = Item::query();

            if ($request->filled('seller_id')) {
                $query->where('seller_id', (int) $request->input('seller_id'));
            }

            if ($request->filled('status')) {
                $query->where('status', (string) $request->input('status'));
            }

            if ($request->filled('sync_status')) {
                $query->where('sync_status', (string) $request->input('sync_status'));
            }

            if ($request->filled('q')) {
                $q = (string) $request->input('q');
                $query->where('title', 'like', "%{$q}%");
            }

            $sort = (string) $request->input('sort', '-fetched_at');

            $allowed = [
                'item_id',
                'title',
                'status',
                'seller_id',
                'fetched_at',
                'synced_at',
                'meli_created_at',
                'meli_updated_at',
                'updated_at',
                'created_at',
            ];

            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');

            if (! in_array($field, $allowed, true)) {
                $field = 'fetched_at';
                $direction = 'desc';
            }

            $paginator = $query
                ->orderBy($field, $direction)
                ->paginate($perPage)
                ->appends($request->query());

            return ItemResource::collection($paginator);
        });
    }
}