<?php

namespace App\Services\Bot;

/**
 * ResponseTemplateEngine - Render response templates với variables
 */
class ResponseTemplateEngine
{
    /**
     * Render template với data
     *
     * @param string $template
     * @param array $data {
     *   greeting: string,
     *   intent_description: string,
     *   skin_types: array,
     *   budget: array {min, max},
     *   concerns: array,
     *   products: array,
     *   follow_up_questions: array
     * }
     * @return string
     */
    public function render(string $template, array $data = []): string
    {
        if (empty($template)) {
            return '';
        }

        $content = $template;

        // Replace simple variables
        $content = $this->replaceVariables($content, $data);

        // Process conditional blocks
        $content = $this->processConditionalBlocks($content, $data);

        // Clean up multiple newlines
        $content = preg_replace('/\n{3,}/u', "\n\n", $content);

        return trim($content);
    }

    /**
     * Replace variables trong template
     */
    private function replaceVariables(string $content, array $data): string
    {
        // Greeting
        $content = str_replace('{greeting}', $data['greeting'] ?? 'Xin chào!', $content);

        // Intent description
        $content = str_replace('{intent_description}', $data['intent_description'] ?? '', $content);

        // Product name (nếu có)
        $productName = $data['product_name'] ?? '';
        if (empty($productName) && !empty($data['products'])) {
            $productName = $data['products'][0]['name'] ?? '';
        }
        $content = str_replace('{product_name}', $productName ?: 'sản phẩm này', $content);

        // Skin types
        $skinTypes = $data['skin_types'] ?? [];
        if (is_string($skinTypes)) {
            $skinTypes = [$skinTypes];
        }
        $skinMap = [
            'oily' => 'da dầu',
            'dry' => 'da khô',
            'combination' => 'da hỗn hợp',
            'sensitive' => 'da nhạy cảm',
            'normal' => 'da thường',
        ];
        $skinLabels = array_map(fn($s) => $skinMap[$s] ?? $s, (array)$skinTypes);
        $content = str_replace('{skin_types}', !empty($skinLabels) ? implode(', ', $skinLabels) : 'chưa xác định', $content);

        // Budget
        $budget = $data['budget'] ?? [];
        $budgetStr = '';
        // QUAN TRỌNG: Nếu min = 0 và có max → hiển thị "dưới X₫"
        // Nếu có min và không có max → hiển thị "từ X₫"
        // Nếu có cả min và max (và min != 0) → hiển thị "X₫ - Y₫"
        if (!empty($budget['max']) && $budget['max'] !== null) {
            $max = number_format($budget['max'], 0, ',', '.');
            // Nếu min = 0 hoặc null → "dưới X₫"
            if (empty($budget['min']) || $budget['min'] === 0 || $budget['min'] === null) {
                $budgetStr = 'dưới ' . $max . '₫';
            } elseif (!empty($budget['min']) && $budget['min'] !== null) {
                // Có cả min và max → "X₫ - Y₫"
                $min = number_format($budget['min'], 0, ',', '.');
                $budgetStr = $min . '₫ - ' . $max . '₫';
            }
        } elseif (isset($budget['min']) && $budget['min'] !== null && $budget['min'] > 0) {
            // Chỉ có min (không có max) → "từ X₫"
            $min = number_format($budget['min'], 0, ',', '.');
            $budgetStr = 'từ ' . $min . '₫';
        }
        $content = str_replace('{budget}', $budgetStr ?: 'chưa xác định', $content);

        // Concerns
        $concerns = $data['concerns'] ?? [];
        if (is_string($concerns)) {
            $concerns = [$concerns];
        }
        $concernMap = [
            'acne' => 'mụn',
            'blackheads' => 'đầu đen',
            'dark_spots' => 'thâm',
            'pores' => 'lỗ chân lông',
            'aging' => 'lão hóa',
            'hydration' => 'dưỡng ẩm',
        ];
        $concernLabels = array_map(fn($c) => $concernMap[$c] ?? $c, (array)$concerns);
        $content = str_replace('{concerns}', !empty($concernLabels) ? implode(', ', $concernLabels) : 'chưa xác định', $content);

        // Products
        $products = $data['products'] ?? [];
        $content = str_replace('{product_count}', count($products), $content);

        // Products list
        $productsList = '';
        if (!empty($products)) {
            $items = [];
            foreach (array_slice($products, 0, 5) as $index => $product) {
                $name = $product['name'] ?? 'Sản phẩm';
                $price = isset($product['price']) ? number_format($product['price'], 0, ',', '.') . '₫' : '';
                $items[] = ($index + 1) . '. **' . $name . '**' . ($price ? ' - ' . $price : '');
            }
            $productsList = implode("\n", $items);
            if (count($products) > 5) {
                $productsList .= "\n... và " . (count($products) - 5) . " sản phẩm khác";
            }
        }
        $content = str_replace('{products_list}', $productsList, $content);

        // Follow-up questions
        $followUps = $data['follow_up_questions'] ?? [];
        $followUpStr = '';
        if (!empty($followUps)) {
            $items = array_map(fn($q, $i) => ($i + 1) . '. ' . $q, $followUps, array_keys($followUps));
            $followUpStr = implode("\n", $items);
        }
        $content = str_replace('{follow_up_questions}', $followUpStr, $content);

        // Benefits (công dụng sản phẩm)
        $benefits = $data['benefits'] ?? '';
        // Nếu không có benefits từ data, thử lấy từ product đầu tiên
        if (empty($benefits) && !empty($data['products'])) {
            $firstProduct = $data['products'][0] ?? [];
            $benefits = $firstProduct['benefits'] ?? $firstProduct['description'] ?? '';
        }
        // Nếu vẫn không có, dùng description
        if (empty($benefits) && !empty($data['products'])) {
            $firstProduct = $data['products'][0] ?? [];
            $benefits = $firstProduct['description'] ?? $firstProduct['short_desc'] ?? '';
        }
        // Format benefits: nếu là string dài, format thành list
        if (!empty($benefits)) {
            // Nếu benefits có dấu xuống dòng hoặc dấu chấm, format thành list
            if (strpos($benefits, "\n") !== false || strpos($benefits, '. ') !== false) {
                $lines = preg_split('/[\n\.]+/', $benefits);
                $lines = array_filter(array_map('trim', $lines), fn($l) => !empty($l) && strlen($l) > 10);
                if (count($lines) > 1) {
                    $benefits = implode("\n", array_map(fn($l) => '- ' . $l, array_slice($lines, 0, 5)));
                }
            }
        }
        $content = str_replace('{benefits}', $benefits ?: 'Đang cập nhật thông tin...', $content);

        return $content;
    }

    /**
     * Process conditional blocks
     */
    private function processConditionalBlocks(string $content, array $data): string
    {
        // Process {if_has_products}...{endif}
        if (preg_match('/\{if_has_products\}(.*?)\{endif\}/s', $content, $matches)) {
            $block = $matches[1];
            if (!empty($data['products'])) {
                $content = str_replace($matches[0], $block, $content);
            } else {
                $content = str_replace($matches[0], '', $content);
            }
        }

        // Process {if_no_products}...{endif}
        if (preg_match('/\{if_no_products\}(.*?)\{endif\}/s', $content, $matches)) {
            $block = $matches[1];
            if (empty($data['products'])) {
                $content = str_replace($matches[0], $block, $content);
            } else {
                $content = str_replace($matches[0], '', $content);
            }
        }

        // Process {if_has_entities}...{endif}
        if (preg_match('/\{if_has_entities\}(.*?)\{endif\}/s', $content, $matches)) {
            $block = $matches[1];
            $hasEntities = !empty($data['skin_types']) || !empty($data['budget']['min']) || !empty($data['budget']['max']) || !empty($data['concerns']);
            if ($hasEntities) {
                $content = str_replace($matches[0], $block, $content);
            } else {
                $content = str_replace($matches[0], '', $content);
            }
        }

        // Process {if_no_products_found}...{endif} - Hiển thị khi có budget filter nhưng không có sản phẩm
        if (preg_match('/\{if_no_products_found\}(.*?)\{endif\}/s', $content, $matches)) {
            $block = $matches[1];
            if (!empty($data['no_products_found'])) {
                $content = str_replace($matches[0], $block, $content);
            } else {
                $content = str_replace($matches[0], '', $content);
            }
        }

        return $content;
    }
}

