<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotIntent;
use App\Models\BotTool;
use App\Models\BotConversation;
use App\Models\BotMessage;
use App\Models\BotAnalytic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BotController extends Controller
{
    /**
     * Dashboard - Tổng quan chatbot
     */
    public function index()
    {
        $stats = [
            'total_conversations' => BotConversation::count(),
            'active_conversations' => BotConversation::where('status', 'active')->count(),
            'total_messages' => BotMessage::count(),
            'total_intents' => BotIntent::active()->count(),
            'total_tools' => BotTool::active()->count(),
        ];

        // Top intents (30 ngày gần nhất)
        $topIntents = BotMessage::select('intent', DB::raw('COUNT(*) as count'))
            ->whereNotNull('intent')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('intent')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Daily messages (30 ngày)
        $dailyMessages = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = BotMessage::whereDate('created_at', $date)->count();
            $dailyMessages[$date] = $count;
        }

        // Intent distribution
        $intentDistribution = BotMessage::select('intent', DB::raw('COUNT(*) as count'))
            ->whereNotNull('intent')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('intent')
            ->get()
            ->pluck('count', 'intent')
            ->toArray();

        return view('admin.bot.index', compact('stats', 'topIntents', 'dailyMessages', 'intentDistribution'));
    }

    /**
     * Quản lý Intents
     */
    public function intents()
    {
        $intents = BotIntent::orderByDesc('priority')->orderBy('name')->paginate(20);
        // Lấy tất cả tools để map với intents
        $availableTools = BotTool::where('is_active', true)
            ->orderBy('display_name')
            ->get(['id', 'name', 'display_name', 'description']);
        return view('admin.bot.intents', compact('intents', 'availableTools'));
    }

    /**
     * Tạo/Edit Intent
     */
    public function intentStore(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:bot_intents,id',
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'examples' => 'nullable|array',
            'examples.*' => 'string|max:500',
            'handler_class' => 'nullable|string',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:1000',
            // Config fields
            'response_template' => 'nullable|string',
            'required_entities' => 'nullable|array',
            'optional_entities' => 'nullable|array',
            'follow_up_questions' => 'nullable|array',
            'follow_up_questions.*' => 'string|max:500',
            'tools' => 'nullable|array',
            'tools.*' => 'string|exists:bot_tools,name',
            'confidence_threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        // Build config
        $config = [
            'response_template' => $validated['response_template'] ?? null,
            'required_entities' => $validated['required_entities'] ?? [],
            'optional_entities' => $validated['optional_entities'] ?? [],
            'follow_up_questions' => $validated['follow_up_questions'] ?? [],
            'tools' => $validated['tools'] ?? [],
            'confidence_threshold' => $validated['confidence_threshold'] ?? 0.7,
        ];

        $intentData = [
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'examples' => $validated['examples'] ?? [],
            'handler_class' => $validated['handler_class'] ?? null,
            'is_active' => $request->has('is_active'),
            'priority' => $validated['priority'] ?? 0,
            'config' => $config,
        ];

        if (isset($validated['id'])) {
            $intent = BotIntent::findOrFail($validated['id']);
            $intent->update($intentData);
            $message = 'Intent đã được cập nhật!';
        } else {
            BotIntent::create($intentData);
            $message = 'Intent đã được tạo!';
        }

        // Clear cache để bot nhận diện examples mới ngay lập tức
        \Illuminate\Support\Facades\Cache::forget('bot.intents.active');
        \Illuminate\Support\Facades\Cache::forget("bot.intent.{$validated['name']}");

        return redirect()->route('admin.bot.intents')->with('success', $message);
    }

    /**
     * Quản lý Tools
     */
    public function tools()
    {
        // Chỉ hiển thị các tools có question và answer (câu hỏi tự động)
        // Ẩn các tools kỹ thuật không có question/answer
        $tools = BotTool::whereNotNull('question')
            ->where('question', '!=', '')
            ->whereNotNull('answer')
            ->where('answer', '!=', '')
            ->orderBy('order')
            ->orderBy('category')
            ->orderBy('display_name')
            ->paginate(20);
        return view('admin.bot.tools', compact('tools'));
    }

    /**
     * Tạo/Edit Tool
     */
    public function toolStore(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:bot_tools,id',
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'question' => 'required|string|max:500', // Câu hỏi hiển thị cho user
            'answer' => 'required|string', // Câu trả lời
            'category' => 'required|string|max:50', // Phân loại
            'order' => 'nullable|integer|min:0',
            'icon' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'parameters_schema' => 'nullable|string',
            'handler_class' => 'nullable|string',
            'is_active' => 'boolean',
            'config' => 'nullable|string',
        ]);

        // Parse JSON fields
        if (isset($validated['parameters_schema']) && is_string($validated['parameters_schema']) && !empty($validated['parameters_schema'])) {
            $validated['parameters_schema'] = json_decode($validated['parameters_schema'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()->withErrors(['parameters_schema' => 'JSON không hợp lệ'])->withInput();
            }
        } else {
            $validated['parameters_schema'] = [];
        }
        
        if (isset($validated['config']) && is_string($validated['config']) && !empty($validated['config'])) {
            $validated['config'] = json_decode($validated['config'], true) ?? [];
        } else {
            $validated['config'] = null;
        }
        
        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        // Nếu có ID thì update, không thì tạo mới
        if (!empty($validated['id'])) {
            $tool = BotTool::findOrFail($validated['id']);
            $tool->update($validated);
        } else {
            BotTool::create($validated);
        }

        return redirect()->route('admin.bot.tools')->with('success', 'Câu hỏi tự động đã được lưu!');
    }

    /**
     * Xóa Tool
     */
    public function toolDestroy(BotTool $tool)
    {
        $tool->delete();
        return redirect()->route('admin.bot.tools')->with('success', 'Câu hỏi tự động đã được xóa!');
    }

    /**
     * Xem Conversations
     */
    public function conversations(Request $request)
    {
        $query = BotConversation::with(['user', 'messages'])
            ->orderByDesc('updated_at');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('session_id', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $conversations = $query->withCount('messages')->paginate(20);
        return view('admin.bot.conversations', compact('conversations'));
    }

    /**
     * Xem chi tiết Conversation
     */
    public function conversation(BotConversation $conversation)
    {
        $conversation->load(['user', 'messages', 'analytics']);
        return view('admin.bot.conversation', compact('conversation'));
    }

    /**
     * Analytics
     */
    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Overall stats
        $stats = [
            'total_interactions' => BotAnalytic::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'intent_detections' => BotAnalytic::where('event_type', 'intent_detected')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'tool_calls' => BotAnalytic::where('event_type', 'tool_called')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'avg_latency' => (float)(BotAnalytic::where('event_type', 'latency')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->get()
                ->map(fn($a) => (float)($a->data['latency_ms'] ?? 0))
                ->avg() ?? 0),
        ];

        // Intent performance
        $intentStats = BotMessage::select('intent', DB::raw('COUNT(*) as count'), DB::raw('AVG(confidence) as avg_confidence'))
            ->whereNotNull('intent')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('intent')
            ->orderByDesc('count')
            ->get();

        // Tool usage
        $toolStats = DB::table('bot_analytics')
            ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.tool')) as tool"), DB::raw('COUNT(*) as count'))
            ->where('event_type', 'tool_called')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull(DB::raw("JSON_EXTRACT(data, '$.tool')"))
            ->groupBy('tool')
            ->orderByDesc('count')
            ->get();

        return view('admin.bot.analytics', compact('stats', 'intentStats', 'toolStats', 'dateFrom', 'dateTo'));
    }
}
