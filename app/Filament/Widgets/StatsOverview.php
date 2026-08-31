<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected static string $view = 'filament.widgets.sadmin-dashboard-state';

    protected function getTotalHospitals(): int
    {
        return User::where('department_id', User::USER_ADMIN)
            ->whereNotNull(['hospital_name', 'username'])
            ->count();
    }

    protected function getRevenue(): int
    {
        $revenue = Transaction::where('status', '=', Transaction::APPROVED)->sum('amount');

        return $revenue;
    }

    public function getTotalActiveDeActiveHospitalPlans(): array
    {
        $activePlansCount = 0;
        $deActivePlansCount = 0;
        $subscriptions = Subscription::whereStatus(Subscription::ACTIVE)->get();
        foreach ($subscriptions as $sub) {
            if (! $sub->isExpired()) { // active plans
                $activePlansCount++;
            } else {
                $deActivePlansCount++;
            }
        }

        return ['activePlansCount' => $activePlansCount, 'deActivePlansCount' => $deActivePlansCount];
    }

    protected function getViewData(): array
    {

        return [
            'totalHospitals' => $this->getTotalHospitals(),
            'totalRevenue' => formatCurrency($this->getRevenue()),
            'totalActivePlan' => $this->getTotalActiveDeActiveHospitalPlans()['activePlansCount'],
            'totalExpiredPlan' => $this->getTotalActiveDeActiveHospitalPlans()['deActivePlansCount'],
        ];
    }
}
