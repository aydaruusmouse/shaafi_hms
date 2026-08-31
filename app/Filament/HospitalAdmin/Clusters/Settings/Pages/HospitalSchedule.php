<?php

namespace App\Filament\HospitalAdmin\Clusters\Settings\Pages;

use App\Filament\HospitalAdmin\Clusters\Settings;
use App\Models\HospitalSchedule as HospitalScheduleModel;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Support\Arr;

class HospitalSchedule extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.hospital-admin.clusters.settings.pages.hospital-schedule';

    protected static ?string $cluster = Settings::class;

    public ?array $data = [];

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('messages.hospital_schedules');
    }

    public function getTitle(): string
    {
        return __('messages.hospital_schedules');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public function mount()
    {
        $tenantId = getLoggedInUser()->tenant_id;
        $schedules = HospitalScheduleModel::where('tenant_id', $tenantId)->get();
        $this->data = $schedules->toArray();

        $defaultStartTimes = array_fill(1, 7, '00:00');
        $defaultEndTimes = array_fill(1, 7, '23:45');

        $startTimes = array_replace($defaultStartTimes, $schedules->pluck('start_time', 'day_of_week')->toArray());
        $endTimes = array_replace($defaultEndTimes, $schedules->pluck('end_time', 'day_of_week')->toArray());

        $day_of_week = array_map(fn ($key) => $schedules->where('day_of_week', $key)->isNotEmpty(), array_keys($defaultStartTimes));

        $result = [
            'start_time' => $startTimes,
            'end_time' => $endTimes,
            'day_of_week' => $day_of_week,
        ];

        $this->form->fill($result);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema(static::getFields())->columns(4),
            ])->statePath('data');
    }

    public static function getFields()
    {
        foreach (HospitalScheduleModel::WEEKDAY as $key => $value) {
            $fields[] = Fieldset::make()->schema([
                Checkbox::make('day_of_week.'.$key - 1)
                    ->extraAttributes(['class' => 'h-7 w-7'])
                    ->label($value),
                Select::make('start_time.'.$key)
                    ->label('')
                    ->native(false)
                    ->searchable()
                    ->options(getSchedulesTimingSlot()),
                Select::make('end_time.'.$key)
                    ->label('')
                    ->native(false)
                    ->searchable()
                    ->options(getSchedulesTimingSlot()),
            ])->columns(3)->columnSpan(3);
        }

        return $fields;
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->requiresConfirmation()
                ->label(__('messages.common.save'))
                ->submit('save'),
        ];
    }

    // protected function getViewData(): array
    // {
    //     $hospitalSchedules = HospitalScheduleModel::all();
    //     $weekDays = HospitalScheduleModel::WEEKDAY_FULL_NAME;
    //     $weekDay = HospitalScheduleModel::WEEKDAY;
    //     $slots = getSchedulesTimingSlot();

    //     return [
    //         'hospitalSchedules' => $hospitalSchedules,
    //         'weekDays' => $weekDays,
    //         'weekDay' => $weekDay,
    //         'slots' => $slots
    //     ];
    // }

    public function save()
    {
        $this->data['day_of_week'] = array_values(
            array_filter(
                array_map(fn ($value, $index) => $value === true ? $index + 1 : false, $this->data['day_of_week'], array_keys($this->data['day_of_week'])),
                fn ($value) => $value !== false
            )
        );

        $this->data['start_time'] = array_map(fn ($time) => $time ?? '00:00', $this->data['start_time']);
        $this->data['end_time'] = array_map(fn ($time) => $time ?? '23:45', $this->data['end_time']);

        $input = Arr::only($this->data, ['day_of_week', 'start_time', 'end_time']);

        $message = __('messages.flash.some_doctors');
        // if (isset($input['day_of_week'])) {
        //     $unCheckedDay = array_diff(array_keys(HospitalScheduleModel::WEEKDAY_FULL_NAME), $input['day_of_week']);
        //     $getFullDayName = [];
        //     foreach ($unCheckedDay as $item) {
        //         $getFullDayName[] = HospitalScheduleModel::WEEKDAY_FULL_NAME[$item];
        //     }
        //     $scheduleDayExists = ScheduleDay::whereIn('available_on', $getFullDayName)->exists();
        //     if ($scheduleDayExists) {
        //         return Notification::make()
        //             ->title($message)
        //             ->danger()
        //             ->send();
        //     } else {
        if (isset($input['day_of_week'])) {
            $oldWeekDays = HospitalScheduleModel::pluck('day_of_week')->toArray();

            foreach (array_diff($oldWeekDays, $input['day_of_week']) as $dayOfWeek) {
                HospitalScheduleModel::whereDayOfWeek($dayOfWeek)->delete();
            }

            foreach ($input['day_of_week'] as $day) {
                $startTime = $input['start_time'][$day];
                $endTime = $input['end_time'][$day];
                if (strtotime($startTime) > strtotime($endTime)) {
                    return $this->sendError(HospitalScheduleModel::WEEKDAY[$day].__('messages.new_change.time_invalid'));
                }
                HospitalScheduleModel::updateOrCreate(
                    ['day_of_week' => $day],
                    ['start_time' => $startTime, 'end_time' => $endTime]
                );
            }

            Notification::make()
                ->success()
                ->title(__('messages.flash.hospital_schedule_saved'))
                ->send();
            $this->afterSave();
            // }
            // }
        }
    }

    protected function afterSave()
    {
        $this->js('window.location.reload()');
    }
}
