<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_items_paginated(): void
    {
        // garante FK
        Seller::query()->create([
            'seller_id' => 252254392,
            'store_id' => 5882832,
            'user_id' => 2522543922,
            'last_token' => 'test-token',
            'token_inactive' => false,
        ]);

        Item::factory()->count(3)->create([
            'seller_id' => 252254392,
            'sync_status' => 'synced',
        ]);

        $res = $this->getJson('/api/items?per_page=2');

        $res->assertOk()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
            ])
            ->assertJsonCount(2, 'data');
    }
}