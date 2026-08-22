<?php

use App\Exceptions\StaleDepartmentVersion;
use App\Http\Middleware\EnsureCitizen;
use App\Http\Middleware\EnsureInternalUser;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->redirectGuestsTo(fn ($request): string => $request->is('admin/*') || $request->is('admin')
            ? route('admin.login')
            : route('citizen.app'));
        $middleware->alias([
            'citizen' => EnsureCitizen::class,
            'internal' => EnsureInternalUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (StaleDepartmentVersion $exception, $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: $exception->getMessage(),
                    errors: [
                        'conflict' => ['Tải lại dữ liệu phòng ban trước khi thử lại.'],
                    ],
                    status: 409,
                );
            }

            return response()->view('errors.department-version-conflict', [
                'message' => $exception->getMessage(),
            ], 409);
        });

        $exceptions->render(function (ValidationException $exception, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    $exception->getMessage(),
                    $exception->errors(),
                    $exception->status,
                );
            }
        });

        $exceptions->render(function (AuthenticationException $exception, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Chưa đăng nhập.', null, 401);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, $request) {
            if ($request->is('api/*')) {
                $status = $exception->status() ?? 403;

                return ApiResponse::error(
                    $status === 404
                        ? 'Không tìm thấy tài nguyên.'
                        : 'Bạn không có quyền thực hiện thao tác này.',
                    null,
                    $status,
                );
            }
        });

        $exceptions->render(function (NotFoundHttpException $exception, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'Không tìm thấy tài nguyên.',
                    null,
                    404,
                );
            }
        });
    })->create();
