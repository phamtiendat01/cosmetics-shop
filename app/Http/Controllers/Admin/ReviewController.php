<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review; // ✅ dùng bảng reviews mới
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $r)
    {
        $filters = [
            'q'       => trim((string)$r->get('q', '')),
            'product' => trim((string)$r->get('product', '')),
            'rating'  => $r->filled('rating') ? (int)$r->get('rating') : null, // 1..5
            'state'   => $r->get('state'), // 'approved' | 'pending' | null
        ];

        $q = Review::query()
            ->with([
                'product:id,slug,name,thumbnail',
                'user:id,name,email',
                'orderItem.order:id,code',
            ])
            ->latest();

        if ($filters['q'] !== '') {
            $q->where(function ($qq) use ($filters) {
                $qq->where('title', 'like', '%' . $filters['q'] . '%')
                    ->orWhere('content', 'like', '%' . $filters['q'] . '%');
            });
        }
        if ($filters['product'] !== '') {
            $q->whereHas('product', function ($p) use ($filters) {
                $p->where('name', 'like', '%' . $filters['product'] . '%');
            });
        }
        if ($filters['rating']) {
            $q->where('rating', $filters['rating']);
        }
        if ($filters['state'] === 'approved')  $q->where('is_approved', 1);
        if ($filters['state'] === 'pending')   $q->where('is_approved', 0);

        $reviews = $q->paginate(20)->withQueryString();

        $counts = [
            'all'      => Review::count(),
            'approved' => Review::where('is_approved', 1)->count(),
            'pending'  => Review::where('is_approved', 0)->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'counts', 'filters'));
    }

    public function show(Review $review)
    {
        $review->load(['product:id,slug,name,thumbnail', 'user:id,name,email', 'orderItem.order:id,code']);
        return view('admin.reviews.show', compact('review'));
    }

    public function approve(Review $review)
    {
        $review->is_approved = true;
        $review->save();

        return back()->with('ok', 'Đã duyệt đánh giá.');
    }

    public function unapprove(Review $review)
    {
        $review->is_approved = false;
        $review->save();

        return back()->with('ok', 'Đã bỏ duyệt đánh giá.');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return back()->with('ok', 'Đã xoá đánh giá.');
    }

    public function bulkApprove(Request $r)
    {
        $ids = (array)$r->input('ids', []);
        if ($ids) Review::whereIn('id', $ids)->update(['is_approved' => true]);
        return back()->with('ok', 'Đã duyệt các đánh giá đã chọn.');
    }

    public function bulkDestroy(Request $r)
    {
        $ids = (array)$r->input('ids', []);
        if ($ids) Review::whereIn('id', $ids)->delete();
        return back()->with('ok', 'Đã xoá các đánh giá đã chọn.');
    }

    // Nếu bạn có form trả lời trong admin, dùng cột 'admin_reply' (nếu chưa có thì bỏ route reply)
    public function reply(Request $r, Review $review)
    {
        $data = $r->validate(['reply' => ['required', 'string', 'max:2000']]);
        if (!\Schema::hasColumn('reviews', 'admin_reply')) {
            return back()->with('err', 'CSDL chưa có cột admin_reply để lưu trả lời.');
        }
        $review->admin_reply = $data['reply'];
        $review->save();
        return back()->with('ok', 'Đã trả lời.');
    }
}
