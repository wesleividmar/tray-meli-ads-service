<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $includeRaw = (bool) filter_var($request->query('include_raw', false), FILTER_VALIDATE_BOOL);

        $data = [
            'item_id' => $this->item_id,
            'seller_id' => $this->seller_id,
            'title' => $this->title,
            'status' => $this->status,
            'price' => $this->price,
            'currency_id' => $this->currency_id,
            'permalink' => $this->permalink,
            'meli_created_at' => optional($this->meli_created_at)->toIso8601String(),
            'meli_updated_at' => optional($this->meli_updated_at)->toIso8601String(),
            'fetched_at' => optional($this->fetched_at)->toIso8601String(),
            'sync_status' => $this->sync_status,
            'synced_at' => optional($this->synced_at)->toIso8601String(),
            'last_error' => $this->last_error,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];

        if ($includeRaw) {
            $data['raw'] = $this->raw;
        }

        return $data;
    }
}