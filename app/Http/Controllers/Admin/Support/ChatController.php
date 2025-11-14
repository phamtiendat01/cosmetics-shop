<?php

namespace App\Http\Controllers\Admin\Support;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $adminId = Auth::id();

        // ✅ CHỈ LẤY CHAT ĐÃ CÓ TIN NHẮN (có nội dung chat)
        // Lấy id của chat mới nhất cho mỗi "người" (group bằng customer_id nếu có, nếu ko có group bằng chat id)
        // NHƯNG chỉ lấy những chat đã có ít nhất 1 tin nhắn
        $groupedIds = DB::table('chats as c')
            ->join('chat_messages as m', 'c.id', '=', 'm.chat_id')
            ->selectRaw('MAX(c.id) as id')
            ->groupBy(DB::raw('COALESCE(c.customer_id, c.id)'))
            ->pluck('id')
            ->toArray();

        if (empty($groupedIds)) {
            $seed = [];
            return view('admin.support.chats.index', compact('seed'));
        }

        // ✅ CHỈ LẤY CHAT CÓ USER ĐÃ ĐĂNG NHẬP HOẶC ĐÃ CÓ TIN NHẮN
        // Lấy thông tin chat (mới nhất mỗi user), join user nếu có, và participant record của admin (để tính unread)
        $rows = DB::table('chats as c')
            ->leftJoin('users as u', 'c.customer_id', '=', 'u.id')
            ->leftJoin('chat_participants as p', function ($join) use ($adminId) {
                $join->on('p.chat_id', '=', 'c.id')
                    ->where('p.user_id', '=', $adminId);
            })
            ->whereIn('c.id', $groupedIds)
            // ✅ CHỈ HIỂN THỊ KHI: có customer_id (user đã đăng nhập) HOẶC đã có tin nhắn
            ->where(function ($query) {
                $query->whereNotNull('c.customer_id') // User đã đăng nhập
                    ->orWhereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('chat_messages')
                            ->whereColumn('chat_messages.chat_id', 'c.id')
                            ->whereNotNull('chat_messages.body'); // Đã có tin nhắn
                    });
            })
            ->select(
                'c.id',
                'c.status',
                'u.name as customer_name',
                DB::raw('NULL as guest_name'),    // tránh truy vấn c.customer_name khi cột không tồn tại
                'c.last_message_at',
                'p.last_read_message_id'
            )
            ->orderByDesc('c.last_message_at')
            ->get();

        // Map to seed with unread count (tin nhắn của customer có id > last_read_message_id)
        $seed = collect($rows)->map(function ($r) {
            $lastRead = (int) ($r->last_read_message_id ?? 0);

            $unread = DB::table('chat_messages')
                ->where('chat_id', $r->id)
                ->where('sender_type', 'customer')
                ->when($lastRead > 0, function ($q) use ($lastRead) {
                    $q->where('id', '>', $lastRead);
                })
                ->count();

            $displayName = $r->customer_name ?? $r->guest_name ?? null;

            return [
                'id' => (int) $r->id,
                'customer_name' => $displayName ? (string)$displayName : null,
                'status' => $r->status ?? 'open',
                'last_message_at' => $r->last_message_at,
                'unread' => (int) $unread,
            ];
        })->values()->all();

        return view('admin.support.chats.index', compact('seed'));
    }
}
