<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources;

use App\Filament\HospitalAdmin\Clusters\Doctors;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\SchedulesResource\Pages;
use App\Models\Doctor;
use App\Models\HospitalSchedule;
use App\Models\Schedule;
use App\Models\User;
use App\Repositories\ScheduleRepository;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SchedulesResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Doctors::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Schedules')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Schedules')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.schedules');
    }

    public static function getLabel(): string
    {
        return __('messages.schedules');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Schedules')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Schedules')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Schedules')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {

        $daysOfWeek = HospitalSchedule::pluck('day_of_week')->toArray();

        return $form
            ->schema([
                // Doctor and Per Patient Time Fields at the Top
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('doctor_id')
                            ->label(__('messages.doctor_opd_charge.doctor').':')
                            ->required()
                            ->options(function ($operation) {

                                $scheduleRepository = app(ScheduleRepository::class);
                                $data = $scheduleRepository->getData($operation);

                                return $data['doctors'];
                            })
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->placeholder(__('messages.schedule.select_doctor_name'))
                            ->columnSpan(2)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.doctor_opd_charge.doctor').' '.__('messages.fields.required'),
                            ]), // First columnF

                        Forms\Components\TimePicker::make('per_patient_time')
                            ->label(__('messages.schedule.per_patient_time'))
                            ->required()
                            ->validationAttribute(__('messages.schedule.per_patient_time'))
                            ->default('00:00:00')
                            ->native(false)
                            ->placeholder(__('messages.schedule.per_patient_time'))
                            ->columnSpan(1), // Second column
                    ])->columns(3),

                // Days of the Week Form Layout
                Forms\Components\Section::make('')
                    ->schema([
                        Forms\Components\Grid::make(7) // 5 columns: Available On, Available From, Available To, Action Button
                            ->schema([
                                // Monday
                                Forms\Components\TextInput::make('schedule.0.available_on')
                                    ->label(__('messages.schedule.available_on').':')
                                    ->default('Monday')
                                    ->readOnly()
                                    ->columnSpan(2),

                                Forms\Components\TimePicker::make('schedule.0.available_from')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->label(__('messages.schedule.available_from').':')
                                    ->columnSpan(2),

                                Forms\Components\TimePicker::make('schedule.0.available_to')
                                    ->label(__('messages.schedule.available_to').':')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->columnSpan(2),

                                Placeholder::make('')
                                    ->label(__('messages.common.action')),
                                // Action::make('copy')
                                //     ->button()
                                //     ->icon('heroicon-o-duplicate'),
                                // Forms\Components\Button::make('monday_action')
                                //     ->icon('heroicon-o-thumb-up')
                                //     ->label(false)
                                //     ->extraAttributes(['class' => 'bg-indigo-500 text-white rounded p-2'])
                                //     ->columnSpan(1),
                            ])->visible(fn (Forms\Get $get) => in_array('1', $daysOfWeek) == '1'),

                        // Repeat for each other day (Tuesday to Sunday)
                        Forms\Components\Grid::make(7)
                            ->schema([
                                // Tuesday
                                Forms\Components\TextInput::make('schedule.1.available_on')
                                    ->default('Tuesday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),

                                Forms\Components\TimePicker::make('schedule.1.available_from')
                                    ->label('')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->columnSpan(2),

                                Forms\Components\TimePicker::make('schedule.1.available_to')
                                    ->label('')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->columnSpan(2),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('copy_previous1')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $set('schedule.1.available_from', str($get('schedule.0.available_from')));
                                            $set('schedule.1.available_to', str($get('schedule.0.available_to')));
                                        })
                                        ->iconButton()
                                        ->icon('fas-copy')
                                        ->tooltip('copy-previous'),

                                ])->columnSpan(1),
                                // Forms\Components\Button::make('tuesday_action')
                                //     ->icon('heroicon-o-thumb-up')
                                //     ->label(false)
                                //     ->extraAttributes(['class' => 'bg-indigo-500 text-white rounded p-2'])
                                //     ->columnSpan(1),
                            ])->visible(fn (Forms\Get $get) => in_array('2', $daysOfWeek) == '2'),                        // Repeat the same for other days (Wednesday, Thursday, etc.)
                        Forms\Components\Grid::make(7)
                            ->schema([
                                // Wednesday
                                Forms\Components\TextInput::make('schedule.2.available_on')
                                    ->default('Wednesday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),
                                Forms\Components\TimePicker::make('schedule.2.available_from')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->label('')
                                    ->columnSpan(2),
                                Forms\Components\TimePicker::make('schedule.2.available_to')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->label('')
                                    ->columnSpan(2),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('copy_previous2')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $set('schedule.2.available_from', str($get('schedule.1.available_from')));
                                            $set('schedule.2.available_to', str($get('schedule.1.available_to')));
                                        })
                                        ->iconButton()
                                        ->icon('fas-copy')
                                        ->tooltip('copy-previous'),
                                ])->columnSpan(1),
                            ])->visible(fn (Forms\Get $get) => in_array('3', $daysOfWeek) == '3'),

                        Forms\Components\Grid::make(7)
                            ->schema([
                                // Thursday
                                Forms\Components\TextInput::make('schedule.3.available_on')
                                    ->default('Thursday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),
                                Forms\Components\TimePicker::make('schedule.3.available_from')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->label('')
                                    ->columnSpan(2),
                                Forms\Components\TimePicker::make('schedule.3.available_to')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->label('')
                                    ->columnSpan(2),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('copy_previous3')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $set('schedule.3.available_from', str($get('schedule.2.available_from')));
                                            $set('schedule.3.available_to', str($get('schedule.2.available_to')));
                                        })
                                        ->iconButton()
                                        ->icon('fas-copy')
                                        ->tooltip('copy-previous'),
                                ])->columnSpan(1),
                            ])->visible(fn (Forms\Get $get) => in_array('4', $daysOfWeek) == '4'),

                        Forms\Components\Grid::make(7)
                            ->schema([
                                // Friday
                                Forms\Components\TextInput::make('schedule.4.available_on')
                                    ->default('Friday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),
                                Forms\Components\TimePicker::make('schedule.4.available_from')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->label('')
                                    ->columnSpan(2),
                                Forms\Components\TimePicker::make('schedule.4.available_to')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->label('')
                                    ->columnSpan(2),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('copy_previous4')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $set('schedule.4.available_from', str($get('schedule.3.available_from')));
                                            $set('schedule.4.available_to', str($get('schedule.3.available_to')));
                                        })
                                        ->iconButton()
                                        ->icon('fas-copy')
                                        ->tooltip('copy-previous'),

                                ])->columnSpan(1),
                            ])->visible(fn (Forms\Get $get) => in_array('5', $daysOfWeek) == '5'),

                        Forms\Components\Grid::make(7)
                            ->schema([
                                // Saturday
                                Forms\Components\TextInput::make('schedule.5.available_on')
                                    ->default('Saturday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),
                                Forms\Components\TimePicker::make('schedule.5.available_from')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->label('')
                                    ->columnSpan(2),
                                Forms\Components\TimePicker::make('schedule.5.available_to')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->label('')
                                    ->columnSpan(2),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('copy_previous5')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $set('schedule.5.available_from', str($get('schedule.4.available_from')));
                                            $set('schedule.5.available_to', str($get('schedule.4.available_to')));
                                        })
                                        ->iconButton()
                                        ->icon('fas-copy')
                                        ->tooltip('copy-previous'),

                                ])->columnSpan(1),
                            ])->visible(fn (Forms\Get $get) => in_array('6', $daysOfWeek) == '6'),

                        Forms\Components\Grid::make(7)
                            ->schema([
                                // Sunday
                                Forms\Components\TextInput::make('schedule.6.available_on')
                                    ->default('Sunday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),
                                Forms\Components\TimePicker::make('schedule.6.available_from')
                                    ->label('')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->columnSpan(2),
                                Forms\Components\TimePicker::make('schedule.6.available_to')
                                    ->label('')
                                    ->default('00:00:00')
                                    ->native(false)
                                    ->columnSpan(2),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('copy_previous7')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $set('schedule.6.available_from', str($get('schedule.5.available_from')));
                                            $set('schedule.6.available_to', str($get('schedule.5.available_to')));
                                        })
                                        ->iconButton()
                                        ->icon('fas-copy')
                                        ->tooltip('copy-previous'),

                                ])->columnSpan(1),
                            ])->visible(fn (Forms\Get $get) => in_array('7', $daysOfWeek) == '7'),
                    ])
                    ->columns(1), // Ensures the section remains as one row per day
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Schedules')) {
            abort(404);
        }

        return $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        })
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('doctor.user.profile')->collection(User::COLLECTION_PROFILE_PICTURES)->rounded()->label(__('messages.case.doctor'))->width(50)->height(50)
                    ->url(fn ($record) => DoctorResource::getUrl('view', ['record' => $record->doctor->id]))
                    ->sortable(['first_name'])
                    ->defaultImageUrl(function ($record) {
                        if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->doctor->user->first_name);
                        }
                    }),
                TextColumn::make('doctor.user.full_name')
                    ->label('')
                    ->description(function ($record) {
                        return $record->doctor->user->email;
                    })
                    ->formatStateUsing(fn ($record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'" class="hoverLink">'.$record->doctor->user->full_name.'</a>')
                    ->html()
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('per_patient_time')
                    ->label(__('messages.schedule.per_patient_time'))
                    ->getStateUsing(function ($record) {
                        $time = \Carbon\Carbon::createFromFormat('H:i:s', $record->per_patient_time)->format('H:i');
                        if ($time > '00:59:00') {
                            return $time.'  '.__('messages.hours');
                        } else {
                            return \Carbon\Carbon::createFromFormat('H:i:s', $record->per_patient_time)->format('i').'  '.__('messages.minutes');
                        }
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->iconButton(),
                Tables\Actions\EditAction::make()->iconButton(),
            ])
            ->recordUrl(false)
            ->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedules::route('/create'),
            'view' => Pages\ViewSchedules::route('/{record}'),
            'edit' => Pages\EditSchedules::route('/{record}/edit'),
        ];
    }
}
