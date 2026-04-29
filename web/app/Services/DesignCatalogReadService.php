<?php

namespace App\Services;

use App\Models\DesignCatalog;
use Illuminate\Pagination\LengthAwarePaginator;

class DesignCatalogReadService
{
    public function paginateActive(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        $query = DesignCatalog::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if (! empty($filters['package_id'])) {
            $query->where('package_id', (int) $filters['package_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
