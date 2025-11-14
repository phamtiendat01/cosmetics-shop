<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\SkinProfile;
use App\Models\SkinTest;
use App\Models\SkinTestPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SkinProfileController extends Controller
{
    /**
     * Trang hồ sơ làn da (hiển thị lịch sử + ảnh).
     */
    public function show(Request $request)
    {
        $userId = $request->user()->id;

        // Lấy tối đa 12 hồ sơ gần nhất
        $profiles = SkinProfile::where('user_id', $userId)
            ->latest('id')
            ->take(12)
            ->get();

        // Map về payload cho UI (metrics + ảnh)
        $history = $profiles->map(function (SkinProfile $p) {
            $photos = [];

            if ($p->skin_test_id) {
                $photos = SkinTestPhoto::where('skin_test_id', $p->skin_test_id)
                    ->orderBy('id')
                    ->pluck('path')
                    ->map(fn($rel) => Storage::url($rel))
                    ->values()
                    ->all();
            }

            return [
                'id'      => $p->id,
                'time'    => optional($p->updated_at)->format('d/m/Y H:i'),
                'type'    => $p->dominant_skin_type,
                'metrics' => (array) $p->metrics_json,
                'photos'  => $photos,
            ];
        })->values();

        $latestUpdated = optional($profiles->first()?->updated_at)->format('d/m/Y H:i');

        return view('account.skin_profile', [
            'history'       => $history,
            'latestUpdated' => $latestUpdated ?: '—',
        ]);
    }

    /**
     * Lưu 1 hồ sơ làn da mới từ SkinTest (khuyến nghị) hoặc từ payload gửi trực tiếp.
     * Request chấp nhận:
     * - skin_test_id (int) -> ưu tiên lấy metrics/routine/type từ SkinTest đã có
     * - dominant_skin_type (string)
     * - metrics (array)
     * - routine (array)
     * - note (string)
     */
    public function store(Request $request)
    {
        $request->validate([
            'skin_test_id'       => 'nullable|integer|exists:skin_tests,id',
            'dominant_skin_type' => 'nullable|string|max:32',
            'metrics'            => 'nullable|array',
            'routine'            => 'nullable|array',
            'note'               => 'nullable|string|max:190',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'Vui lòng đăng nhập.'], 401);
        }

        $skinTest = null;
        $type     = $request->string('dominant_skin_type')->toString() ?: null;
        $metrics  = $request->input('metrics');
        $routine  = $request->input('routine');

        if ($request->filled('skin_test_id')) {
            $skinTest = SkinTest::find($request->integer('skin_test_id'));
            if ($skinTest) {
                // Ưu tiên lấy dữ liệu từ bản test đã có
                $type    = $type    ?: $skinTest->dominant_skin_type;
                $metrics = $metrics ?: (array) $skinTest->metrics_json;
                $routine = $routine ?: (array) $skinTest->recommendation_json;
            }
        }

        $profile = SkinProfile::create([
            'user_id'            => $user->id,
            'skin_test_id'       => $skinTest?->id,
            'dominant_skin_type' => $type,
            'metrics_json'       => $metrics ?: [],
            'routine_json'       => $routine ?: [],
            'note'               => $request->string('note')->toString() ?: null,
        ]);

        return response()->json([
            'ok'       => true,
            'id'       => $profile->id,
            'redirect' => route('account.skin_profile'),
        ]);
    }
}
