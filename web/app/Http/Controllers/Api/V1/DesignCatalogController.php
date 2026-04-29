<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DesignCatalogResource;
use App\Models\DesignCatalog;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignCatalogController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $query = DesignCatalog::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('package_id')) {
            $query->where('package_id', $request->integer('package_id'));
        }

        $designs = $query->paginate($perPage)->withQueryString();

        return $this->responder->paginated($designs, DesignCatalogResource::collection($designs), 'Daftar desain berhasil dimuat.');
    }

    public function show(DesignCatalog $designCatalog): JsonResponse
    {
        if (! $designCatalog->is_active) {
            return $this->responder->error('Desain tidak tersedia.', 404);
        }

        return $this->responder->success(new DesignCatalogResource($designCatalog));
    }
}
