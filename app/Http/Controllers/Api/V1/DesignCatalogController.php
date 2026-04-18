<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DesignCatalogIndexRequest;
use App\Http\Resources\DesignCatalogResource;
use App\Models\DesignCatalog;
use App\Services\DesignCatalogReadService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;

class DesignCatalogController extends Controller
{
    public function __construct(
        private readonly DesignCatalogReadService $designCatalogReadService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(DesignCatalogIndexRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $perPage = (int) ($payload['per_page'] ?? 15);

        $designs = $this->designCatalogReadService->paginateActive($payload, $perPage);

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
