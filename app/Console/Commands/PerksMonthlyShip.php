<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserTier;
use App\Services\Loyalty\PerkIssuer;

class PerksMonthlyShip extends Command
{
    protected $signature = 'perks:monthly-ship';
    protected $description = 'Issue monthly free-shipping vouchers for users by tier quota';

    public function handle(PerkIssuer $perks): int
    {
        $monthKey = now()->format('Y-m');
        $this->info("Issuing monthly ship vouchers for $monthKey ...");

        UserTier::with('tier', 'user')->chunkById(500, function ($rows) use ($perks) {
            foreach ($rows as $ut) {
                if (!$ut->tier) continue;
                $perks->issueMonthlyShip($ut->user, (int)$ut->tier->monthly_ship_quota);
            }
        });

        $this->info('Done.');
        return 0;
    }
}
