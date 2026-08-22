<?php

namespace App\Http\Controllers\Admin\ServiceCategories;

use App\Actions\ServiceCategory\CreateServiceCategory;
use App\Actions\ServiceCategory\UpdateServiceCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceCategories\StoreServiceCategoryRequest;
use App\Http\Requests\Admin\ServiceCategories\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ServiceCategory::class);

        /** @var User $actor */
        $actor = $request->user();
        $status = $request->input('status', 'active');
        $search = $request->input('search');

        $query = ServiceCategory::withTrashed();

        if ($status === 'active') {
            $query->whereNull('deleted_at');
        } elseif ($status === 'archived') {
            $query->whereNotNull('deleted_at');
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%'.$search.'%')
                    ->orWhere('code', 'ilike', '%'.$search.'%')
                    ->orWhere('description', 'ilike', '%'.$search.'%');
            });
        }

        $categories = $query
            ->withCount(['serviceTypes' => fn ($q) => $q->withTrashed()])
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $hasFilters = $request->filled('search') || $status !== 'active';

        return view('admin.service-categories.index', compact('categories', 'status', 'search', 'hasFilters'));
    }

    public function create(): View
    {
        $this->authorize('create', ServiceCategory::class);

        return view('admin.service-categories.create');
    }

    public function show(ServiceCategory $serviceCategory): View
    {
        $this->authorize('view', $serviceCategory);

        $serviceCategory->load(['serviceTypes' => fn ($q) => $q->withTrashed()]);

        return view('admin.service-categories.show', compact('serviceCategory'));
    }

    public function store(
        StoreServiceCategoryRequest $request,
        CreateServiceCategory $createServiceCategory,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $category = $createServiceCategory->handle($request->validated(), $actor, $request);

        return redirect()
            ->route('admin.service-categories.index')
            ->with('success', 'Đã tạo danh mục dịch vụ thành công.');
    }

    public function edit(ServiceCategory $serviceCategory): View
    {
        $this->authorize('update', $serviceCategory);

        return view('admin.service-categories.edit', compact('serviceCategory'));
    }

    public function update(
        UpdateServiceCategoryRequest $request,
        ServiceCategory $serviceCategory,
        UpdateServiceCategory $updateServiceCategory,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $updatedCategory = $updateServiceCategory->handle(
            $serviceCategory,
            $request->validated(),
            $actor,
            $request,
        );

        return redirect()
            ->route('admin.service-categories.index')
            ->with('success', 'Đã cập nhật thông tin danh mục dịch vụ.');
    }

    public function destroy(Request $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $this->authorize('delete', $serviceCategory);

        $serviceCategory->delete();

        return redirect()
            ->back(fallback: route('admin.service-categories.index'))
            ->with('success', 'Đã lưu trữ danh mục dịch vụ thành công.');
    }

    public function restore(Request $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $this->authorize('restore', $serviceCategory);

        $serviceCategory->restore();

        return redirect()
            ->back(fallback: route('admin.service-categories.index'))
            ->with('success', 'Đã hoàn tác và khôi phục danh mục dịch vụ thành công.');
    }
}
