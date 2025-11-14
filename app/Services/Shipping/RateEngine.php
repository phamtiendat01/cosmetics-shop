<?php

namespace App\Services\Shipping;

use App\Models\ShippingCarrier;
use App\Models\ShippingZone;
use App\Models\ShippingRate;
use Illuminate\Support\Str;

class RateEngine
{
    protected array $cartItems = [];
    protected ?string $province = null;
    protected int $subtotal = 0;

    public static function make(): self
    {
        return new self();
    }

    public function forCart(array $items, int $subtotal): self
    {
        $this->cartItems = $items;
        $this->subtotal = max(0, (int)$subtotal);
        return $this;
    }

    public function toProvince(?string $provinceNameOrCode): self
    {
        $this->province = $provinceNameOrCode;
        return $this;
    }

    protected function totalWeightKg(): float
    {
        // items: [['variant'=>['weight_grams'=>...], 'qty'=>...], ...] — tùy cấu trúc giỏ của bạn
        $grams = 0;
        foreach ($this->cartItems as $it) {
            $w = (int)($it['variant']['weight_grams'] ?? 0);
            $q = (int)($it['qty'] ?? 1);
            $grams += $w * $q;
        }
        return round($grams / 1000, 3);
    }

    protected function findZoneId(): ?int
    {
        if (!$this->province) return null;

        $needle = Str::of($this->province)->lower()->ascii()->value();

        $zones = ShippingZone::query()->where('enabled', 1)->get();
        foreach ($zones as $z) {
            $arr = (array)($z->province_codes ?? []);
            foreach ($arr as $code) {
                $codeN = Str::of((string)$code)->lower()->ascii()->value();
                if ($codeN === $needle) return $z->id;
            }
        }
        return null;
    }

    public function quotes(): array
    {
        $weightKg = $this->totalWeightKg();
        $kg = max(1, (int)ceil($weightKg));
        $zoneId = $this->findZoneId();

        $carriers = ShippingCarrier::where('enabled', 1)->orderBy('sort_order')->get();
        $out = [];

        foreach ($carriers as $carrier) {
            $rates = ShippingRate::query()
                ->where('enabled', 1)
                ->where('carrier_id', $carrier->id)
                ->where(function ($q) use ($zoneId) {
                    $q->whereNull('zone_id');
                    if ($zoneId) $q->orWhere('zone_id', $zoneId);
                })
                ->get()
                ->filter(function ($r) use ($kg) {
                    $okW = (is_null($r->min_weight) || $kg >= $r->min_weight)
                        && (is_null($r->max_weight) || $kg <= $r->max_weight);
                    $okT = (is_null($r->min_total) || $this->subtotal >= $r->min_total)
                        && (is_null($r->max_total) || $this->subtotal <= $r->max_total);
                    return $okW && $okT;
                });

            foreach ($rates as $r) {
                $extra = max(0, $kg - 1);
                $fee = (int)$r->base_fee + $extra * (int)($r->per_kg_fee ?? 0);
                $out[] = [
                    'carrier_id'   => $carrier->id,
                    'carrier_name' => $carrier->name,
                    'carrier_code' => $carrier->code,
                    'rate_id'      => $r->id,
                    'rate_name'    => $r->name,
                    'fee'          => $fee,
                    'etd'          => $r->etd_min_days && $r->etd_max_days
                        ? "{$r->etd_min_days}-{$r->etd_max_days} ngày"
                        : null,
                ];
            }
        }

        usort($out, fn($a, $b) => $a['fee'] <=> $b['fee']);
        return $out;
    }

    public function best(): ?array
    {
        $all = $this->quotes();
        return $all[0] ?? null;
    }
}
