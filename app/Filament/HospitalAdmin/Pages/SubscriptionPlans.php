<?php

namespace App\Filament\HospitalAdmin\Pages;

use Filament\Pages\Page;
use App\Models\Notification;
use App\Repositories\SubscriptionPlanRepository;

class SubscriptionPlans extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.hospital-admin.pages.subscription-plans';

    protected function getViewData(): array
    {
        $subscriptionPlanRepository = app(SubscriptionPlanRepository::class);
        $subscriptionPlanRepository->saveNotifications();

        $latestNotification = Notification::where('user_id', getLoggedInUserId())
            ->where('type', Notification::NOTIFICATION_TYPE['Subscription Plan'])
            ->latest()
            ->first();

        $data = app(SubscriptionPlanRepository::class)->getSubscriptionPlansData();
        $data['latestNotification'] = $latestNotification ? ($latestNotification->read_at ? null : $latestNotification) : null;

        return $data;
    }
}
