<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seller extends Model
{
    protected $fillable = [
        'seller_id',
        'name',
        'raw',
    ];

    protected $casts = [
        'raw' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'seller_id', 'seller_id');
    }
}