<?php

namespace App\Filament\HospitalAdmin\Clusters\LiveConsultations\Pages;

use App\Filament\HospitalAdmin\Clusters\LiveConsultations;
use App\Http\Controllers\GoogleMeetCalendarController;
use App\Models\Doctor;
use App\Models\EventGoogleCalendar;
use App\Models\GoogleCalendarIntegration;
use App\Models\GoogleCalendarList;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Google_Service_Calendar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ConnectGoogleCalender extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static string $view = 'filament.hospital-admin.clusters.live-consultations.pages.connect-google-calender';

    protected static ?string $cluster = LiveConsultations::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public bool $integrationExists = false;

    public $googleCalendarLists;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Doctor'])) {
            return true;
        }

        return false;
    }

    public function mount(): void
    {
        if (! auth()->user()->hasRole(['Doctor'])) {
            abort(404);
        }

        $this->integrationExists = GoogleCalendarIntegration::where('user_id', getLoggedInUserId())->exists();
        $this->googleCalendarLists = GoogleCalendarList::with('eventGoogleCalendar')->where('user_id', getLoggedInUserId())->get();
        $this->form->fill([]);
    }

    public function form(Form $form): Form
    {
        if ($this->integrationExists) {
            return $form
                ->schema(function () {
                    $checkboxList = [];
                    if (! empty($this->googleCalendarLists)) {
                        foreach ($this->googleCalendarLists as $key => $googleCalendarList) {
                            $checkboxList[] = Checkbox::make('google_calendar.'.$googleCalendarList->id)
                                ->label($googleCalendarList->calendar_name)
                                ->afterStateHydrated(function (Checkbox $component) use ($googleCalendarList) {
                                    $value = EventGoogleCalendar::where('google_calendar_list_id', $googleCalendarList->id)->exists();
                                    $component->state($value);
                                });
                        }
                    }

                    return $checkboxList;
                })
                ->statePath('data');
        } else {
            return $form
                ->model(Doctor::whereUserId(Auth::id())->first())
                ->schema([
                    Actions::make([
                        FormAction::make('connect_google_calender')
                            ->action(function ($record) {
                                $googleMeetCalendarController = App::make(GoogleMeetCalendarController::class);
                                $googleMeetCalendarController->googleConfig();

                                $name = getGoogleJsonFilePath();

                                $jsonFilePath = storage_path($name);

                                if (! file_exists($jsonFilePath)) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('messages.google_meet.upload_again_json_file'))
                                        ->send();

                                    return;
                                }

                                if (empty($name) && (file_exists(storage_path()))) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('messages.google_meet.validate_json_file'))
                                        ->send();

                                    return;
                                }

                                $authUrl = $googleMeetCalendarController->client->createAuthUrl();
                                $filteredUrl = filter_var($authUrl, FILTER_SANITIZE_URL);

                                return redirect($filteredUrl);
                            }),
                    ])->columnSpanFull(),
                    SpatieMediaLibraryFileUpload::make('json_file')
                        ->label(__('messages.google_meet.google_json_file').':')
                        ->required()
                        ->collection(Doctor::GOOGLE_JSON_FILE_PATH)
                        ->disk('google_json_file')
                        ->rules(['file', 'mimes:json'])
                        ->validationMessages([
                            'required' => __('messages.google_meet.upload_json_file'),
                            'file' => __('messages.google_meet.upload_file'),
                            'mimes' => __('messages.google_meet.invalid_json_format'),
                        ])
                        ->extraInputAttributes([
                            'accept' => 'application/json',
                        ])
                        ->saveUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
                            if (! method_exists($record, 'addMediaFromString')) {
                                return $file;
                            }

                            try {
                                if (! $file->exists()) {
                                    return null;
                                }
                            } catch (UnableToCheckFileExistence $exception) {
                                return null;
                            }

                            /** @var FileAdder $mediaAdder */
                            $mediaAdder = $record->addMediaFromString($file->get());

                            $filename = $component->getUploadedFileNameForStorage($file);

                            $media = $mediaAdder
                                ->addCustomHeaders($component->getCustomHeaders())
                                ->usingFileName($filename)
                                ->usingName($component->getMediaName($file) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                                ->storingConversionsOnDisk($component->getConversionsDisk() ?? '')
                                ->withCustomProperties($component->getCustomProperties())
                                ->withManipulations($component->getManipulations())
                                ->withResponsiveImagesIf($component->hasResponsiveImages())
                                ->withProperties($component->getProperties())
                                ->toMediaCollection($component->getCollection() ?? 'default', $component->getDiskName());

                            $googleJsonFilePath = str_replace(env('APP_URL').'/uploads', 'google_json_files', $media->getUrl());
                            $record->update(['google_json_file_path' => $googleJsonFilePath]);

                            return $media->getAttributeValue('uuid');
                        }),
                ])
                ->columns(3)
                ->statePath('data');
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
            Action::make('sync_google_calender')
                ->visible(fn (): bool => $this->integrationExists)
                ->label(__('messages.google_meet.sync_google_calendar'))
                ->action('syncGoogleCalendar'),
            Action::make('disconnect_google_calender')
                ->visible(fn (): bool => $this->integrationExists)
                ->label(__('messages.google_meet.disconnect_google_calendar'))
                ->color('danger')
                ->action('disconnectGoogleCalendar'),
        ];
    }

    public function save()
    {
        try {
            $data = $this->form->getState();
            if ($this->integrationExists) {
                $googleCalendarIds = $data['google_calendar'] ?? [];
                $allFalse = ! array_filter($googleCalendarIds);
                if ($allFalse) {
                    Notification::make()
                        ->danger()
                        ->title(__('js.select_calendar'))
                        ->send();

                    return;
                }

                $eventGoogleCalendars = EventGoogleCalendar::where('user_id', getLoggedInUserId())->get();

                if ($eventGoogleCalendars) {
                    foreach ($eventGoogleCalendars as $eventGoogleCalendar) {
                        $eventGoogleCalendar->delete();
                    }
                }

                foreach ($googleCalendarIds as $id => $value) {
                    if ($value == false) {
                        continue;
                    }
                    $googleCalendarListId = GoogleCalendarList::find($id)->google_calendar_id;
                    $data = [
                        'user_id' => getLoggedInUserId(),
                        'google_calendar_list_id' => $id,
                        'google_calendar_id' => $googleCalendarListId,
                    ];

                    EventGoogleCalendar::create($data);
                }

                Notification::make()
                    ->success()
                    ->title(__('messages.google_meet.google_calendar_add'))
                    ->send();

                return redirect(route('filament.hospitalAdmin.live-consultations.pages.connect-google-calender'));
            } else {
                Notification::make()
                    ->success()
                    ->title(__('messages.google_meet.json_file_saved_successfully'))
                    ->send();
            }
        } catch (Exception $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();

            return;
        }
    }

    public function syncGoogleCalendar()
    {
        $googleMeetCalendarController = App::make(GoogleMeetCalendarController::class);
        $googleMeetCalendarController->syncGoogleCalendarList(getLoggedInUserId());

        $gcHelper = new Google_Service_Calendar($googleMeetCalendarController->client);
        $calendarList = $gcHelper->calendarList->listCalendarList();

        $googleCalendarList = [];

        $existingCalendars = GoogleCalendarList::where('user_id', getLoggedInUserId())
            ->pluck('google_calendar_id', 'google_calendar_id')
            ->toArray();

        foreach ($calendarList->getItems() as $calendarListEntry) {
            if ($calendarListEntry->accessRole == 'owner') {
                $exists = GoogleCalendarList::where('user_id', getLoggedInUserId())
                    ->where('google_calendar_id', $calendarListEntry['id'])
                    ->first();
                unset($existingCalendars[$calendarListEntry['id']]);

                if (! $exists) {
                    $googleCalendarList[] = GoogleCalendarList::create([
                        'user_id' => getLoggedInUserId(),
                        'calendar_name' => $calendarListEntry['summary'],
                        'google_calendar_id' => $calendarListEntry['id'],
                        'meta' => json_encode($calendarListEntry),
                    ]);
                }
            }
        }

        EventGoogleCalendar::whereIn('google_calendar_id', $existingCalendars)->delete();
        GoogleCalendarList::whereIn('google_calendar_id', $existingCalendars)->delete();

        Notification::make()
            ->success()
            ->title(__('messages.google_meet.google_calendar_update'))
            ->send();

        return redirect(route('filament.hospitalAdmin.live-consultations.pages.connect-google-calender'));
    }

    public function disconnectGoogleCalendar()
    {
        EventGoogleCalendar::where('user_id', getLoggedInUserId())->delete();
        GoogleCalendarIntegration::where('user_id', getLoggedInUserId())->delete();
        GoogleCalendarList::where('user_id', getLoggedInUserId())->delete();

        Notification::make()
            ->success()
            ->title(__('messages.google_meet.google_calendar_disconnect'))
            ->send();

        return redirect(route('filament.hospitalAdmin.live-consultations.pages.connect-google-calender'));
    }
}
