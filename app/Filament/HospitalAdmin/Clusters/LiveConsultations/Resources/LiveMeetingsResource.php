<?php

namespace App\Filament\HospitalAdmin\Clusters\LiveConsultations\Resources;

use App\Filament\HospitalAdmin\Clusters\LiveConsultations;
use App\Filament\HospitalAdmin\Clusters\LiveConsultations\Resources\LiveMeetingsResource\Pages;
use App\Models\LiveConsultation;
use App\Models\LiveMeeting;
use App\Repositories\LiveMeetingRepository;
use App\Repositories\ZoomRepository;
use Carbon\Carbon;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Group as ComponentsGroup;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class LiveMeetingsResource extends Resource
{
    protected static ?string $model = LiveMeeting::class;

    protected static ?string $cluster = LiveConsultations::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Live Meetings')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Live Meetings')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.live_meetings');
    }

    public static function getLabel(): string
    {
        return __('messages.live_meetings');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist']) && getModuleAccess('Live Meetings')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist']) && getModuleAccess('Live Meetings')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist']) && getModuleAccess('Live Meetings')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Accountant', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('consultation_title')
                    ->label(__('messages.live_consultation.consultation_title').':')
                    ->placeholder(__('messages.live_consultation.consultation_title'))
                    ->required()
                    ->validationAttribute(__('messages.live_consultation.consultation_title'))
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('consultation_date')
                    ->label(__('messages.live_consultation.consultation_date').':')
                    ->placeholder(__('messages.live_consultation.consultation_date'))
                    ->minDate(now())
                    ->default(Carbon::now())
                    ->native(false)
                    ->required()
                    ->validationAttribute(__('messages.live_consultation.consultation_date'))
                    ->columns(6),

                Forms\Components\TextInput::make('consultation_duration_minutes')
                    ->label(__('messages.live_consultation.consultation_duration_minutes').':')
                    ->placeholder(__('messages.live_consultation.consultation_duration_minutes'))
                    ->numeric()
                    ->required()
                    ->validationAttribute(__('messages.live_consultation.consultation_duration_minutes'))
                    ->minValue(0)
                    ->maxValue(720)
                    ->columns(6),

                Forms\Components\Select::make('staff_list')
                    ->label(__('messages.live_consultation.staff_list').':')
                    ->multiple()
                    ->afterStateHydrated(function (Select $component, $record) {
                        if ($record) {
                            $value = $record->members ? $record->members->pluck('id')->toArray() : null;
                            $component->state($value);
                        } else {
                            $component->state(null);
                        }
                    })
                    ->options(app(LiveMeetingRepository::class)->getUsers())
                    ->default(getLoggedInUserId())
                    ->required()
                    ->columnSpanFull()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.live_consultation.staff_list').' '.__('messages.fields.required'),
                    ]),

                Forms\Components\Radio::make('host_video')
                    ->label(__('messages.live_consultation.host_video').':')
                    ->required()
                    ->validationAttribute(__('messages.live_consultation.host_video'))
                    ->options([
                        LiveConsultation::HOST_ENABLE => __('messages.live_consultation.enable'),
                        LiveConsultation::HOST_DISABLED => __('messages.live_consultation.disabled'),
                    ])
                    ->default(LiveConsultation::HOST_DISABLED)
                    ->columns(2),

                Forms\Components\Radio::make('participant_video')
                    ->label(__('messages.live_consultation.client_video').':')
                    ->required()
                    ->validationAttribute(__('messages.live_consultation.client_video'))
                    ->options([
                        LiveConsultation::CLIENT_ENABLE => __('messages.live_consultation.enable'),
                        LiveConsultation::CLIENT_DISABLED => __('messages.live_consultation.disabled'),
                    ])
                    ->default(LiveConsultation::HOST_DISABLED)
                    ->columns(2),

                Forms\Components\Textarea::make('description')
                    ->label(__('messages.testimonial.description').':')
                    ->placeholder(__('messages.testimonial.description'))
                    ->rows(3)
                    ->columnSpanFull(),
                Hidden::make('created_by')
                    ->default(getLoggedInUserId()),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Doctor', 'Accountant']) && ! getModuleAccess('Live Meetings')) {
            abort(404);
        } elseif (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse']) && ! getModuleAccess('Live Meetings')) {
            abort(404);
        }

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $tenantId = Auth::user()->tenant_id;

                $query->whereHas('user', function (Builder $query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                });

                $roles = [
                    'Receptionist',
                    'Doctor',
                    'Nurse',
                    'Accountant',
                    'Lab Technician',
                    'Pharmacist',
                    'Case Manager',
                ];

                if (getLoggedInUser()->hasAnyRole($roles)) {
                    $query->whereHas('members', function (Builder $query) {
                        $query->where('user_id', '=', getLoggedInUserId());
                    });
                }

                return $query;
            })
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('consultation_title')
                    ->label(__('messages.live_consultation.consultation_title'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('consultation_date')
                    ->label(__('messages.investigation_report.date'))
                    ->sortable()
                    ->alignCenter()
                    ->date('h:i A, jS M, Y')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label(__('messages.live_consultation.created_by'))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name']),
                Tables\Columns\ViewColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->sortable()
                    ->alignCenter()
                    ->view('filament.hospital-admin.clusters.live-consultations.columns.status'),
                Tables\Columns\TextColumn::make('password')
                    ->label(__('messages.user.password'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        '' => __('messages.filter.all'),
                        '0' => __('messages.live_consultation_filter.awaited'),
                        '1' => __('messages.live_consultation_filter.cancelled'),
                        '2' => __('messages.live_consultation_filter.finished'),
                    ])
                    ->native(false),
            ])
            ->recordAction(null)
            ->actions([
                Action::make('start_consultation_zoom')
                    ->visible(function ($record) {
                        $today = Carbon::now()->format('Y-m-d h:i A');
                        $meetingTime = Carbon::parse($record->consultation_date)->format('Y-m-d h:i A');

                        return $record->status == 0 && $meetingTime > $today;
                    })
                    ->icon('heroicon-s-video-camera')
                    ->iconButton()
                    ->modalHeading(function ($record) {
                        try {
                            /** @var ZoomRepository $zoomRepo */
                            $zoomRepo = App::make(ZoomRepository::class, ['createdBy' => $record->created_by]);
                            $zoomLiveData = $zoomRepo->zoomGet($record->meeting_id);

                            return $record->consultation_title;
                        } catch (\Exception $e) {
                            return false;
                        }
                    })
                    ->modalWidth('lg')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->infolist(function ($record) {
                        try {
                            /** @var ZoomRepository $zoomRepo */
                            $zoomRepo = App::make(ZoomRepository::class, ['createdBy' => $record->created_by]);
                            $zoomLiveData = $zoomRepo->zoomGet($record->meeting_id);

                            return [
                                ComponentsGroup::make([
                                    TextEntry::make('user.full_name')
                                        ->label(__('messages.live_consultation.host_video').':'),
                                    TextEntry::make('consultation_date')
                                        ->label(__('messages.live_consultation.consultation_date').':'),
                                    TextEntry::make('consultation_duration_minutes')
                                        ->label(__('messages.live_consultation.duration').':'),
                                    ComponentsGroup::make([
                                        TextEntry::make('meeting_id')
                                            ->label(__('messages.common.status').':')
                                            ->formatStateUsing(function ($state) use ($zoomLiveData) {
                                                if ($zoomLiveData->status == 'started') {
                                                    return 'Started';
                                                } else {
                                                    return 'Awaited';
                                                }
                                            }),
                                        Actions::make([
                                            InfolistAction::make('join_now')
                                                ->icon('heroicon-s-video-camera')
                                                ->label(function () {
                                                    return getLoggedInUser()->hasRole('Patient') ? __('messages.live_consultation.join_now') : __('messages.live_consultation.start_now');
                                                })
                                                ->url(function ($record) use ($zoomLiveData) {
                                                    return getLoggedInUser()->hasRole('Patient') ? $record->meta['join_url'] : ($zoomLiveData->status === 'started' ? 'javascript:void(0);' : $record->meta['start_url']);
                                                }, true)
                                                ->disabled(function () use ($zoomLiveData) {
                                                    return $zoomLiveData->status === 'started';
                                                })->extraAttributes(['class' => 'ms-auto']),
                                        ])->verticalAlignment(VerticalAlignment::End),
                                    ])->columns(2)
                                        ->extraAttributes(['class' => 'border-t border-gray-200 pt-3'])
                                        ->columnSpanFull(),
                                ])->columns(2),
                            ];
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();

                            return [];
                        }
                    }),
                Tables\Actions\EditAction::make()
                    ->using(function ($data, $record, $action): void {
                        if (count($data['staff_list']) > 10) {
                            Notification::make()->danger()->title(__('messages.new_change.staff_limit'))->send();
                            $action->halt();
                        }
                        try {
                            $liveMeetingRepository = app(LiveMeetingRepository::class);
                            $liveMeetingRepository->edit($data, $record);
                        } catch (Exception $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    })
                    ->visible(function ($record) {
                        $doctorRole = getLoggedInUser()->hasRole('Doctor') ? true : false;
                        $adminRole = getLoggedInUser()->hasRole('Admin') ? true : false;
                        if ($doctorRole || $adminRole) {
                            $today = Carbon::now()->format('Y-m-d h:i A');
                            $meetingTime = Carbon::parse($record->consultation_date)->format('Y-m-d h:i A');

                            return $record->status == 0 && $meetingTime > $today;
                        }

                        return false;
                    })
                    ->successNotificationTitle(__('messages.flash.live_meeting_updated'))
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->visible(function () {
                        $doctorRole = getLoggedInUser()->hasRole('Doctor') ? true : false;
                        $adminRole = getLoggedInUser()->hasRole('Admin') ? true : false;

                        return $doctorRole || $adminRole;
                    })
                    ->iconButton()
                    ->label(__('messages.common.delete'))
                    ->tooltip(__('messages.common.delete'))
                    ->modalCancelActionLabel(__('messages.common.cancel'))
                    ->modalSubmitActionLabel(__('messages.common.confirm'))
                    ->action(function ($record) {
                        if (! canAccessRecord(LiveMeeting::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.new_change.live_meeting_not_delete'))
                                ->send();
                        }
                        try {
                            app(ZoomRepository::class)->destroyZoomMeeting($record->meeting_id);
                            $record->delete();

                            return Notification::make()
                                ->success()
                                ->title(__('messages.flash.live_meeting_deleted'))
                                ->send();
                        } catch (Exception $e) {
                            return Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                        }
                    })
                    ->modalHeading(__('messages.common.delete').'!')
                    ->modalDescription(__('messages.common.are_you_sure_want_to_delete_this').' '.__('messages.delete.live_meeting').'?')
                    ->successNotificationTitle(__('messages.flash.live_meeting_deleted')),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLiveMeetings::route('/'),
        ];
    }
}
