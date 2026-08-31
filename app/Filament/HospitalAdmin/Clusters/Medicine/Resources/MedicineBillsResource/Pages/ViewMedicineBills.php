<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource\Widgets\MedicineDetail;
use Carbon\Carbon;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicineBills extends ViewRecord
{
    protected static string $resource = MedicineBillsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->color('success')
                ->label(__('messages.bill.print_bill'))
                ->url(function ($record) {
                    return route('medicine.bill.pdf', $record->id);
                })
                ->openUrlInNewTab(),
            Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(url()->previous()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('')
                ->schema([
                    Grid::make(12)
                        ->schema([
                            Grid::make(12)
                                ->schema([
                                    ImageEntry::make('logo')
                                        ->label('')
                                        ->defaultImageUrl(asset(getLogoUrl()))
                                        ->columnSpan(10)
                                        ->height(100)
                                        ->width(100),
                                    TextEntry::make('print_button')
                                        ->label(__('messages.bill.print_bill'))
                                        ->columnSpan(2)
                                        ->extraAttributes(['class' => 'd-flex justify-content-end'])
                                        ->view('components.print-button', [
                                            'billId' => $this->record->id,
                                            'language' => getLoggedInUser()->language,
                                        ]),
                                    TextEntry::make('bill_number')
                                        ->prefix('#')
                                        ->label(__(''))
                                        ->extraAttributes(['class' => 'text-3xl'])
                                        ->columnSpan(4),
                                ])
                                ->columnSpan(4),
                        ]),
                    TextEntry::make('patient.user.full_name')
                        ->label(__('messages.document.patient').': '),
                    TextEntry::make('bill_date')
                        ->label(__('messages.bill.bill_date').':'),
                    TextEntry::make('patient.user.email')
                        ->label(__('messages.bill.patient_email').':'),
                    TextEntry::make('payment_status')
                        ->label(__('messages.ipd_patient.bill_status').':')
                        ->formatStateUsing(function ($state) {
                            return $state === 1 ? __('messages.employee_payroll.paid') : __('messages.appointment.pending');
                        }),
                    TextEntry::make('patient.patientUser.phone')
                        ->label(__('messages.bill.patient_cell_no').':')
                        ->default(__('messages.common.n/a')),

                    TextEntry::make('patient.patientUser.gender')
                        ->label(__('messages.bill.patient_gender').':')
                        ->formatStateUsing(function ($state) {
                            return $state ? __('messages.user.male') : __('messages.user.female');
                        }),

                    TextEntry::make('patient.patientUser.dob')
                        ->label(__('messages.bill.patient_dob').':')
                        ->default(__('messages.common.n/a'))
                        ->getStateUsing(function ($record) {
                            return $record->patient->patientUser->dob ? Carbon::parse($record->patient->patientUser->dob)->format('jS M, Y') : '';
                        }),

                    TextEntry::make('patientAdmission.discharge_date')
                        ->label(__('messages.bill.discharge_date').':')
                        ->getStateUsing(fn ($record) => $record->patientAdmission->discharge_date ?? __('messages.common.n/a')),

                    TextEntry::make('patientAdmission.package.name')
                        ->label(__('messages.bill.package_name').':')
                        ->getStateUsing(fn ($record) => $record->patientAdmission->package->name ?? __('messages.common.n/a')),

                    TextEntry::make('patientAdmission.insurance.name')
                        ->label(__('messages.bill.insurance_name').':')
                        ->getStateUsing(fn ($record) => $record->patientAdmission->insurance->name ?? __('messages.common.n/a')),

                    TextEntry::make('totalDays')
                        ->label(__('messages.bill.total_days').':')
                        ->getStateUsing(fn ($record) => $record->patientAdmission->insurance->name ?? __('messages.common.n/a')),

                    TextEntry::make('patientAdmission->policy_no')
                        ->label(__('messages.bill.policy_no').':')
                        ->getStateUsing(fn ($record) => $record->patientAdmission->insurance->name ?? __('messages.common.n/a')),

                    TextEntry::make('created_at')
                        ->label(__('messages.common.created_on').':')
                        ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a')),

                    TextEntry::make('updated_at')
                        ->label(__('messages.common.last_updated').':')
                        ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a')),
                    Grid::make(12)
                        ->schema([
                            TextEntry::make('')
                                ->columnSpan(9),

                            Grid::make(3)
                                ->schema([
                                    TextEntry::make('total')
                                        ->label(__('messages.purchase_medicine.total').':')
                                        ->inlineLabel()
                                        ->columnSpan(3),
                                    TextEntry::make('tax')
                                        ->label(__('messages.purchase_medicine.tax').':')
                                        ->inlineLabel()
                                        ->default('0')
                                        ->columnSpan(3),
                                    TextEntry::make('discount')
                                        ->label(__('messages.purchase_medicine.discount').':')
                                        ->inlineLabel()
                                        ->columnSpan(3),
                                    TextEntry::make('net_amount')
                                        ->label(__('messages.purchase_medicine.net_amount').':')
                                        ->inlineLabel()
                                        ->columnSpan(3),
                                ])
                                ->columnSpan(3),
                        ]),
                ])->columns(4),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            MedicineDetail::class,
        ];
    }
}
