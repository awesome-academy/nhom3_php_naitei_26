<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceTypeResource;
use App\Http\Responses\ApiResponse;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceCatalogController extends Controller
{
    /**
     * Display a listing of the active public services.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ServiceType::query()
            ->with('category')
            ->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%'.$search.'%')
                    ->orWhere('description', 'ilike', '%'.$search.'%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $services = $query->paginate($request->input('per_page', 15));
        $payload = ServiceTypeResource::collection($services)->response()->getData();

        return ApiResponse::success(
            'Services retrieved successfully',
            [
                'data' => $payload->data,
                'links' => $payload->links,
                'meta' => $payload->meta,
            ],
        );
    }

    /**
     * Display the specified public service details.
     */
    public function show(ServiceType $service): JsonResponse
    {
        $service->load('category');

        return ApiResponse::success(
            'Service retrieved successfully',
            new ServiceTypeResource($service),
        );
    }

    /**
     * Display a listing of categories for the catalog sidebar.
     */
    public function categories(): JsonResponse
    {
        $categories = ServiceCategory::select('id', 'name', 'code', 'description')->get();

        return ApiResponse::success(
            'Service categories retrieved successfully',
            $categories,
        );
    }
}
