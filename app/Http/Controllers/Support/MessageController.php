<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Events\MessageSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * GET /livechat/{chat}/messages
     * Trả về tin nhắn (mới → cũ theo thứ tự thời gian tăng dần). Nếu user là participant (khách) -> cập nhật last_read_message_id
     */
    public function index(Request $request, Chat $chat)
    {
        $user = $request->user();

        // staff xem được mọi phòng; khách chỉ xem nếu là participant
        if ($user && !$user->hasAnyRole(['super-admin', 'admin', 'staff'])) {
            $isParticipant = ChatParticipant::where('chat_id', $chat->id)
                ->where('user_id', $user->id)
                ->exists();
            abort_unless($isParticipant, 403, 'Forbidden');
        }

        $messages = ChatMessage::where('chat_id', $chat->id)
            ->orderBy('id', 'asc')
            ->limit(500)
            ->get();

        if ($user && !$user->hasAnyRole(['super-admin', 'admin', 'staff']) && $messages->count()) {
            ChatParticipant::updateOrCreate(
                ['chat_id' => $chat->id, 'user_id' => $user->id],
                ['last_read_message_id' => $messages->last()->id]
            );
        }

        return response()->json($messages);
    }

    /**
     * POST /livechat/{chat}/messages
     * Tạo message, đảm bảo participant, cập nhật chat, broadcast MessageSent
     */
    public function store(Request $request, Chat $chat)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthorized');

        $isStaff    = $user->hasAnyRole(['super-admin', 'admin', 'staff']);
        $senderType = $isStaff ? 'staff' : 'customer';

        $request->validate([
            'type' => 'nullable|string|in:text',
            'body' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $message = ChatMessage::create([
                'chat_id'     => $chat->id,
                'sender_type' => $senderType,
                'sender_id'   => $user->id,
                'type'        => $request->input('type', 'text'),
                'body'        => $request->input('body'),
            ]);

            ChatParticipant::updateOrCreate(
                ['chat_id' => $chat->id, 'user_id' => $user->id],
                [
                    'role' => $senderType === 'staff' ? 'staff' : 'customer',
                    'joined_at' => now(),
                    'last_read_message_id' => $message->id,
                ]
            );

            // Auto assign staff if staff replied and not assigned
            if ($isStaff && empty($chat->assigned_to)) {
                $chat->assigned_to = $user->id;
            }

            $chat->last_message_at = now();
            $chat->save();

            DB::commit();

            // Broadcast (suppress broadcast failures)
            try {
                event(new MessageSent($message->fresh()));
            } catch (\Throwable $e) {
                Log::warning('Broadcast MessageSent failed: ' . $e->getMessage());
            }

            return response()->json(['ok' => true, 'message_id' => (int)$message->id]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Create chat message failed: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'Could not create message'], 500);
        }
    }

    /**
     * GET /livechat/unread-count
     * Trả số lượng phòng có tin nhắn chưa đọc cho staff
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['super-admin', 'admin', 'staff'])) {
            return response()->json(['count' => 0]);
        }

        // Số room có tin nhắn mới hơn last_read_message_id của participant (đơn giản)
        $rows = \DB::select(
            <<<'SQL'
            select count(distinct m.chat_id) as cnt
            from chat_messages m
            left join chat_participants p on p.chat_id = m.chat_id
            where (p.user_id is null or p.user_id <> ?)
              and m.id > COALESCE(p.last_read_message_id, 0)
        SQL,
            [$user->id]
        );

        $count = isset($rows[0]->cnt) ? (int)$rows[0]->cnt : 0;
        return response()->json(['count' => $count]);
    }
}
