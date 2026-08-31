<?php

namespace App\Filament\HospitalAdmin\Clusters\LiveConsultations\Resources;

use App\Filament\HospitalAdmin\Clusters\LiveConsultations;
use App\Filament\HospitalAdmin\Clusters\LiveConsultations\Resources\LiveConsultationsResource\Pages;
use App\Models\IpdPatientDepartment;
use App\Models\LiveConsultation;
use App\Models\OpdPatientDepartment;
use App\Models\UserGoogleEventSchedule;
use App\Repositories\LiveConsultationRepository;
use App\Repositories\PatientCaseRepository;
use App\Repositories\ZoomRepository;
use Carbon\Carbon;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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

class LiveConsultationsResource extends Resource
{
    protected static ?string $model = LiveConsultation::class;

    protected static ?string $cluster = LiveConsultations::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 0;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Live Consultations')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Live Consultations')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.live_consultations');
    }

    public static function getLabel(): string
    {
        return __('messages.live_consultations');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Live Consultations')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Live Consultations')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Live Consultations')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Forms\Components\TextInput::make('consultation_title')
                            ->label(__('messages.live_consultation.consultation_title').':')
                            ->placeholder(__('messages.live_consultation.consultation_title'))
                            ->validationAttribute(__('messages.live_consultation.consultation_title'))
                            ->required(),

                        Forms\Components\DateTimePicker::make('consultation_date')
                            ->label(__('messages.live_consultation.consultation_date').':')
                            ->minDate(now())
                            ->default(Carbon::now())
                            ->native(false)
                            ->placeholder(__('messages.live_consultation.consultation_date'))
                            ->required()
                            ->validationAttribute(__('messages.live_consultation.consultation_date'))
                            ->reactive(),

                        Forms\Components\Select::make('platform_type')
                            ->label(__('messages.google_meet.platform_type').':')
                            ->placeholder(__('messages.live_consultation.select_platform'))
                            ->options(getLoggedinDoctor() ? \App\Models\LiveConsultation::PLATFORM_TYPE : \App\Models\LiveConsultation::PLATFORM_TYPE_ZOOM)
                            ->required()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.google_meet.platform_type').' '.__('messages.fields.required'),
                            ]),
                    ])->columns(3),

                Forms\Components\TextInput::make('consultation_duration_minutes')
                    ->label(__('messages.live_consultation.consultation_duration_minutes').':')
                    ->placeholder(__('messages.live_consultation.consultation_duration_minutes'))
                    ->required()
                    ->validationAttribute(__('messages.live_consultation.consultation_duration_minutes'))
                    ->numeric(),

                Forms\Components\Select::make('patient_id')
                    ->label(__('messages.blood_issue.patient_name').':')
                    ->placeholder(__('messages.user.select_patient_name'))
                    ->options(app(PatientCaseRepository::class)->getPatients())
                    ->required()
                    ->native(false)
                    ->live()
                    ->searchable()
                    ->afterStateUpdated(fn (Set $set) => $set('type', null))
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.blood_issue.patient_name').' '.__('messages.fields.required'),
                    ]),

                Grid::make(12)
                    ->schema([
                        Auth::user()->hasRole('Doctor') ? Forms\Components\Hidden::make('doctor_id')->default(Auth::user()->owner_id) : Forms\Components\Select::make('doctor_id')
                            ->label(__('messages.blood_issue.doctor_name'))
                            ->options(app(PatientCaseRepository::class)->getDoctors())
                            ->placeholder(__('messages.schedule.select_doctor_name'))
                            ->required()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.blood_issue.doctor_name').' '.__('messages.fields.required'),
                            ]),

                        Forms\Components\Select::make('type')
                            ->label(__('messages.live_consultation.type'))
                            ->options(LiveConsultation::STATUS_TYPE)
                            ->placeholder(__('messages.common.select_type'))
                            ->disabled(fn ($get) => ! $get('patient_id'))
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('type_number', null))
                            ->required(true)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.live_consultation.type').' '.__('messages.fields.required'),
                            ]),

                        Forms\Components\Select::make('type_number')
                            ->label(__('messages.live_consultation.type_number'))
                            ->placeholder(__('messages.common.select_type_number'))
                            ->options(function (Get $get) {
                                if ($get('type') === null) {
                                    return [];
                                }

                                return self::getTypeNumber($get('type'), $get('patient_id'));
                            })
                            ->native(false)
                            ->searchable()
                            ->disabled(function (Get $get) {
                                if (count(self::getTypeNumber($get('type'), $get('patient_id'))) > 0 && $get('type') != null) {
                                    return false;
                                }

                                return true;
                            })
                            ->required(true)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.live_consultation.type_number').' '.__('messages.fields.required'),
                            ]),
                    ])->columns(3),

                Group::make()->schema([
                    Forms\Components\Radio::make('host_video')
                        ->label(__('messages.live_consultation.host_video'))
                        ->options([
                            LiveConsultation::HOST_ENABLE => __('messages.live_consultation.enable'),
                            LiveConsultation::HOST_DISABLED => __('messages.live_consultation.disabled'),
                        ])
                        ->default('0')
                        ->columns(2)
                        ->validationAttribute(__('messages.live_consultation.host_video'))
                        ->required(),
                ])->columns(2),

                Group::make()->schema([
                    Forms\Components\Radio::make('participant_video')
                        ->label(__('messages.live_consultation.client_video'))
                        ->options([
                            LiveConsultation::CLIENT_ENABLE => __('messages.live_consultation.enable'),
                            LiveConsultation::CLIENT_DISABLED => __('messages.live_consultation.disabled'),
                        ])
                        ->required()
                        ->validationAttribute(__('messages.live_consultation.client_video'))
                        ->default('0')
                        ->columns(2),
                ])->columns(2),

                Forms\Components\Textarea::make('description')
                    ->label(__('messages.testimonial.description'))
                    ->placeholder(__('messages.testimonial.description'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Doctor', 'Patient']) && ! getModuleAccess('Live Consultations')) {
            abort(404);
        } elseif (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Live Consultations')) {
            abort(404);
        }

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $tenantId = Auth::user()->tenant_id;
                $query = LiveConsultation::whereHas('patient', function ($query) use ($tenantId) {
                    $query->whereHas('patientUser', function ($subQuery) use ($tenantId) {
                        $subQuery->where('users.tenant_id', $tenantId); // Tenant filter
                    })->where('patients.tenant_id', $tenantId); // Patient tenant filter
                })
                    ->whereHas('doctor', function ($query) use ($tenantId) {
                        $query->whereHas('doctorUser', function ($subQuery) use ($tenantId) {
                            $subQuery->where('users.tenant_id', $tenantId); // Tenant filter
                        })->where('doctors.tenant_id', $tenantId); // Doctor tenant filter
                    })
                    ->whereHas('user', function ($query) use ($tenantId) {
                        $query->where('users.tenant_id', $tenantId); // User tenant filter
                    })
                    ->where('live_consultations.tenant_id', $tenantId) // Main tenant filter
                    ->with([
                        'patient.patientUser',
                        'doctor.doctorUser',
                        'user',
                    ]);

                $ipdIds = IpdPatientDepartment::where('tenant_id', $tenantId)->pluck('id')->toArray();
                $opdIds = OpdPatientDepartment::where('tenant_id', $tenantId)->pluck('id')->toArray();

                $query->where(function (Builder $q) use ($ipdIds, $opdIds) {
                    $q->whereIn('type_number', $ipdIds)->where('type', 1)
                        ->orWhereIn('type_number', $opdIds)->where('type', 0);
                });

                if (getLoggedInUser()->hasRole('Patient')) {
                    $query->where('patient_id', getLoggedInUser()->owner_id);
                }
                if (getLoggedInUser()->hasRole('Doctor')) {
                    $query->where('doctor_id', getLoggedInUser()->owner_id);
                }

                $query->select('live_consultations.*');

                return $query;
            })
            ->paginated([10, 25, 50])
            ->recordUrl(false)
            ->recordAction(null)
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('consultation_title')
                    ->label(__('messages.live_consultation.consultation_title'))
                    ->searchable()
                    ->sortable()
                    ->action(
                        Action::make('campaing-video')
                            ->infolist(self::getViewModel())
                    ),
                Tables\Columns\ViewColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->sortable()
                    ->alignCenter()
                    ->view('filament.hospital-admin.clusters.live-consultations.columns.status'),
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
                Tables\Columns\TextColumn::make('doctor.doctorUser.full_name')
                    ->label(__('messages.live_consultation.created_for'))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name']),
                Tables\Columns\TextColumn::make('patient.patientUser.full_name')
                    ->label(__('messages.investigation_report.patient'))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name']),
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
            ->actions([
                Action::make('start_consultation_google')
                    ->visible(function ($record) {
                        return $record->platform_type == LiveConsultation::GOOGLE_MEET;
                    })
                    ->icon('heroicon-s-video-camera')
                    ->iconButton()
                    ->disabled(function ($record) {
                        $googleUserEventSchedule = UserGoogleEventSchedule::where('user_id', $record->user->id)->where('google_live_consultation_id', $record->id)->first();

                        return $record->status == 0 && ! empty($googleUserEventSchedule->google_meet_link) ? false : true;
                    })
                    ->url(function ($record) {
                        $googleUserEventSchedule = UserGoogleEventSchedule::where('user_id', $record->user->id)->where('google_live_consultation_id', $record->id)->first();

                        if (! empty($googleUserEventSchedule->google_meet_link)) {
                            return $googleUserEventSchedule->google_meet_link;
                        } else {
                            return 'javascript:void(0)';
                        }
                    }, true),
                Action::make('start_consultation_zoom')
                    ->visible(function ($record) {
                        return $record->platform_type != LiveConsultation::GOOGLE_MEET;
                    })
                    ->icon('heroicon-s-video-camera')
                    ->iconButton()
                    ->disabled(function ($record) {
                        $meetingTime = Carbon::parse($record->consultation_date);

                        return $record->status == LiveConsultation::STATUS_AWAITED && $meetingTime > Carbon::now() ? false : true;
                    })
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
                    ->iconButton()
                    ->visible(fn ($record) => $record->status == LiveConsultation::STATUS_AWAITED)
                    ->action(function (array $data, $record) {
                        try {
                            app(liveConsultationRepository::class)->edit($data, $record);

                            return Notification::make()
                                ->title(__('messages.flash.live_consultation_updated'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {

                            $responseData = json_decode($e->getMessage(), true);

                            if (isset($responseData['error'])) {
                                $errorCode = $responseData['error']['code'];

                                if ($errorCode == 401) {
                                    Notification::make()->danger()->title(__('messages.google_meet.disconnect_or_reconnect'))->send();
                                }
                            }

                            return Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    })
                    ->successNotificationTitle(__('messages.flash.live_consultation_updated')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->label(__('messages.common.delete'))
                    ->tooltip(__('messages.common.delete'))
                    ->modalCancelActionLabel(__('messages.common.cancel'))
                    ->modalSubmitActionLabel(__('messages.common.confirm'))
                    ->modalHeading(__('messages.common.delete').'!')
                    ->modalDescription(__('messages.common.are_you_sure_want_to_delete_this').' '.__('messages.live_consultations').'?')
                    ->action(function (array $data, $record) {
                        if (! canAccessRecord(LiveConsultation::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.live_consultation_not_found'))
                                ->send();
                        }
                        try {
                            if ($record->platform_type == LiveConsultation::GOOGLE_MEET) {
                                $userGoogleEventCalendar = UserGoogleEventSchedule::where(['user_id' => Auth::id(), 'google_live_consultation_id' => $liveConsultation->id])->first();
                                $userGoogleEventCalendar->delete();
                                $record->delete();
                            } else {
                                app(ZoomRepository::class)->destroyZoomMeeting($record->meeting_id);
                                $record->delete();
                            }

                            return Notification::make()
                                ->success()
                                ->title(__('messages.flash.live_consultation_deleted'))
                                ->send();
                        } catch (Exception $e) {
                            return Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                        }
                    })
                    ->modalDescription(__('messages.common.are_you_sure_want_to_delete_this').' '.__('messages.live_consultations').'?'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLiveConsultations::route('/'),
        ];
    }

    public static function getTypeNumber($type, $patientId)
    {
        if ($type == LiveConsultation::OPD) {
            return OpdPatientDepartment::where('patient_id', $patientId)->pluck('opd_number', 'id')->toArray();
        } else {
            return IpdPatientDepartment::where('patient_id', $patientId)->pluck('ipd_number', 'id')->toArray();
        }
    }

    public static function getViewModel()
    {
        return [
            ComponentsGroup::make([
                TextEntry::make('consultation_title')
                    ->label(__('messages.live_consultation.consultation_title').':'),
                TextEntry::make('consultation_date')
                    ->label(__('messages.live_consultation.consultation_date').':')
                    ->date('d M, Y g:i A'),
                TextEntry::make('consultation_duration_minutes')
                    ->label(__('messages.live_consultation.consultation_duration_minutes').':'),
                TextEntry::make('patient.patientUser.full_name')
                    ->label(__('messages.blood_issue.patient_name').':'),
                TextEntry::make('doctor.doctorUser.full_name')
                    ->label(__('messages.blood_issue.doctor_name').':'),
                TextEntry::make('type')
                    ->label(__('messages.live_consultation.type').':')
                    ->formatStateUsing(function ($state) {
                        if ($state == 0) {
                            return 'OPD';
                        } else {
                            return 'IPD';
                        }
                    }),
                TextEntry::make('opdPatient.opd_number')
                    ->label(__('messages.live_consultation.type_number').':')
                    ->formatStateUsing(function ($record) {
                        if ($record->type == 0) {
                            return $record->opdPatient->opd_number ?? 'N/A';
                        } else {
                            return $record->ipd_patient->ipd_number ?? 'N/A';
                        }
                    }),
                TextEntry::make('host_video')
                    ->label(__('messages.live_consultation.host_video').':')
                    ->visible(function ($record) {
                        if ($record->platform_type != 2) {
                            return true;
                        }

                        return false;
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state == 0) {
                            return 'Disable';
                        } else {
                            return 'Enable';
                        }
                    }),
                TextEntry::make('participant_video')
                    ->label(__('messages.live_consultation.client_video').':')
                    ->visible(function ($record) {
                        if ($record->platform_type != 2) {
                            return true;
                        }

                        return false;
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state == 0) {
                            return 'Disable';
                        } else {
                            return 'Enable';
                        }
                    }),
                TextEntry::make('description')
                    ->label(__('messages.testimonial.description').':')
                    ->default('N/A')
                    ->formatStateUsing(function ($state) {
                        return $state ?? 'N/A';
                    }),
                TextEntry::make('consultation_title')
                    ->label(__('messages.google_meet.platform_type').':')
                    ->formatStateUsing(function ($record) {
                        if ($record->platform_type != 2) {
                            return 'Zoom';
                        } else {
                            return 'Google Meet';
                        }
                    }),
            ])->columns(2),
        ];
    }
}
