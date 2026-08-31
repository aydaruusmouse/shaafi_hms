<?php

namespace App\Http\Middleware;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->hasRole('Super Admin') || ! Auth::check()) {
            return $next($request);
        }

        if (Auth::check() && ! Auth::user()->hasRole('Admin') || ! Auth::check()) {
            return $next($request);
        }

        $allowedRoutes = [
            'filament.user.pages.manage-subscription',
            'filament.hospitalAdmin.pages.subscription-plans',
            'filament.hospitalAdmin.pages.choose-payment-type',
            'filament.hospitalAdmin.auth.profile',
            'filament.hospitalAdmin.auth.logout',
        ];

        if (in_array(Route::currentRouteName(), $allowedRoutes)) {
            return $next($request);
        }

        $subscription = Subscription::with('subscriptionPlan')->where('status', SubscriptionStatus::ACTIVE)
            ->where('user_id', Auth::id())
            ->first();

        if (! $subscription) {
            return redirect()->route('filament.hospitalAdmin.pages.subscription-plans');
        }

        if ($subscription->isExpired()) {
            return redirect()->route('filament.hospitalAdmin.pages.subscription-plans');
        }

        return $next($request);
    }
}
