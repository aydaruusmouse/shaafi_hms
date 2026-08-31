<?php

namespace App\Filament\HospitalAdmin\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\InvoiceResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class Invoices extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    public static function canAccess(): bool
    {
        if (auth()->user()->hasRole('Patient') && getModuleAccess('Invoices')) {
            return true;
        }

        return false;
    }

    protected static ?int $navigationSort = 16;

    protected static ?string $navigationIcon = 'fas-file-invoice';

    protected static string $view = 'filament.hospital-admin.pages.invoices';

    public static function table(Table $table): Table
    {
        return $table
            ->query(Invoice::where('tenant_id', auth()->user()->tenant_id)->where('patient_id', getLoggedInUser()->owner_id))
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('invoice_id')
                    ->sortable()
                    ->label(__('messages.invoice.invoice_id'))
                    ->badge()
                    ->url(fn ($record) => InvoiceResource::getUrl('view', ['record' => $record->id]))
                    ->searchable(),

                SpatieMediaLibraryImageColumn::make('patient.user.profile')
                    ->label(__('messages.invoice.patient'))
                    ->circular()
                    ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                    ->defaultImageUrl(function ($record) {
                        if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.id')
                    ->label('')
                    ->formatStateUsing(function (Invoice $record) {
                        return "<a href='".PatientResource::getUrl('view', ['record' => $record->patient->id])."' class='text-primary'>".$record->patient->user->full_name.'</a>';
                    })
                    ->html()
                    ->color('primary')
                    ->description(function (Invoice $record) {
                        return $record->patient->user->email;
                    })
                    ->searchable(),
                TextColumn::make('invoice_date')
                    ->label(__('messages.invoice.invoice_date'))
                    ->getStateUsing(fn ($record) => $record->invoice_date ? Carbon::parse($record->invoice_date)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('messages.invoice.amount'))
                    ->formatStateUsing(function (Invoice $record) {
                        return getCurrencyFormat($record->amount);
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->color(fn (Invoice $record) => $record->status == 1 ? 'primary' : 'warning')
                    ->formatStateUsing(function (Invoice $record) {
                        $record = $record->status == 1 ? 'Paid' : 'Pending';

                        return $record;
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('messages.user.status').':')
                    ->native(false)
                    ->options([
                        '' => __('messages.filter.all'),
                        1 => __('messages.employee_payroll.paid'),
                        0 => __('messages.appointment.pending'),
                    ]),
            ]);
    }
}
