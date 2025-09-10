<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PointsService;

class PointsConfirmDue extends Command
{
    protected $signature = 'points:confirm-due';
    protected $description = 'Confirm pending point transactions that are due';

    public function handle(): int
    {
        PointsService::confirmDue();
        $this->info('Confirmed due point transactions.');
        return self::SUCCESS;
    }
}
