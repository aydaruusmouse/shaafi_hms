<?php

namespace App\Livewire;

use App\Models\PackageService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class PackageServiceTable extends Component implements HasForms, HasTable
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
            ->query((PackageService::where('package_id', $this->id)))
            ->heading(__('messages.services'))
            ->columns([
                TextColumn::make('service.name')
                    ->label(__('messages.package.service'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('quantity')
                    ->label(__('messages.package.qty'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('rate')
                    ->label(__('messages.package.rate'))
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state, 2))
                    ->alignEnd()
                    ->default(__('messages.common.n/a')),
                TextColumn::make('amount')
                    ->label(__('messages.package.amount'))
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state, 2))
                    ->alignEnd()
                    ->default(__('messages.common.n/a')),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public function render()
    {
        return view('livewire.package-service-table');
    }
}
