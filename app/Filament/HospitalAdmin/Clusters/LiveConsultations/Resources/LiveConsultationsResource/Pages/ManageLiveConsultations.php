<?php

namespace App\Filament\HospitalAdmin\Clusters\LiveConsultations\Resources\LiveConsultationsResource\Pages;

use App\Filament\hospitalAdmin\Clusters\LiveConsultations\Resources\LiveConsultationsResource;
use App\Http\Controllers\GoogleMeetCalendarController;
use App\Models\LiveConsultation;
use App\Models\UserZoomCredential;
use App\Repositories\LiveConsultationRepository;
use Exception;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

// use Filament\Tables\Actions\Action;

class ManageLiveConsultations extends ManageRecords
{
    protected static string $resource = LiveConsultationsResource::class;

    protected function getHeaderActions(): array
    {
        if (Auth::user()->hasRole('Patient')) {
            return [];
        }
        $existingData = UserZoomCredential::where('user_id', getLoggedInUserId())->first();

        return [
            ActionGroup::make([
                Actions\CreateAction::make()
                    ->label(__('messages.live_consultation.new_live_consultation'))
                    ->modalWidth('5xl')
                    ->groupedIcon('')
                    ->createAnother(false)
                    ->modalFooterActionsAlignment('end')
                    ->action(function (array $data) {
                        try {
                            DB::beginTransaction();
                            if ($data['platform_type'] == LiveConsultation::GOOGLE_MEET) {
                                /** @var GoogleMeetCalendarController $getAccessToken */
                                $getAccessToken = App::make(GoogleMeetCalendarController::class);
                                $getAccessToken->getAccessToken(getLoggedInUserId());

                                app(LiveConsultationRepository::class)->googleMeetStore($data);
                            } else {
                                app(LiveConsultationRepository::class)->store($data);
                            }
                            app(LiveConsultationRepository::class)->createNotification($data);

                            DB::commit();

                            Notification::make()
                                ->title(__('messages.flash.live_consultation_saved'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            DB::rollBack();

                            $responseData = json_decode($e->getMessage(), true);

                            if (isset($responseData['error'])) {
                                $errorCode = $responseData['error']['code'];

                                if ($errorCode == 401) {
                                    Notification::make()->danger()->title(__('messages.google_meet.disconnect_or_reconnect'))->send();
                                }
                            }

                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                Action::make('add_credential')
                    ->label(__('messages.live_consultation.add_credential'))
                    ->modalWidth('md')
                    ->form([
                        TextInput::make('zoom_api_key')
                            ->label(__('messages.live_consultation.zoom_api_key'))
                            ->required()
                            ->placeholder(__('messages.live_consultation.zoom_api_key'))
                            ->autocomplete('off')
                            ->columnSpan('full')
                            ->default($existingData['zoom_api_key'] ?? ''),
                        TextInput::make('zoom_api_secret')
                            ->label(__('messages.live_consultation.zoom_api_secret'))
                            ->required()
                            ->placeholder(__('messages.live_consultation.zoom_api_secret'))
                            ->autocomplete('off')
                            ->columnSpan('full')
                            ->default($existingData['zoom_api_secret'] ?? ''),
                        Placeholder::make('documentation')
                            ->label('')
                            ->content(new HtmlString('<a href="https://developers.zoom.us/docs/integrations/create" target="_blank"> '.__('messages.live_consultation.how_to_generate_Oauth_credentials').' ? </a>')),
                    ])
                    ->action(function (array $data) {
                        try {
                            app(LiveConsultationRepository::class)->createUserZoom($data);

                            Notification::make()->success()->title(__('messages.flash.user_zoom_credential_saved'))->send();
                        } catch (Exception $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ])->button()
                ->icon('heroicon-o-chevron-down')
                ->iconPosition('after')
                ->extraAttributes([
                    'class' => 'mx-3',
                ]),

            Action::make('connect_with_zoom')
                ->label(__('messages.medicine_bills.connect_with_zoom'))
                ->color('success')
                ->visible(isZoomTokenExpire())
                ->action(function () {
                    $userZoomCredential = UserZoomCredential::where('user_id', getLoggedInUserId())->first();
                    if ($userZoomCredential == null) {
                        app()->setLocale(getLoggedInUser()->language);

                        return Notification::make()->danger()->title(__('messages.new_change.add_credential'))->send();
                    }
                    $clientID = $userZoomCredential->zoom_api_key;
                    $callbackURL = config('app.zoom_callback');
                    $url = "https://zoom.us/oauth/authorize?client_id=$clientID&response_type=code&redirect_uri=$callbackURL";

                    return redirect($url);
                }),
        ];
    }

    public function changeStatus($status, LiveConsultation $liveConsultation)
    {
        try {
            $liveConsultation->update([
                'status' => $status,
            ]);

            return Notification::make()->success()->title(__('messages.common.status_updated_successfully'))->send();
        } catch (Exception $e) {
            return Notification::make()->danger()->title($e->getMessage())->send();
        }
    }
}
