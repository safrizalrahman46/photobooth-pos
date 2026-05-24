<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWalkInRequest;
use App\Models\AddOn;
use App\Models\Branch;
use App\Models\Package;
use App\Models\WalkInRequest;
use App\Services\WalkInRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class WalkInRequestController extends Controller
{
    public function __construct(
        private readonly WalkInRequestService $service,
    ) {}

    public function create(Request $request): View
    {
        $branches = collect();
        $packages = collect();
        $addOns = collect();

        try {
            $branches = Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'timezone', 'address']);

            $packages = Package::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'duration_minutes', 'base_price', 'branch_id', 'sample_photos'])
                ->map(function (Package $package): array {
                    return [
                        'id' => (int) $package->id,
                        'name' => (string) $package->name,
                        'description' => (string) ($package->description ?? ''),
                        'duration_minutes' => (int) $package->duration_minutes,
                        'base_price' => (float) $package->base_price,
                        'branch_id' => $package->branch_id ? (int) $package->branch_id : null,
                        'sample_photos' => $package->resolvedSamplePhotos(),
                    ];
                });

            $addOns = AddOn::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'package_id', 'code', 'name', 'price', 'max_qty']);
        } catch (Throwable) {
        }

        return view('web.walk-in-create', [
            'branches' => $branches,
            'packages' => $packages,
            'addOns' => $addOns,
            'prefillBranch' => $request->integer('branch') ?: null,
            'prefillPackage' => $request->integer('package') ?: null,
            'submissionKey' => (string) Str::uuid(),
        ]);
    }

    public function store(StoreWalkInRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['request_ip'] = $request->ip();
        $payload['user_agent'] = $request->userAgent();

        try {
            $walkInRequest = $this->service->create($payload);
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput();
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['walk_in' => $exception->getMessage() ?: 'Self walk-in belum berhasil dibuat.'])
                ->withInput();
        }

        return redirect()->route('walk-in.success', ['walkInRequest' => $walkInRequest->request_code]);
    }

    public function success(WalkInRequest $walkInRequest): View
    {
        return view('web.walk-in-success', [
            'walkInRequest' => $walkInRequest->load(['branch']),
        ]);
    }
}
