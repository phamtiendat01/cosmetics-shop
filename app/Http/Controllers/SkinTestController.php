<?php

namespace App\Http\Controllers;

use App\Contracts\SkinAnalyzer;
use App\Models\{SkinTest, SkinTestPhoto};
use App\Services\SuggestRoutineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SkinTestController extends Controller
{
    public function __construct(
        private SkinAnalyzer $analyzer,
        private SuggestRoutineService $suggest
    ) {}

    public function start(Request $r)
    {
        $data = $r->validate([
            'consent'        => 'required|boolean',
            'policy_version' => 'nullable|string',
            'chat_id'        => 'nullable|integer',
        ]);

        $test = SkinTest::create([
            'user_id'    => optional($r->user())->id,
            'session_id' => $r->session()->getId(),
            'status'     => 'pending',
        ]);

        // Ghi consent nếu có bảng
        if (($data['consent'] ?? false) && Schema::hasTable('skin_consent_logs')) {
            DB::table('skin_consent_logs')->insert([
                'skin_test_id'   => $test->id,
                'consent_at'     => now(),
                'policy_version' => $data['policy_version'] ?? null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return response()->json([
            'id'           => $test->id,
            'public_token' => $r->session()->getId(),
        ]);
    }

    public function upload(Request $r, SkinTest $skinTest)
    {
        $this->authorizeView($r, $skinTest);

        $r->validate([
            'photos'   => 'required',
            'photos.*' => 'image|max:4096',
        ]);

        foreach ((array) $r->file('photos') as $file) {
            $disk = config('filesystems.default', 'public');
            $path = $file->store("skin_tests/{$skinTest->id}", $disk);
            [$w, $h] = @getimagesize($file->getPathname()) ?: [null, null];

            SkinTestPhoto::create([
                'skin_test_id' => $skinTest->id,
                'path'         => $path,
                'width'        => $w,
                'height'       => $h,
                'face_ok'      => true,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function submit(Request $request, SkinTest $skinTest)
    {
        if (config('skin.driver') !== 'api') {
            Log::warning('SkinTest submit called while SKIN_DRIVER != api');
            return response()->json([
                'ok' => false,
                'message' => 'Cấu hình chưa đúng. Vui lòng đặt SKIN_DRIVER=api để dùng LLM.',
            ], 409);
        }

        $data = $request->validate(['budget' => ['nullable', 'integer', 'min:0']]);

        try {
            // có ảnh chưa?
            $has = method_exists($skinTest, 'photos') ? $skinTest->photos()->exists() : false;
            if (!$has && is_array($skinTest->photos_json ?? null)) {
                $has = count($skinTest->photos_json) > 0;
            }
            if (!$has) {
                return response()->json(['ok' => false, 'message' => 'Chưa nhận ảnh nào.'], 422);
            }

            // mark processing
            if (Schema::hasColumn($skinTest->getTable(), 'status'))  $skinTest->status = 'processing';
            if (Schema::hasColumn($skinTest->getTable(), 'budget'))  $skinTest->budget = $data['budget'] ?? null;
            $skinTest->save();

            // chuẩn bị input ảnh
            if (method_exists($skinTest, 'photos')) {
                $input = $skinTest->photos()->get(); // collection SkinTestPhoto
            } else {
                $disk  = config('filesystems.default', 'public');
                $paths = [];
                foreach ((array) $skinTest->photos_json as $rel) {
                    $abs = Storage::disk($disk)->path($rel);
                    if (is_readable($abs)) $paths[] = $abs;
                }
                $input = $paths;
            }

            // gọi analyzer (driver LLM)
            $metrics = $this->analyzer->analyze($input);

            // gợi ý routine + type
            $suggest  = $this->suggest->fromMetrics($metrics, $skinTest->budget ?? null);
            $routine  = $suggest['routine'] ?? [];
            $skinType = $suggest['type'] ?? null;

            // cập nhật SkinTest
            $updates = [];
            if (Schema::hasColumn($skinTest->getTable(), 'status'))               $updates['status'] = 'completed';
            if (Schema::hasColumn($skinTest->getTable(), 'metrics_json'))         $updates['metrics_json'] = $metrics;
            if (Schema::hasColumn($skinTest->getTable(), 'recommendation_json'))  $updates['recommendation_json'] = $routine;
            if (Schema::hasColumn($skinTest->getTable(), 'dominant_skin_type'))   $updates['dominant_skin_type'] = $skinType;
            if ($updates) $skinTest->update($updates);

            return response()->json([
                'ok'     => true,
                'status' => 'completed',
                'id'     => $skinTest->id,
                'payload' => [
                    'dominant_skin_type'  => $skinType,
                    'metrics'             => $metrics,
                    'recommendation_json' => $routine,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('skintest.submit failed', [
                'id'   => $skinTest->id ?? null,
                'msg'  => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            if (Schema::hasColumn($skinTest->getTable(), 'status')) {
                $skinTest->forceFill(['status' => 'failed'])->save();
            }
            return response()->json(['ok' => false, 'message' => 'Lỗi máy chủ khi submit.'], 500);
        }
    }

    public function show(Request $r, SkinTest $skinTest)
    {
        $this->authorizeView($r, $skinTest);

        // Lấy URL public của ảnh (nếu có quan hệ photos)
        $photos = [];
        if (method_exists($skinTest, 'photos')) {
            $disk = config('filesystems.default', 'public');
            $photos = $skinTest->photos()
                ->pluck('path')
                ->map(fn($p) => Storage::disk($disk)->url($p))
                ->values()
                ->all();
        } elseif (is_array($skinTest->photos_json ?? null)) {
            $disk = config('filesystems.default', 'public');
            $photos = collect($skinTest->photos_json)
                ->map(fn($p) => Storage::disk($disk)->url($p))
                ->all();
        }

        return response()->json([
            'id'                 => $skinTest->id,
            'status'             => $skinTest->status,
            'dominant_skin_type' => $skinTest->dominant_skin_type,
            'metrics'            => $skinTest->metrics_json,
            'routine'            => $skinTest->recommendation_json,
            'updated_at'         => $skinTest->updated_at,
            'photos'             => $photos, // 👈 thêm dòng này
        ]);
    }


    private function authorizeView(Request $r, SkinTest $skinTest)
    {
        $sameUser = $r->user() && $skinTest->user_id === $r->user()->id;
        $sameSess = $skinTest->session_id === $r->session()->getId();
        abort_unless($sameUser || $sameSess, 403, 'Forbidden');
    }

    public function camera()
    {
        return view('skin_test.camera');
    }
}
