@extends('admin.layouts.app')
@section('title', 'CosmeBot - Chủ đề trò chuyện')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-brain text-rose-600"></i>
            Chủ đề trò chuyện (Intents)
        </h1>
        <p class="text-slate-600 mt-1">Quản lý các chủ đề mà bot có thể hiểu và trả lời (VD: Tìm sản phẩm, Tra cứu đơn, Hỏi về chính sách...)</p>
        <div class="mt-2 text-sm text-amber-600 bg-amber-50 px-3 py-2 rounded-lg inline-block">
            <i class="fa-solid fa-lightbulb mr-1"></i>
            <strong>Gợi ý:</strong> Thêm các chủ đề như "Tìm sản phẩm", "Tra cứu đơn hàng", "Hỏi về phí ship", "Chính sách đổi trả"...
        </div>
    </div>
    <button onclick="document.getElementById('intentModal').classList.remove('hidden')" 
        class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Thêm chủ đề
    </button>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-xl p-4">
        <div class="text-2xl font-bold text-rose-600 mb-1">{{ $intents->total() }}</div>
        <div class="text-sm text-slate-600">Tổng chủ đề</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-4">
        <div class="text-2xl font-bold text-green-600 mb-1">{{ $intents->where('is_active', true)->count() }}</div>
        <div class="text-sm text-slate-600">Đang hoạt động</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-4">
        <div class="text-2xl font-bold text-blue-600 mb-1">{{ $intents->where('is_active', false)->count() }}</div>
        <div class="text-sm text-slate-600">Đã tắt</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-4">
        <div class="text-2xl font-bold text-purple-600 mb-1">{{ $intents->max('priority') ?? 0 }}</div>
        <div class="text-sm text-slate-600">Độ ưu tiên cao nhất</div>
    </div>
</div>

{{-- Table --}}
<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Tên chủ đề</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Mô tả</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Độ ưu tiên</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Trạng thái</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse($intents as $intent)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4">
                    <div class="font-medium text-slate-900">{{ $intent->display_name }}</div>
                    <div class="text-xs text-slate-500 font-mono mt-1">{{ $intent->name }}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-slate-700 max-w-md">{{ Str::limit($intent->description ?? 'Chưa có mô tả', 80) }}</div>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">
                        {{ $intent->priority }}
                    </span>
                    <div class="text-xs text-slate-500 mt-1">Số càng cao càng ưu tiên</div>
                </td>
                <td class="px-6 py-4">
                    @if($intent->is_active)
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold flex items-center gap-1 w-fit">
                        <i class="fa-solid fa-check-circle text-xs"></i> Hoạt động
                    </span>
                    @else
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-semibold flex items-center gap-1 w-fit">
                        <i class="fa-solid fa-pause-circle text-xs"></i> Tắt
                    </span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    <button onclick="editIntent(@json($intent))" 
                        class="text-blue-600 hover:text-blue-700 text-sm font-medium flex items-center gap-1">
                        <i class="fa-solid fa-edit"></i> Sửa
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-8 text-center">
                    <div class="text-slate-500 mb-2">
                        <i class="fa-solid fa-inbox text-4xl mb-3"></i>
                    </div>
                    <p class="text-slate-600 font-medium">Chưa có chủ đề nào</p>
                    <p class="text-sm text-slate-500 mt-1">Hãy thêm chủ đề đầu tiên để bot hiểu được ý định của khách hàng!</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div class="mt-4">
    {{ $intents->links() }}
</div>

{{-- Modal --}}
<div id="intentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <form method="POST" action="{{ route('admin.bot.intents.store') }}" class="p-6">
            @csrf
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-900">Thêm/Sửa chủ đề</h3>
                <button type="button" onclick="document.getElementById('intentModal').classList.add('hidden')" 
                    class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start gap-2">
                        <i class="fa-solid fa-info-circle text-blue-600 mt-0.5"></i>
                        <div class="text-sm text-blue-800">
                            <strong>Hướng dẫn:</strong> Chủ đề là các ý định mà bot có thể hiểu. Ví dụ: Khi khách hỏi "Tìm sản phẩm cho da dầu" → Bot hiểu đây là chủ đề "Tìm sản phẩm"
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Tên hiển thị <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="display_name" id="intent_display_name" required
                        placeholder="VD: Tìm sản phẩm"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <p class="text-xs text-slate-500 mt-1">Tên dễ hiểu cho admin (không hiển thị cho khách hàng)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Tên kỹ thuật (name) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="intent_name" required
                        placeholder="VD: product_search"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <p class="text-xs text-slate-500 mt-1">Tên kỹ thuật dùng trong code (không có dấu cách, VD: product_search, order_tracking)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mô tả</label>
                    <textarea name="description" id="intent_description" rows="3"
                        placeholder="VD: Khách hàng muốn tìm kiếm sản phẩm theo tiêu chí (loại da, ngân sách, vấn đề da...)"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Độ ưu tiên (0-1000)</label>
                    <input type="number" name="priority" id="intent_priority" value="0" min="0" max="1000"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <p class="text-xs text-slate-500 mt-1">Số càng cao, bot càng ưu tiên hiểu chủ đề này trước (mặc định: 0)</p>
                </div>

                <div class="flex items-center gap-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" id="intent_is_active" value="1" checked
                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="ml-2 text-sm text-slate-700">Kích hoạt chủ đề này</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-save"></i> Lưu
                </button>
                <button type="button" onclick="document.getElementById('intentModal').classList.add('hidden')" 
                    class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition">
                    Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editIntent(intent) {
    document.getElementById('intent_name').value = intent.name;
    document.getElementById('intent_display_name').value = intent.display_name;
    document.getElementById('intent_description').value = intent.description || '';
    document.getElementById('intent_priority').value = intent.priority || 0;
    document.getElementById('intent_is_active').checked = intent.is_active;
    document.getElementById('intentModal').classList.remove('hidden');
}
</script>
@endsection
