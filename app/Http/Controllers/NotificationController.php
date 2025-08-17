<?php

namespace App\Http\Controllers;

use App\Models\OrderHistoryModel;
use App\Models\OrderModel;
use App\Models\StudentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    /**
     * Fetch notifications for the authenticated student user.
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $student = StudentModel::where('user_id', $user->id)->first();
        if (!$student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Student record not found.',
            ], 404);
        }

        $studentId = $student->student_id;

        // Pull newest first (email & UI both want latest on top)
        $histories = OrderHistoryModel::query()
            ->select(
                'order_history.history_id',
                'order_history.order_id',
                'order_history.status',
                'order_history.updated_at',
                'order_history.updated_by',
                'order_history.is_read',
                'users.name as updated_by_name'
            )
            ->join('order', 'order_history.order_id', '=', 'order.order_id')
            ->leftJoin('users', 'order_history.updated_by', '=', 'users.id')
            ->where('order.student_id', $studentId)
            ->orderBy('order_history.updated_at', 'desc')
            ->get();

        $notifications = $histories->map(function ($history) {
            return $this->formatNotification($history);
        });

        // Count unread
        $unreadCount = OrderHistoryModel::join('order', 'order_history.order_id', '=', 'order.order_id')
            ->where('order.student_id', $studentId)
            ->where('order_history.is_read', false)
            ->count();

        return response()->json([
            'status'      => 'success',
            'data'        => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Map a history row into a notification payload suitable for:
     *  - In‑app list (type, title, message, icon, color, time_ago, updated_by)
     *  - GenRev email blade (title, message, status, createdAt, actionText, actionUrl)
     */
    private function formatNotification($history): array
    {
        // Normalize status to a consistent key (supports DB values like ReadyForPickup / readyforpickup / Ready for Pickup)
        $rawStatus = (string) $history->status;
        $key = Str::of($rawStatus)->replace(' ', '')->lower(); // e.g. "ReadyForPickup" -> "readyforpickup"

        // Messages by status
        $statusMessages = [
            'pending'         => "Your order #{$history->order_id} is pending confirmation.",
            'paid'            => "Your order #{$history->order_id} payment has been confirmed.",
            'processing'      => "Your order #{$history->order_id} is being processed.",
            'readyforpickup'  => "Your order #{$history->order_id} is ready for pickup at the coop office.",
            'completed'       => "Your order #{$history->order_id} has been completed.",
            'cancelled'       => "Your order #{$history->order_id} has been cancelled.",
        ];

        // Label in the UI pill
        $statusTypes = [
            'pending'         => 'ORDER UPDATE',
            'paid'            => 'ORDER UPDATE',
            'processing'      => 'ORDER UPDATE',
            'readyforpickup'  => 'ORDER UPDATE',
            'completed'       => 'ORDER UPDATE',
            'cancelled'       => 'ORDER UPDATE',
        ];

        // Same icon path (you can swap per status later)
        $statusIcons = [
            'pending'         => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'paid'            => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'processing'      => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'readyforpickup'  => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'completed'       => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'cancelled'       => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        ];

        // Colors for UI chip
        $statusColors = [
            'pending'         => '#EDD100',
            'paid'            => '#047705',
            'processing'      => '#3B82F6',
            'readyforpickup'  => '#8B5CF6',
            'completed'       => '#10B981',
            'cancelled'       => '#EF4444',
        ];

        // Fallback message if a new/unknown status appears
        $message = $statusMessages[$key] ?? "Order #{$history->order_id} status updated to {$history->status}.";

        // Build an action URL to view/track the order (adjust if you have a named route)
        $actionUrl = url("/orders/{$history->order_id}"); // change to route('orders.show', $history->order_id) if available

        return [
            // In‑app notification fields
            'history_id'  => $history->history_id,
            'type'        => $statusTypes[$key] ?? 'ORDER UPDATE',
            'title'       => "Order #{$history->order_id} Status Update",
            'message'     => $message,
            'icon'        => $statusIcons[$key] ?? $statusIcons['pending'],
            'color'       => $statusColors[$key] ?? '#EDD100',
            'time_ago'    => optional($history->updated_at)->diffForHumans(),
            'updated_by'  => $history->updated_by_name ?? 'GenRev Team',

            // Email blade specific fields
            'status'      => $history->status, // raw status for display
            'createdAt'   => optional($history->updated_at)?->toIso8601String(),
            'actionText'  => $key === 'readyforpickup' ? 'View pickup details' : 'View order',
            'actionUrl'   => $actionUrl,
        ];
    }

    /**
     * Mark all notifications as read for the current student.
     */
    public function markNotificationsAsRead()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $student = StudentModel::where('user_id', $user->id)->first();
        Log::info('🔔 markNotificationsAsRead called', ['user_id' => $user->id]);

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
        }

        $updated = OrderHistoryModel::join('order', 'order_history.order_id', '=', 'order.order_id')
            ->where('order.student_id', $student->student_id)
            ->where('order_history.is_read', false)
            ->update(['order_history.is_read' => true]);

        Log::info('✅ Notifications marked as read', ['updated_rows' => $updated]);

        return response()->json(['status' => 'success', 'message' => 'All notifications marked as read.']);
    }
}
