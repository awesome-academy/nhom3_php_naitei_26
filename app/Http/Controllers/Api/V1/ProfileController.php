<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateCitizenProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Support\Auth\AuthEventLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        Gate::authorize('view', $user);

        return ApiResponse::success(
            message: 'Lấy thông tin hồ sơ thành công.',
            data: UserResource::make($user),
        );
    }

    public function update(UpdateCitizenProfileRequest $request, AuthEventLogger $events): JsonResponse
    {
        $user = $request->user();

        Gate::authorize('update', $user);

        $user->fill($request->safe()->only([
            'name',
            'date_of_birth',
            'phone',
            'address',
            'email_notifications_enabled',
        ]));
        $user->save();

        $events->log(
            action: 'profile.updated',
            actor: $user,
            subject: $user,
            request: $request,
            description: 'Citizen profile updated.',
            metadata: [
                'fields' => array_keys($request->safe()->only([
                    'name',
                    'date_of_birth',
                    'phone',
                    'address',
                    'email_notifications_enabled',
                ])),
            ],
        );

        return ApiResponse::success(
            message: 'Cập nhật hồ sơ thành công.',
            data: UserResource::make($user->refresh()),
        );
    }
}
