<?php

namespace App\Repositories;

use Arr;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\SuperAdminSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SubscriptionPlanRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'price',
        'valid_until',
    ];

    /**
     * Return searchable fields
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return SubscriptionPlan::class;
    }

    /**
     * @return mixed
     */
    public function store($input)
    {
        $subscriptionPlan = null;
        $input['trial_days'] = $input['trial_days'] != null ? $input['trial_days'] : 0;
        $input['sms_limit'] = $input['sms_limit'] != null ? $input['sms_limit'] : 0;
        $input['price'] = removeCommaFromNumbers($input['price']);
        /** @var SubscriptionPlan $subscriptionPlan */
        $subscriptionPlan = SubscriptionPlan::create(Arr::except($input, ['plan_feature']));

        if (isset($input['plan_feature'])) {
            $subscriptionPlan->features()->sync($input['plan_feature']);
        }

        return $subscriptionPlan;
    }

    /**
     * @return Builder|Builder[]|Collection|Model
     */
    public function update($input, $id)
    {
        $subscriptionPlan = SubscriptionPlan::findOrFail($id);
        $input['trial_days'] = $input['trial_days'] != null ? $input['trial_days'] : 0;
        $input['sms_limit'] = $input['sms_limit'] != null ? $input['sms_limit'] : 0;
        $input['price'] = removeCommaFromNumbers($input['price']);
        $subscriptionPlan->update($input);

        $subscriptionPlan->features()->sync(isset($input['plan_feature']) ? $input['plan_feature'] : []);

        return $subscriptionPlan;
    }

    public function saveNotifications()
    {
        $adminUser = User::where('id', getLoggedInUserId())->first();
        $superAdminSetting = SuperAdminSetting::where('key', 'plan_expire_notification')->first();
        $planExpireNotification = $superAdminSetting->value;
        $currentSubscription = currentActiveSubscription();
        $endAt = $currentSubscription->ends_at;
        $now = Carbon::now();
        $remainingDays = round(abs($endAt->diffInDays($now)));
        $title = 'Your subscription plan will expire in ' . $remainingDays . ' days';
        $allUser = Notification::NOTIFICATION_FOR[Notification::ADMIN];
        if ($remainingDays == $planExpireNotification) {
            addNotification([
                Notification::NOTIFICATION_TYPE['Subscription Plan'],
                $adminUser->id,
                $allUser,
                $title,
            ]);
        }
    }

    public function getSubscriptionPlansData(): array
    {
        $data = null;

        $plans = SubscriptionPlan::with([
            'plan',
            'plans',
            'planFeatures.feature',
            'subscription',
            'hasZeroPlan',
        ])->get();

        $data['subscriptionPricingMonthPlans'] = [];
        $data['subscriptionPricingYearPlans'] = [];

        if (! empty($plans)) {
            foreach ($plans as $plan) {
                if ($plan->frequency == SubscriptionPlan::MONTH) {
                    $data['subscriptionPricingMonthPlans'][] = $plan;
                } elseif ($plan->frequency == SubscriptionPlan::YEAR) {
                    $data['subscriptionPricingYearPlans'][] = $plan;
                }
            }
        }

        $data['currentActivePlan'] = Subscription::with('subscriptionPlan')->where('user_id', auth()->id())->where('status', SubscriptionStatus::ACTIVE->value)->first();

        $data['plans'] = SubscriptionPlan::all()->groupBy('frequency')->map(function ($plans) {
            return $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'short_description' => $plan->short_description,
                    'price' => $plan->price,
                    'currency' => $plan->currency,
                    'trial_days' => $plan->trial_days,
                    'listing_interval' => $plan->listing_interval,
                    'other_images_per_listing' => $plan->other_images_per_listing,
                    'mark_as_recommended' => $plan->mark_as_recommended,
                    'assign_while_register' => $plan->mark_as_recommended,
                ];
            });
        });

        //        $data['subscriptionPricingMonthPlans'] = SubscriptionPlan::with([
        //            'plan', 'plans', 'planFeatures.feature', 'subscription', 'hasZeroPlan',
        //        ])
        //            ->where('frequency', '=', SubscriptionPlan::MONTH)
        //            ->get();
        //        $data['subscriptionPricingYearPlans'] = SubscriptionPlan::with([
        //            'plan', 'plans', 'planFeatures.feature', 'subscription', 'hasZeroPlan',
        //        ])
        //            ->where('frequency', '=', SubscriptionPlan::YEAR)
        //            ->get();
        //

        return $data;
    }
}
