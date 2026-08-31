<?php

namespace App\Filament\HospitalAdmin\Pages;

use App\Models\Doctor;
use App\Models\Schedule as ScheduleModel;
use App\Models\ScheduleDay;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class Schedule extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static ?string $navigationIcon = 'fas-calendar';

    protected static string $view = 'filament.hospital-admin.pages.schedule';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('messages.schedules');
    }

    public static function getLabel(): string
    {
        return __('messages.schedules');
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Doctor') && ! getModuleAccess('Schedules')) {
            return false;
        }

        return auth()->user()->hasRole('Doctor');
    }

    public function mount(): void
    {
        $doctor = Doctor::with('doctorUser')->where('user_id', auth()->user()->id)->value('id');
        $record = ScheduleModel::with('scheduleDays')->where('doctor_id', $doctor)->first();
        $this->form->fill($record->toArray());
    }
    // public  function table(Table $table): Table
    // {
    //     return $table
    //         ->query(ScheduleModel::query())
    //         ->columns([
    //             TextColumn::make('doctor_id')
    //                 ->label('Doctor')
    //         ]);
    // }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Doctor and Per Patient Time Fields at the Top
                TimePicker::make('per_patient_time')
                    ->native(false)
                    ->label(__('messages.schedule.per_patient_time'))
                    ->required()
                    ->default('00:00:00')
                    ->columnSpan(1), // Second column

                // Days of the Week Form Layout
                Section::make('')
                    ->schema([
                        Grid::make(7) // 5 columns: Available On, Available From, Available To, Action Button
                            ->schema([
                                // Monday
                                TextInput::make('schedule_days.0.available_on')
                                    ->label(__('messages.schedule.available_on').':')
                                    ->default('Monday')
                                    ->readOnly()
                                    ->columnSpan(2),

                                TimePicker::make('schedule_days.0.available_from')
                                    ->label(__('messages.schedule.available_from').':')
                                    ->columnSpan(2),

                                TimePicker::make('schedule_days.0.available_to')
                                    ->label(__('messages.schedule.available_to').':')
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
                            ]),

                        // Repeat for each other day (Tuesday to Sunday)
                        Grid::make(7)
                            ->schema([
                                // Tuesday
                                TextInput::make('schedule_days.1.available_on')
                                    ->default('Tuesday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),

                                TimePicker::make('schedule_days.1.available_from')
                                    ->label('')
                                    ->columnSpan(2),

                                TimePicker::make('schedule_days.1.available_to')
                                    ->label('')
                                    ->columnSpan(2),

                                Actions::make([
                                    Action::make('copy_previous1')
                                        ->action(function (Get $get, Set $set) {
                                            $set('schedule_days.1.available_from', str($get('schedule_days.0.available_from')));
                                            $set('schedule_days.1.available_to', str($get('schedule_days.0.available_to')));
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
                            ]),                        // Repeat the same for other days (Wednesday, Thursday, etc.)
                        Grid::make(7)
                            ->schema([
                                // Wednesday
                                TextInput::make('schedule_days.2.available_on')
                                    ->default('Wednesday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),
                                TimePicker::make('schedule_days.2.available_from')

                                    ->label('')
                                    ->columnSpan(2),
                                TimePicker::make('schedule_days.2.available_to')

                                    ->label('')
                                    ->columnSpan(2),
                                Actions::make([
                                    Action::make('copy_previous2')
                                        ->action(function (Get $get, Set $set) {
                                            $set('schedule_days.2.available_from', str($get('schedule_days.1.available_from')));
                                            $set('schedule_days.2.available_to', str($get('schedule_days.1.available_to')));
                                        })
                                        ->iconButton()
                                        ->icon('fas-copy')
                                        ->tooltip('copy-previous'),
                                ])->columnSpan(1),
                            ]),

                        Grid::make(7)
                            ->schema([
                                // Thursday
                                TextInput::make('schedule_days.3.available_on')
                                    ->default('Thursday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),
                                TimePicker::make('schedule_days.3.available_from')

                                    ->label('')
                                    ->columnSpan(2),
                                TimePicker::make('schedule_days.3.available_to')

                                    ->label('')
                                    ->columnSpan(2),
                                Actions::make([
                                    Action::make('copy_previous3')
                                        ->action(function (Get $get, Set $set) {
                                            $set('schedule_days.3.available_from', str($get('schedule_days.2.available_from')));
                                            $set('schedule_days.3.available_to', str($get('schedule_days.2.available_to')));
                                        })
                                        ->iconButton()
                                        ->icon('fas-copy')
                                        ->tooltip('copy-previous'),
                                ])->columnSpan(1),
                            ]),

                        Grid::make(7)
                            ->schema([
                                // Friday
                                TextInput::make('schedule_days.4.available_on')
                                    ->default('Friday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),
                                TimePicker::make('schedule_days.4.available_from')
                                    ->label('')
                                    ->columnSpan(2),
                                TimePicker::make('schedule_days.4.available_to')
                                    ->label('')
                                    ->columnSpan(2),
                                Actions::make([
                                    Action::make('copy_previous4')
                                        ->action(function (Get $get, Set $set) {
                                            $set('schedule_days.4.available_from', str($get('schedule_days.3.available_from')));
                                            $set('schedule_days.4.available_to', str($get('schedule_days.3.available_to')));
                                        })
                                        ->iconButton()
                                        ->icon('fas-copy')
                                        ->tooltip('copy-previous'),

                                ])->columnSpan(1),
                            ]),

                        Grid::make(7)
                            ->schema([
                                // Saturday
                                TextInput::make('schedule_days.5.available_on')
                                    ->default('Saturday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),
                                TimePicker::make('schedule_days.5.available_from')

                                    ->label('')
                                    ->columnSpan(2),
                                TimePicker::make('schedule_days.5.available_to')

                                    ->label('')
                                    ->columnSpan(2),
                                Actions::make([
                                    Action::make('copy_previous5')
                                        ->action(function (Get $get, Set $set) {
                                            $set('schedule_days.5.available_from', str($get('schedule_days.4.available_from')));
                                            $set('schedule_days.5.available_to', str($get('schedule_days.4.available_to')));
                                        })
                                        ->iconButton()
                                        ->icon('fas-copy')
                                        ->tooltip('copy-previous'),

                                ])->columnSpan(1),
                            ]),

                        Grid::make(7)
                            ->schema([
                                // Sunday
                                TextInput::make('schedule_days.6.available_on')
                                    ->default('Sunday')
                                    ->label('')
                                    ->readOnly()
                                    ->columnSpan(2),
                                TimePicker::make('schedule_days.6.available_from')
                                    ->label('')
                                    ->columnSpan(2),
                                TimePicker::make('schedule_days.6.available_to')
                                    ->label('')
                                    ->columnSpan(2),
                                Actions::make([
                                    Action::make('copy_previous7')
                                        ->action(function (Get $get, Set $set) {
                                            $set('schedule_days.6.available_from', str($get('schedule_days.5.available_from')));
                                            $set('schedule_days.6.available_to', str($get('schedule_days.5.available_to')));
                                        })
                                        ->iconButton()
                                        ->icon('fas-copy')
                                        ->tooltip('copy-previous'),

                                ])->columnSpan(1),
                            ]),
                    ])
                    ->columns(1), // Ensures the section remains as one row per day
            ])->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('messages.common.save'))
                ->submit('save'),
        ];
    }

    public function save()
    {
        $input = $this->form->getState();
        $scheduleDay = [];
        $schedule = ScheduleModel::findOrFail($this->data['id']);
        $input = $input + ['doctor_id' => $this->data['doctor_id']];
        $schedule->update($input);
        foreach ($input['schedule_days'] as $data) {
            $scheduleDay = ScheduleDay::whereScheduleId($this->data['id'])
                ->where('available_on', $data['available_on']);
            $data['doctor_id'] = $input['doctor_id'];
            $data['schedule_id'] = $schedule->id;
            $scheduleDay->update($data);
        }

        Notification::make()
            ->success()
            ->title(__('messages.flash.schedule_saved'))
            ->send();
    }
}
