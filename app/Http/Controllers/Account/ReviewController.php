<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // Sửa trong vòng 30 ngày kể từ khi đăng (tuỳ bạn)
    private int $editWindowDays = 30;

    public function index()
    {
        $reviews = Review::where('user_id', Auth::id())
            ->latest()
            ->with(['product:id,slug,name,thumbnail', 'orderItem.order:id,code'])
            ->paginate(10);

        return view('account.reviews.index', compact('reviews'));
    }

    public function update(Request $request, Review $review)
    {
        abort_unless((int)$review->user_id === (int)Auth::id(), 403);

        // Giới hạn thời gian được sửa (bỏ nếu không cần)
        if ($review->created_at && $review->created_at->lt(now()->subDays($this->editWindowDays))) {
            return back()->withErrors("Bạn chỉ có thể sửa đánh giá trong {$this->editWindowDays} ngày sau khi đăng.");
        }

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'between:1,5'],
            'title'   => ['nullable', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $review->fill($data);

        // Nếu có thay đổi nội dung/sao/tiêu đề → đưa về chờ duyệt lại
        if ($review->isDirty(['rating', 'title', 'content'])) {
            $review->is_approved = false;
            if (\Schema::hasColumn('reviews', 'edited_at')) {
                $review->edited_at = now();
            }
        }

        $review->save();

        return back()->with('success', 'Đã cập nhật đánh giá (đang chờ duyệt lại).');
    }

    public function destroy(Review $review)
    {
        abort_unless((int)$review->user_id === (int)Auth::id(), 403);
        $review->delete(); // nếu dùng SoftDeletes sẽ là xóa mềm
        return back()->with('success', 'Đã xóa đánh giá.');
    }
}
