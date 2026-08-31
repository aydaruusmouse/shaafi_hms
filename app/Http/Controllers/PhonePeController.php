<?php

namespace App\Http\Controllers;

use App\Repositories\SubscriptionRepository;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PhonePeController extends Controller
{
    public function subscriptionPhonePePaymentSuccess(Request $request)
    {
        $subscription = app(SubscriptionRepository::class)->phonePePaymentSuccess($request->all());

        if ($subscription instanceof Model) {
            setPlanFeatures();
            Notification::make()
                ->success()
                ->title($subscription->subscriptionPlan->name.' '.__('messages.subscription_pricing_plans.has_been_subscribed'))
                ->send();
        } else {
            Notification::make()
                ->danger()
                ->title($subscription)
                ->send();
        }

        if (session('from_pricing') == 'landing.home') {
            return redirect(route('landing-home'));
        } elseif (session('from_pricing') == 'landing.about.us') {
            return redirect(route('landing.about.us'));
        } elseif (session('from_pricing') == 'landing.services') {
            return redirect(route('landing.services'));
        } elseif (session('from_pricing') == 'landing.pricing') {
            return redirect(route('landing.pricing'));
        } else {
            return redirect(route('subscription.pricing.plans.index'));
        }
    }
}
