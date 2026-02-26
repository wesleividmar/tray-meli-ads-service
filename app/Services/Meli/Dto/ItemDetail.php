<?php

namespace App\Services\Meli\Dto;

use Carbon\CarbonImmutable;

class ItemDetail
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $status,
        public readonly CarbonImmutable $createdAt,
        public readonly CarbonImmutable $updatedAt
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['id'] ?? ''),
            (string) ($data['title'] ?? ''),
            (string) ($data['status'] ?? ''),
            CarbonImmutable::parse((string) ($data['created'] ?? 'now')),
            CarbonImmutable::parse((string) ($data['updated'] ?? 'now')),
        );
    }
}