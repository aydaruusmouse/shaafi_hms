<?php

namespace App\Livewire;

use App\Models\InsuranceDisease;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class InsuranceDiseaseTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public $id;

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(InsuranceDisease::where('insurance_id', $this->id))
            ->heading(__('messages.insurance.disease_details'))
            ->columns([
                TextColumn::make('disease_name')
                    ->label(__('messages.insurance.diseases_name'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('disease_charge')
                    ->label(__('messages.insurance.diseases_charge'))
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state, 2))
                    ->alignEnd()
                    ->default(__('messages.common.n/a')),
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
        return view('livewire.insurance-disease-table');
    }
}
