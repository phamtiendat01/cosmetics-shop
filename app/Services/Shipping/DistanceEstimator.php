<?php

namespace App\Services\Shipping;

class DistanceEstimator
{
    // km theo công thức Haversine
    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth * $c;
    }

    public static function estimateFee(?float $lat, ?float $lng, int $cartSubTotal = 0): array
    {
        $shopLat = (float) config('shipping.shop_lat');
        $shopLng = (float) config('shipping.shop_lng');
        $factor  = (float) config('shipping.road_factor', 1.2);
        $tiers   = config('shipping.tiers', []);
        $freeAt  = (int)   config('shipping.free_threshold_amount', 0);

        if ($lat === null || $lng === null) {
            return ['km' => null, 'fee' => null, 'reason' => 'missing_geo'];
        }

        $km = self::haversineKm($shopLat, $shopLng, $lat, $lng) * $factor;

        // free-ship theo giá trị đơn
        if ($cartSubTotal >= $freeAt && $freeAt > 0) {
            return ['km' => $km, 'fee' => 0, 'reason' => 'free_threshold'];
        }

        $fee = 0;
        foreach ($tiers as $t) {
            if ($km <= $t['max_km']) {
                $fee = (int) $t['fee'];
                break;
            }
        }

        return ['km' => $km, 'fee' => $fee, 'reason' => 'tier'];
    }
}
