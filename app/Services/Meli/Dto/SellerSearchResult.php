<?php

namespace App\Services\Meli\Dto;

class SellerSearchResult
{
    public function __construct(
        public readonly int $storeId,
        public readonly int $userId,
        public readonly string $accessToken,
        public readonly bool $inactiveToken
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['store_id'] ?? 0),
            (int) ($data['user_id'] ?? 0),
            (string) ($data['access_token'] ?? ''),
            (bool) ((int) ($data['inactive_token'] ?? 0))
        );
    }
}