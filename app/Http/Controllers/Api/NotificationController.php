<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->notificationService->getNotifications(
            $request->user(),
            $request->per_page ?? 15
        );

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user());

        return response()->json([
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $this->notificationService->markAsRead($notification);

        return response()->json([
            'message' => 'تم تحديد الإشعار كمقروء',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'message' => 'تم تحديد جميع الإشعارات كمقروءة',
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $this->notificationService->deleteNotification($notification);

        return response()->json([
            'message' => 'تم حذف الإشعار بنجاح',
        ]);
    }
}
