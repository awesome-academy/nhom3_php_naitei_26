<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\IndexNotificationRequest;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(IndexNotificationRequest $request): JsonResponse
    {
        $filter = $request->validated('filter', 'all');

        $query = $request->user()
            ->notifications()
            ->when($filter === 'unread', fn ($notificationQuery) => $notificationQuery->whereNull('read_at'))
            ->when($filter === 'read', fn ($notificationQuery) => $notificationQuery->whereNotNull('read_at'))
            ->latest();

        $notifications = $query->paginate(10);
        $payload = NotificationResource::collection($notifications)->response()->getData();

        return ApiResponse::success(
            'Lấy danh sách thông báo thành công.',
            [
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'notifications' => $payload->data,
                'links' => $payload->links,
                'meta' => $payload->meta,
            ],
        );
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        /** @var DatabaseNotification|null $record */
        $record = $request->user()->notifications()->whereKey($notification)->first();

        if ($record === null) {
            return ApiResponse::error('Không tìm thấy thông báo.', status: 404);
        }

        $record->markAsRead();

        return ApiResponse::success(
            'Đã đánh dấu thông báo là đã đọc.',
            [
                'id' => $record->id,
                'read_at' => $record->fresh()?->read_at?->toISOString(),
            ],
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return ApiResponse::success(
            'Đã đánh dấu tất cả thông báo là đã đọc.',
            ['unread_count' => 0],
        );
    }
}
