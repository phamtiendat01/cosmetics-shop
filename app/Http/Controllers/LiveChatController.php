<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LiveChatController extends Controller
{
    public function index(Request $r)
    {
        // Mở UI chung, nếu có ?open=ID thì mở thẳng cuộc trò chuyện đó
        return view('livechat.index', ['chatId' => $r->query('open')]);
    }

    public function show($chatId)
    {
        // Dùng chung 1 view, truyền sẵn chatId
        return view('livechat.index', ['chatId' => $chatId]);
    }
}
