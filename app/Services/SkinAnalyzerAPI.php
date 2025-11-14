<?php

namespace App\Services;

use App\Contracts\SkinAnalyzer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SkinAnalyzerAPI implements SkinAnalyzer
{
    public function __construct(
        protected ?SimpleSkinAnalyzerService $fallback = null
    ) {}

    public function analyze(iterable $photos): array
    {
        return $this->analyzePhotos($photos);
    }

    public function analyzePhotos(iterable $photos): array
    {
        $paths = $this->normalizeToAbsolutePaths($photos);
        return $this->analyzePaths($paths);
    }

    public function analyzePaths(array $absolutePaths): array
    {
        $sum = ['oiliness' => 0, 'dryness' => 0, 'redness' => 0, 'acne_score' => 0];
        $nOk = 0;

        foreach ($absolutePaths as $path) {
            if (!is_readable($path)) continue;

            $mime = $this->detectMime($path);
            $b64  = base64_encode(@file_get_contents($path) ?: '');

            $one = $this->callGemini($b64, $mime);
            if (!$one && $this->fallback) {
                $one = $this->fallback->analyzePaths([$path]);
            }

            if (is_array($one)) {
                foreach ($sum as $k => $_) $sum[$k] += (float)($one[$k] ?? 0);
                $nOk++;
            }
        }

        if ($nOk === 0) {
            return $this->fallback
                ? $this->fallback->analyzePaths($absolutePaths)
                : ['oiliness' => 0, 'dryness' => 0, 'redness' => 0, 'acne_score' => 0];
        }

        foreach ($sum as $k => $v) $sum[$k] = $this->clamp($v / $nOk);
        return $sum;
    }

    protected function callGemini(string $b64, string $mime): ?array
    {
        try {
            $base  = rtrim((string)config('skin.gemini.base', 'https://generativelanguage.googleapis.com'), '/');
            $model = (string)config('skin.gemini.model', 'gemini-2.5-flash');
            $key   = (string)config('skin.gemini.key');

            if (!$key) throw new \RuntimeException('Missing GEMINI_API_KEY');

            $url = "{$base}/v1beta/models/{$model}:generateContent?key={$key}";
            $prompt = <<<TXT
Bạn là trợ lý da liễu. Hãy chấm điểm các chỉ số làn da dựa trên ảnh khuôn mặt (0..1):
- oiliness
- dryness
- redness
- acne_score
Chỉ trả về JSON:
{"oiliness":0.0,"dryness":0.0,"redness":0.0,"acne_score":0.0}
TXT;

            $payload = [
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => [
                            ['text' => $prompt],
                            // v1beta: inlineData + mimeType
                            ['inlineData' => ['mimeType' => $mime, 'data' => $b64]],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'response_mime_type' => 'application/json',
                ],
            ];

            $res = Http::timeout((int)config('skin.gemini.timeout', 25))
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if (!$res->ok()) {
                Log::warning('Gemini analyze non-200', ['code' => $res->status(), 'body' => $res->body()]);
                return null;
            }

            $txt = data_get($res->json(), 'candidates.0.content.parts.0.text');
            if (!$txt) return null;

            if (preg_match('/\{.*\}/s', $txt, $m)) $txt = $m[0];
            $json = json_decode($txt, true);
            if (!is_array($json)) return null;

            return [
                'oiliness'   => $this->clamp((float)($json['oiliness']   ?? 0)),
                'dryness'    => $this->clamp((float)($json['dryness']    ?? 0)),
                'redness'    => $this->clamp((float)($json['redness']    ?? 0)),
                'acne_score' => $this->clamp((float)($json['acne_score'] ?? 0)),
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini analyze error', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    // -------- helpers --------

    protected function normalizeToAbsolutePaths(iterable $photos): array
    {
        $out = [];
        $disk = config('filesystems.default', 'public');

        foreach ($photos as $p) {
            $rel = is_object($p) && isset($p->path) ? (string)$p->path : (string)$p;
            $abs = Str::startsWith($rel, ['/', 'C:\\', 'D:\\'])
                ? $rel
                : Storage::disk($disk)->path($rel);

            if (is_file($abs)) $out[] = $abs;
        }
        return $out;
    }

    protected function detectMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'        => 'image/png',
            'webp'       => 'image/webp',
            default      => 'image/jpeg',
        };
    }

    protected function clamp(float $v): float
    {
        return max(0.0, min(1.0, $v));
    }
}
