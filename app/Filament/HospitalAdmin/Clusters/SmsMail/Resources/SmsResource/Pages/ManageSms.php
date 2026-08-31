<?php

namespace App\Filament\HospitalAdmin\Clusters\SmsMail\Resources\SmsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\SmsMail\Resources\SmsResource;
use App\Models\Subscription;
use App\Repositories\SmsRepository;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageSms extends ManageRecords
{
    protected static string $resource = SmsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->action(function (array $data) {
                $input = $data;
                if (getLoggedInUser()->hasRole('Admin') && ! $input['number_directly']) {
                    $smsCount = Subscription::whereUserId(getLoggedInUserId())->whereStatus(1)->value('sms_limit');

                    if (! isset($input['phone'])) {
                        $smsCount = $smsCount - (count($input['send_to']));
                        if ($smsCount < 0) {
                            return Notification::make()
                                ->title(__('messages.flash.sms_limit_over'))
                                ->danger()
                                ->send();
                        }
                    } else {
                        if ($smsCount <= 0) {
                            return Notification::make()
                                ->title(__('messages.flash.sms_limit_over'))
                                ->danger()
                                ->send();
                        }
                    }
                }

                $requests = $input;
                app(SmsRepository::class)->store($requests, $this);

                return Notification::make()
                    ->title(__('messages.flash.sms_send'))
                    ->success()
                    ->send();
            }),
        ];
    }
}
