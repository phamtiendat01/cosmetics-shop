<?php

namespace App\Contracts;

/**
 * Phân tích SkinTest từ danh sách ảnh.
 * - Dùng 'iterable' để nhận được cả Collection<Photo> hoặc array<string>.
 */
interface SkinAnalyzer
{
    /**
     * @param iterable<\App\Models\SkinTestPhoto|string> $photos
     * @return array{oiliness:float,dryness:float,redness:float,acne_score:float}
     */
    public function analyze(iterable $photos): array;

    /**
     * @param iterable<\App\Models\SkinTestPhoto|string> $photos
     * @return array{oiliness:float,dryness:float,redness:float,acne_score:float}
     */
    public function analyzePhotos(iterable $photos): array;

    /**
     * @param array<int,string> $absolutePaths Absolute file paths
     * @return array{oiliness:float,dryness:float,redness:float,acne_score:float}
     */
    public function analyzePaths(array $absolutePaths): array;
}
