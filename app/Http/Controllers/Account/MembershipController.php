<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Loyalty\TierService;
use App\Models\MemberTier;

class MembershipController extends Controller
{
    public function __construct(private TierService $tiers) {}

    public function show()
    {
        $user      = auth()->user();
        $userTier  = $this->tiers->evaluate($user);     // đảm bảo có record user_tiers
        $summary   = $this->tiers->progressSummary($user);
        $tiersList = MemberTier::where('active', 1)->orderBy('min_spend_year')->get();

        return view('account.membership.show', compact('userTier', 'summary', 'tiersList'));
    }
}
