<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Events\ChatCreated;

class ChatController extends Controller
{
    /**
     * POST /livechat/start
     * - nếu user: tìm/tao phòng và ensure participant
     * - nếu guest: dùng session để giữ chat_id (guest phòng)
     * - phát ChatCreated lên channel 'support' để admin biết
     */
    public function start(Request $request)
    {
        $user = $request->user();
        $chat = null;

        if ($user) {
            // tìm chat gần nhất mà user là participant
            $chatId = ChatParticipant::where('user_id', $user->id)
                ->orderByDesc('chat_id')
                ->value('chat_id');

            $chat = $chatId ? Chat::find($chatId) : null;

            if (!$chat) {
                $chat = Chat::create([
                    'customer_id'    => $user->id,
                    'status'         => 'open',
                    'last_message_at' => now(),
                ]);
            }

            ChatParticipant::updateOrCreate(
                ['chat_id' => $chat->id, 'user_id' => $user->id],
                ['role' => 'customer', 'joined_at' => now()]
            );
        } else {
            // guest: dùng session để bám 1 phòng
            $chatId = (int) $request->session()->get('livechat.chat_id', 0);
            $chat   = $chatId ? Chat::find($chatId) : null;

            if (!$chat) {
                $chat = Chat::create([
                    'customer_id'    => null,
                    'status'         => 'open',
                    'last_message_at' => now(),
                ]);
                $request->session()->put('livechat.chat_id', $chat->id);
            }
            // guest có thể có participant record không kèm user_id
            ChatParticipant::updateOrCreate(
                ['chat_id' => $chat->id, 'user_id' => null],
                ['role' => 'guest', 'joined_at' => now()]
            );
        }

        // thông báo cho staff (sidebar admin)
        try {
            event(new ChatCreated($chat));
        } catch (\Throwable $e) {
            // không block xử lý nếu broadcast fail
        }

        // public token (widget expects this). Use session id as simple token.
        $publicToken = $request->session()->getId();

        return response()->json([
            'ok' => true,
            'chat_id' => (int) $chat->id,
            'public_token' => (string) $publicToken,
        ]);
    }
}
