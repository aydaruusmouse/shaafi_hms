<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\InvoiceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\InvoiceResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Setting;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Actions as InfolistGroupAction;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(url()->previous()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        ImageEntry::make('invoice_logo')
                            ->label('')
                            ->url(asset(getLogoUrl()))
                            ->columnSpanFull(),
                        Group::make()->schema([
                            TextEntry::make('invoice_id')
                                ->label(__('messages.invoice.invoice_id').' #'),
                            TextEntry::make('invoice_date')
                                ->label(__('messages.invoice.invoice_date').':')
                                ->getStateUsing(fn ($record) => \Carbon\Carbon::parse($record->invoice_date)->translatedFormat('jS M, Y')),

                            TextEntry::make('patient.patientUser.full_name')
                                ->label(__('messages.invoice.patient').' '.__('messages.common.address').':')
                                ->html()
                                ->formatStateUsing(function ($record) {
                                    if (isset($record->patient->address) && ! empty($record->patient->address)) {
                                        return $record->patient->patientUser->full_name.' <br> '.ucfirst($record->patient->address->address1).', '.ucfirst($record->patient->address->city).', '.$record->patient->address->zip;
                                    }

                                    return __('messages.common.n/a');
                                }),
                            TextEntry::make('patient.patientUser.full_name')
                                ->label(__('messages.invoice.hospital_address').':')
                                ->html()
                                ->formatStateUsing(function ($record) {
                                    return getAppName().'<br>'.Setting::where('key', '=', 'hospital_address')->first()->value;
                                }),

                            Group::make()->schema([
                                TextEntry::make('Table')
                                    ->getStateUsing(function ($record) {
                                        $invoice = $record;
                                        $invoiceItems = $record->invoiceItems ?? [];
                                        $language = getCurrentLoginUserLanguageName();

                                        return view('invoices.show_invoice_table', compact('invoice', 'invoiceItems', 'language'))->render();
                                    })
                                    ->html()
                                    ->label(''),

                            ])->columnSpanFull(),
                        ])->columnSpan(2)->columns(2),
                        Group::make()->schema([
                            TextEntry::make('status')
                                ->label(__('messages.common.status').':')
                                ->badge()
                                ->color(function ($record) {
                                    if ($record->status == 1) {
                                        return 'success';
                                    } elseif ($record->status == 0) {
                                        return 'danger';
                                    }
                                })
                                ->formatStateUsing(function ($record) {
                                    if ($record->status == 1) {
                                        return __('messages.paid');
                                    } elseif ($record->status == 0) {
                                        return __('messages.unpaid');
                                    }
                                }),
                            TextEntry::make('patient.patientUser.full_name')
                                ->label(__('messages.birth_report.patient_name').':')
                                ->formatStateUsing(function ($record) {
                                    return "<a href='".PatientResource::getUrl('view', ['record' => $record->patient->id])."' class='hoverLink'>".$record->patient->patientUser->full_name.'</a>';
                                })
                                ->html()
                                ->color('primary'),
                            TextEntry::make('patient.patientUser.email')
                                ->label(__('messages.bill.patient_email').':'),
                            TextEntry::make('patient.patientUser.gender')
                                ->label(__('messages.user.gender').':')
                                ->formatStateUsing(function ($record) {
                                    if ($record->patient->patientUser->gender == 0) {
                                        return __('messages.user.male');
                                    } elseif ($record->patient->patientUser->gender == 1) {
                                        return __('messages.user.female');
                                    }
                                }),
                        ])->columnSpan(1),
                        Group::make()->schema([
                            InfolistGroupAction::make([
                                InfolistAction::make('print')
                                    ->label(__('messages.invoice.print_invoice'))
                                    ->color('success')
                                    ->url(route('invoices.pdf', $this->record->id), shouldOpenInNewTab: true),
                            ]),
                            InfolistGroupAction::make([
                                InfolistAction::make('send_mail')
                                    ->label(__('messages.invoice.send_mail'))
                                    ->color('success')
                                    ->url(route('invoices.send.mail', $this->record->id)),
                            ]),
                        ])->columnSpan(1),
                    ])->columns(4),
            ]);
    }
}
