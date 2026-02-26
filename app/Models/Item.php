<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'seller_id',
        'title',
        'price',
        'currency_id',
        'permalink',
        'status',
        'meli_created_at',
        'meli_updated_at',
        'fetched_at',
        'sync_status',
        'synced_at',
        'last_error',
        'raw',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'meli_created_at' => 'datetime',
        'meli_updated_at' => 'datetime',
        'fetched_at' => 'datetime',
        'synced_at' => 'datetime',
        'raw' => 'array',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id', 'seller_id');
    }
}