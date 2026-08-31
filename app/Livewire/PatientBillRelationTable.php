<?php

namespace App\Livewire;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource;
use App\Models\Bill;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class PatientBillRelationTable extends Component implements HasForms, HasTable
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
            ->query(Bill::where('patient_id', $this->id)->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc'))
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('bill_id')
                    ->label(__('messages.bill.bill_id'))
                    ->default(__('messages.common.n/a'))
                    ->badge()
                    ->color('primary')
                    ->sortable()->searchable(),
                TextColumn::make('bill_date')
                    ->label(__('messages.bill.bill_date'))
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('g:i A').' <br>   '.\Carbon\Carbon::parse($state)->translatedFormat('jS M, Y'))
                    ->html(),
                TextColumn::make('amount')
                    ->label(__('messages.bill.amount'))
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state))
                    ->html(),
            ])
            ->actionsColumnLabel(function () {
                if (auth()->user()->hasRole('Patient')) {
                    return null;
                }

                return __('messages.bill.actions');
            })
            ->actions([
                Actions\EditAction::make()
                    ->url(fn ($record) => BillResource::getUrl('edit', ['record' => $record->id]))
                    ->visible(function () {
                        if (auth()->user()->hasRole('Patient')) {
                            return false;
                        }

                        return true;
                    })
                    ->iconButton(),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(function () {
                        if (auth()->user()->hasRole('Patient')) {
                            return false;
                        }

                        return true;
                    })
                    ->successNotificationTitle(__('messages.flash.bill_deleted')),
            ])
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
        return view('livewire.patient-bill-relation-table');
    }
}
