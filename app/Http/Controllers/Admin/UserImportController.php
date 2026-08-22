<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CsvImportRequest;
use App\Models\Department;
use App\Services\CsvImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class UserImportController extends Controller
{
    public function __construct(
        private readonly CsvImportService $importService
    ) {}

    /**
     * Display the user import page with instructions and reports.
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Chỉ Super Admin có quyền truy cập chức năng Nhập CSV.');

        $departments = Department::query()->orderBy('name')->get();

        // Retrieve report from cache if a key was flashed (avoid cookie size limit)
        $report = null;
        if ($key = session('import_report_key')) {
            $report = Cache::pull($key);
        }

        return view('admin.users.import', compact('departments', 'report'));
    }

    /**
     * Handle Citizen CSV import request.
     */
    public function importCitizens(CsvImportRequest $request): RedirectResponse|JsonResponse
    {
        $file = $request->file('csv_file');
        $rollbackOnError = $request->boolean('rollback_on_error');
        $report = $this->importService->importCitizens($file->getRealPath(), $rollbackOnError);

        if ($request->wantsJson()) {
            return response()->json($report);
        }

        // Store large report in cache (5 min TTL), only flash the key into cookie session
        $cacheKey = 'import_report_'.uniqid();
        Cache::put($cacheKey, $report, now()->addMinutes(5));

        return redirect()
            ->route('admin.users.import')
            ->with('import_report_key', $cacheKey)
            ->with('import_type', 'citizen');
    }

    /**
     * Handle Staff CSV import request.
     */
    public function importStaff(CsvImportRequest $request): RedirectResponse|JsonResponse
    {
        $file = $request->file('csv_file');
        $rollbackOnError = $request->boolean('rollback_on_error');
        $report = $this->importService->importStaff($file->getRealPath(), $rollbackOnError);

        if ($request->wantsJson()) {
            return response()->json($report);
        }

        // Store large report in cache (5 min TTL), only flash the key into cookie session
        $cacheKey = 'import_report_'.uniqid();
        Cache::put($cacheKey, $report, now()->addMinutes(5));

        return redirect()
            ->route('admin.users.import')
            ->with('import_report_key', $cacheKey)
            ->with('import_type', 'staff');
    }
}
