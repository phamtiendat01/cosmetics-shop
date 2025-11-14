<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\Loyalty\TierService;

class TiersReevaluate extends Command
{
    protected $signature = 'tiers:reevaluate {--user_id=}';
    protected $description = 'Re-evaluate user tiers based on current-year spend';

    public function handle(TierService $tiers): int
    {
        $q = User::query();
        if ($id = $this->option('user_id')) $q->where('id', $id);

        $bar = $this->output->createProgressBar($q->count());
        $bar->start();

        $q->chunkById(500, function ($users) use ($tiers, $bar) {
            foreach ($users as $u) {
                $tiers->evaluate($u);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Done.');
        return 0;
    }
}
