<?php

namespace App\Services;

use App\Models\Package;
use Illuminate\Pagination\LengthAwarePaginator;

class PackageReadService
{
    public function paginateActive(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        $query = Package::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if (! empty($filters['branch_id'])) {
            $branchId = (int) $filters['branch_id'];
            $query->where(function ($builder) use ($branchId): void {
                $builder->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
