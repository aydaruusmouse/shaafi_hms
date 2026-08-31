<?php

namespace App\Livewire;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class PatientInvoiceRelationTable extends Component implements HasForms, HasTable
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
            ->query(Invoice::where('patient_id', $this->id)->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc'))
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('invoice_id')
                    ->label(__('messages.invoice.invoice_id'))
                    ->default(__('messages.common.n/a'))
                    ->badge()
                    ->color('primary')
                    ->sortable()->searchable(),
                TextColumn::make('invoice_date')
                    ->label(__('messages.invoice.invoice_date'))
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->extraAttributes(['class' => 'text-center'])
                    ->sortable()
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('g:i A').' <br>   '.\Carbon\Carbon::parse($state)->translatedFormat('jS M, Y'))
                    ->html(),
                TextColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('messages.invoice.paid') : __('messages.invoice.not_paid'))
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                TextColumn::make('amount')
                    ->label(__('messages.invoice.amount'))
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

                return __('messages.common.actions');
            })
            ->actions([
                Actions\EditAction::make()
                    ->url(fn ($record) => InvoiceResource::getUrl('edit', ['record' => $record->id]))
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
                    ->successNotificationTitle(__('messages.flash.invoice_deleted')),
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
        return view('livewire.patient-invoice-relation-table');
    }
}
