<?php

namespace App\Services;

use App\Contracts\SkinAnalyzer;
use Illuminate\Support\Facades\Storage;

/**
 * Phân tích rất nhẹ bằng PHP/GD (không ML)
 */
class SimpleSkinAnalyzerService implements SkinAnalyzer
{
    public function analyze(iterable $photos): array
    {
        return $this->analyzePhotos($photos);
    }

    public function analyzePhotos(iterable $photos): array
    {
        $disk  = config('filesystems.default', 'public');
        $paths = [];

        foreach ($photos as $p) {
            try {
                // $p có thể là model (có ->path) hoặc string path
                $rel = is_object($p) && isset($p->path) ? (string)$p->path : (string)$p;
                $abs = \Illuminate\Support\Str::startsWith($rel, ['/', 'C:\\', 'D:\\'])
                    ? $rel
                    : Storage::disk($disk)->path($rel);

                if (is_readable($abs)) $paths[] = $abs;
            } catch (\Throwable $e) {
                // bỏ qua ảnh lỗi
            }
        }
        return $this->analyzePaths($paths);
    }

    public function analyzePaths(array $absolutePaths): array
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('PHP GD extension chưa bật – không thể phân tích ảnh.');
        }

        $all = ['oiliness' => 0, 'dryness' => 0, 'redness' => 0, 'acne_score' => 0];
        $cnt = 0;

        foreach ($absolutePaths as $path) {
            if (!is_readable($path)) continue;
            $m = $this->analyzeOne($path);
            if (!$m) continue;
            foreach ($all as $k => $v) $all[$k] += $m[$k] ?? 0;
            $cnt++;
        }

        if ($cnt === 0) return $all;
        foreach ($all as $k => $v) $all[$k] = $this->clamp($v / $cnt);
        return $all;
    }

    private function analyzeOne(string $path): ?array
    {
        $info = @getimagesize($path);
        if (!$info) return null;

        // tạo ảnh nguồn
        $im = null;
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $im = @imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $im = @imagecreatefrompng($path);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $im = @imagecreatefromwebp($path);
                } else {
                    $bin = @file_get_contents($path);
                    if ($bin !== false) $im = @imagecreatefromstring($bin);
                }
                break;
            default:
                $bin = @file_get_contents($path);
                if ($bin !== false) $im = @imagecreatefromstring($bin);
        }
        if (!$im) return null;

        $w = imagesx($im);
        $h = imagesy($im);

        // Downscale để chạy nhanh
        $tw = 160;
        $th = max(120, (int)round($h * ($tw / max(1, $w))));
        $tmp = imagecreatetruecolor($tw, $th);
        imagecopyresampled($tmp, $im, 0, 0, 0, 0, $tw, $th, $w, $h);
        imagedestroy($im);

        $N = 0;
        $sumL = 0;
        $sumS = 0;
        $specular = 0;
        $redmask = 0;
        $edge = 0;
        $prevLRow = array_fill(0, $tw, 0);

        for ($y = 0; $y < $th; $y++) {
            $prevL = 0;
            for ($x = 0; $x < $tw; $x++) {
                $rgb = imagecolorat($tmp, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                [$H, $S, $L] = $this->rgbToHsl($r, $g, $b);

                $N++;
                $sumL += $L;
                $sumS += $S;

                if ($L > 0.87 && $S < 0.25) $specular++; // dầu
                $isRedHue = ($H < 0.06 || $H > 0.94);
                if ($isRedHue && $S > 0.35 && $L > 0.22 && $L < 0.82) $redmask++;

                $d1 = abs($L - $prevL);
                $d2 = abs($L - $prevLRow[$x]);
                if ($d1 > 0.12 || $d2 > 0.12) $edge++;
                $prevL = $L;
                $prevLRow[$x] = $L;
            }
        }
        imagedestroy($tmp);

        $avgL = $sumL / max(1, $N);
        $avgS = $sumS / max(1, $N);

        $oiliness = $specular / max(1, $N) * 2.2;
        $redness  = $redmask  / max(1, $N) * 1.6;
        $texture  = $edge     / max(1, $N) * 1.4;

        $dryness  = (1 - $avgL) * 0.9 + ($avgS < 0.18 ? 0.18 : 0) + 0.25 * $texture;
        $acne     = 0.5 * $redness + 0.6 * $texture;

        return [
            'oiliness'   => $this->clamp($oiliness),
            'dryness'    => $this->clamp($dryness),
            'redness'    => $this->clamp($redness),
            'acne_score' => $this->clamp($acne),
        ];
    }

    private function clamp($v)
    {
        return max(0, min(1, (float)$v));
    }

    private function rgbToHsl($r, $g, $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $h = $s = $l = ($max + $min) / 2;
        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = ($b - $r) / $d + 2;
                    break;
                default:
                    $h = ($r - $g) / $d + 4;
                    break;
            }
            $h /= 6;
        }
        return [$h, $s, $l];
    }
}
