<?php

namespace App\Filament\HospitalAdmin\Clusters\LiveConsultations\Resources\LiveMeetingsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\LiveConsultations\Resources\LiveMeetingsResource;
use App\Models\LiveMeeting;
use App\Repositories\LiveMeetingRepository;
use Exception;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageLiveMeetings extends ManageRecords
{
    protected static string $resource = LiveMeetingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('messages.live_consultation.new_live_meeting'))->modalWidth('3xl')->createAnother(false)->modalHeading(__('messages.live_consultation.new_live_meeting'))->successNotificationTitle(__('messages.flash.live_meeting_saved'))
                ->action(function (array $data) {
                    if (count($data['staff_list']) > 10) {
                        Notification::make()->danger()->title(__('messages.new_change.staff_limit'))->send();

                        return;
                    }

                    try {
                        app(LiveMeetingRepository::class)->store($data);
                        app(LiveMeetingRepository::class)->createNotification($data);

                        Notification::make()->success()->title(__('messages.flash.live_meeting_saved'))->send();
                    } catch (Exception $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();
                    }
                }),
        ];
    }

    public function changeStatus($status, LiveMeeting $liveMeeting)
    {
        try {
            $liveMeeting->update([
                'status' => $status,
            ]);

            return Notification::make()->success()->title(__('messages.common.status_updated_successfully'))->send();
        } catch (Exception $e) {
            return Notification::make()->danger()->title($e->getMessage())->send();
        }
    }
}
