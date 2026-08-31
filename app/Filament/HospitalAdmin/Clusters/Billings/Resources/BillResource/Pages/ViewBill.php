<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource\Widgets\BillItemList;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBill extends ViewRecord
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->color('success')
                ->url(function ($record) {
                    return route('bills.pdf', $record->id);
                })
                ->label(__('messages.bill.print_bill'))
                ->openUrlInNewTab(),
            EditAction::make(),
            Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(static::getResource()::getUrl('index')),
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
                                    TextEntry::make('bill_id')
                                        ->prefix('#')
                                        ->label(__('messages.bill.bill'))
                                        ->extraAttributes(['class' => 'text-3xl'])
                                        ->columnSpan(4)
                                        ->inlineLabel(),
                                ])
                                ->columnSpan(4),
                        ]),
                    TextEntry::make('patient.user.full_name')
                        ->label(__('messages.case.patient').':'),
                    TextEntry::make('bill_date')
                        ->label(__('messages.bill.bill_date').': ')
                        ->formatStateUsing(function ($state) {
                            return $state ? Carbon::parse($state)->format('jS M, Y g:i A') : __('messages.common.n/a');
                        }),
                    TextEntry::make('patient_admission_id')
                        ->label(__('messages.bill.admission_id').':'),
                    TextEntry::make('patient.user.email')
                        ->label(__('messages.bill.patient_email').':'),
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
                        ->getStateUsing(fn ($record) => $record->patient->user->dob ? Carbon::parse($record->patient->user->dob)->format('jS M, Y') : __('messages.common.n/a')),

                    TextEntry::make('patientAdmission.admission_date')
                        ->label(__('messages.bill.admission_date').':')
                        ->formatStateUsing(function ($state) {
                            return $state ? Carbon::parse($state)->format('jS M, Y g:i A') : __('messages.common.n/a');
                        }),

                    TextEntry::make('bill_date')
                        ->label(__('messages.bill.bill_date').': ')
                        ->formatStateUsing(function ($state) {
                            return $state ? Carbon::parse($state)->format('jS M, Y g:i A') : __('messages.common.n/a');
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
                        ->getStateUsing(fn ($record) => $record->totalDays ?? __('messages.common.n/a')),

                    TextEntry::make('patientAdmission.policy_no')
                        ->label(__('messages.bill.policy_no').':')
                        ->getStateUsing(fn ($record) => $record->patientAdmission->policy_no ?? __('messages.common.n/a')),

                    TextEntry::make('created_at')
                        ->label(__('messages.common.created_on').':')
                        ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a')),

                    TextEntry::make('updated_at')
                        ->label(__('messages.common.last_updated').':')
                        ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a')),
                ])->columns(4),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            BillItemList::class,
        ];
    }
}
