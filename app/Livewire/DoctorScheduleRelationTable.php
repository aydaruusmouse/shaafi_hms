<?php

namespace App\Livewire;

use App\Models\Doctor;
use App\Models\ScheduleDay;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class DoctorScheduleRelationTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public function GetRecord()
    {
        $id = Route::current()->parameter('record');

        $doctors = Doctor::with('schedules')->where('id', $id)->get();

        foreach ($doctors as $item) {
            $this->record = $item->cases;
        }

        $schedule_ids = $this->record->pluck('doctor_id')->toArray();

        $data = ScheduleDay::whereIn('doctor_id', $schedule_ids);

        return $data;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(self::GetRecord())
            ->columns([
                TextColumn::make('available_on')
                    ->searchable()
                    ->label(__('messages.schedule.available_on')),
                TextColumn::make('available_from')
                    ->label(__('messages.schedule.available_from'))
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if ($record->available_from == '00:00:00') {
                            return __('messages.common.n/a');
                        } else {
                            return date('H:i A', strtotime($record->available_from));
                        }
                    }),
                TextColumn::make('available_to')
                    ->searchable()
                    ->label(__('messages.schedule.available_to'))
                    ->getStateUsing(function ($record) {
                        if ($record->available_to == '00:00:00') {
                            return __('messages.common.n/a');
                        } else {
                            return date('H:i A', strtotime($record->available_to));
                        }
                    }),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public function render()
    {
        return view('livewire.doctor-schedule-relation-table');
    }
}
