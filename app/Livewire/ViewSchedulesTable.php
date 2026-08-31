<?php

namespace App\Livewire;

use App\Models\ScheduleDay;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class ViewSchedulesTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public function GetRecord()
    {
        $id = Route::current()->parameter('record');
        $schedules = ScheduleDay::where('schedule_id', $id);

        return $schedules;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(self::GetRecord())
            ->columns([
                TextColumn::make('available_on'),
                TextColumn::make('available_from')
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(fn ($state) => date('H:i A', strtotime($state))),
                TextColumn::make('available_to')
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(fn ($state) => date('H:i A', strtotime($state))),
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
        return view('livewire.view-schedules-table');
    }
}
