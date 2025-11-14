<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ChatParticipant;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Register the event broadcasting channels your application supports.
|
*/

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    if (!$user) {
        return false;
    }

    // staff luôn được phép
    if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'admin', 'staff'])) {
        return true;
    }

    // participant check
    return ChatParticipant::where('chat_id', (int) $chatId)
        ->where('user_id', $user->id)
        ->exists();
});
