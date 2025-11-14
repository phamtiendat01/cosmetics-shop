<?php

namespace App\Jobs;

use App\Contracts\SkinAnalyzer;
use App\Events\{SkinTestCompleted, SkinTestFailed};
use App\Models\SkinTest;
use App\Services\SuggestRoutineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSkinTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $skinTestId,
        public string $publicToken,
        public ?int $budget = null
    ) {}

    public function handle(): void
    {
        $test = SkinTest::with('photos')->findOrFail($this->skinTestId);

        try {
            /** @var SkinAnalyzer $analyzer */
            $analyzer = app(SkinAnalyzer::class);

            // 1) Phân tích ảnh qua driver (simple/api)
            $metrics = $analyzer->analyze($test->photos);

            // 2) Gợi ý routine + xác định skin type
            /** @var SuggestRoutineService $sugg */
            $sugg    = app(SuggestRoutineService::class);
            $result  = $sugg->fromMetrics($metrics, $this->budget);
            $skinType = $result['type']    ?? null;
            $routine  = $result['routine'] ?? [];

            // 3) Lưu & bắn event
            $test->update([
                'status'               => 'completed',
                'dominant_skin_type'   => $skinType,
                'metrics_json'         => $metrics,
                'recommendation_json'  => $routine,
                'failed_reason'        => null,
            ]);

            broadcast(new SkinTestCompleted($test, $this->publicToken));
        } catch (\Throwable $e) {
            $test->update([
                'status'        => 'failed',
                'failed_reason' => mb_substr($e->getMessage(), 0, 190),
            ]);
            broadcast(new SkinTestFailed($test, $this->publicToken));
            report($e);
        }
    }
}
