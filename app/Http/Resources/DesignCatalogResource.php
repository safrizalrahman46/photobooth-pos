<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignCatalogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'package_id' => $this->package_id,
            'code' => $this->code,
            'name' => $this->name,
            'theme' => $this->theme,
            'preview_url' => $this->preview_url,
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
