<?php

namespace App\Services\Meli;

use App\Services\Meli\Dto\ItemSummary;
use App\Services\Meli\Dto\SellerSearchResult;
use App\Services\Meli\Exceptions\MeliApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class MeliClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl(rtrim((string) config('meli.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withUserAgent((string) config('meli.user_agent'))
            ->timeout((int) config('meli.timeout'))
            ->connectTimeout((int) config('meli.connect_timeout'))
            ->retry((int) config('meli.retries'), (int) config('meli.retry_sleep_ms'), throw: false);
    }

    public function getSellerToken(int $sellerId): SellerSearchResult
    {
        $res = $this->request('GET', "traymeli/sellers/{$sellerId}");

        if (! $res->successful()) {
            throw $this->exceptionFromResponse($res, "traymeli/sellers/{$sellerId}");
        }

        return SellerSearchResult::fromArray((array) $res->json());
    }

    public function getActiveSellerToken(int $sellerId): SellerSearchResult
    {
        $attempts = max(1, ((int) config('meli.retries')) + 1);

        for ($i = 1; $i <= $attempts; $i++) {
            $res = $this->request('GET', "traymeli/sellers/{$sellerId}");

            if ($res->status() === 429) {
                usleep(((int) config('meli.retry_sleep_ms')) * 1000);
                continue;
            }

            if (! $res->successful()) {
                throw $this->exceptionFromResponse($res, "traymeli/sellers/{$sellerId}");
            }

            $dto = SellerSearchResult::fromArray((array) $res->json());

            if (! $dto->inactiveToken && $dto->accessToken !== '') {
                return $dto;
            }

            // token veio inativo -> espera e tenta de novo (mock simula isso)
            usleep(((int) config('meli.retry_sleep_ms')) * 1000);
        }

        throw new MeliApiException(
            'Unable to obtain active access token.',
            0,
            ['seller_id' => $sellerId]
        );
    }

    public function searchItems(string $accessToken, int $sellerId, int $offset = 0, int $limit = 30): array
    {
        $site = (string) config('meli.site');

        $res = $this->request(
            'GET',
            "mercadolibre/sites/{$site}/search",
            [
                'seller_id' => $sellerId,
                'offset' => $offset,
                'limit' => $limit,
            ],
            $accessToken
        );

        if (! $res->successful()) {
            throw $this->exceptionFromResponse($res, "mercadolibre/sites/{$site}/search");
        }

        $json = (array) $res->json();

        $results = array_map(
            fn(string $id) => ItemSummary::fromId($id),
            (array) ($json['results'] ?? [])
        );

        return [
            'site_id' => (string) ($json['site_id'] ?? ''),
            'seller_id' => (int) ($json['seller_id'] ?? 0),
            'paging' => (array) ($json['paging'] ?? []),
            'results' => $results,
        ];
    }

    public function getItemDetailRaw(string $accessToken, string $itemId): array
    {
        $res = $this->request('GET', "mercadolibre/items/{$itemId}", [], $accessToken);

        if (! $res->successful()) {
            throw $this->exceptionFromResponse($res, "mercadolibre/items/{$itemId}");
        }

        return (array) $res->json();
    }

    private function request(string $method, string $uri, array $query = [], ?string $accessToken = null): Response
    {
        $this->throttle();

        $req = $this->http;

        if ($accessToken !== null && $accessToken !== '') {
            $req = $req->withToken($accessToken);
        }

        $method = strtoupper($method);

        return match ($method) {
            'GET' => $req->get($uri, $query),
            default => $req->{$method}($uri, $query),
        };
    }

    private function throttle(): void
    {
        $key = (string) config('meli.throttle_key');
        $allow = max(1, (int) config('meli.rate_limit_per_second'));
        $blockSeconds = max(1, (int) config('meli.throttle_block_seconds'));

        // Redis::throttle usa script atômico e suporta block()
        Redis::throttle($key)
            ->allow($allow)
            ->every(1)
            ->block($blockSeconds);
    }

    private function exceptionFromResponse(Response $res, string $endpoint): MeliApiException
    {
        return new MeliApiException(
            "Meli API request failed ({$res->status()}).",
            $res->status(),
            [
                'endpoint' => $endpoint,
                'body' => $res->json(),
            ]
        );
    }
}
