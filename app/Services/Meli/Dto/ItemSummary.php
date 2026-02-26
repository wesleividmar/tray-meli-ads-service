<?php

namespace App\Services\Meli\Dto;

class ItemSummary
{
    public function __construct(
        public readonly string $id
    ) {
    }

    public static function fromId(string $id): self
    {
        return new self($id);
    }
}