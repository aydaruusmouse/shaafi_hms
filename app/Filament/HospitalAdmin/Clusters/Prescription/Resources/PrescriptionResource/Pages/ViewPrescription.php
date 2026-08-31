<?php

namespace App\Filament\HospitalAdmin\Clusters\Prescription\Resources\PrescriptionResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Prescription\Resources\PrescriptionResource;
use App\Livewire\PrescriptionMedicineTable;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPrescription extends ViewRecord
{
    protected static string $resource = PrescriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label(__('messages.ipd_patient_prescription.print_prescription'))
                ->color('success')
                ->url(function ($record) {
                    return route('prescriptions.pdf', $record->id);
                })
                ->openUrlInNewTab(),
            Actions\EditAction::make(),
            Actions\Action::make('back')
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
                        Group::make()->schema([
                            ImageEntry::make('app_logo')
                                ->label('')
                                ->default(asset(getSettingValue()['app_logo']['value'])),
                            TextEntry::make('doctor.user.full_name')
                                ->formatStateUsing(fn ($record) => '<span class="'.'font-bold'.'">'.$record->doctor->user->full_name.'<br> <span class="'.'opacity-50'.'">'.$record->doctor->specialist.'</span>')
                                ->html()
                                ->label(''),
                        ])->columns(1),
                        Group::make()->schema([
                            TextEntry::make('patient.user.full_name')
                                ->inlineLabel()
                                ->label(__('messages.patient_admission.patient_name').':'),
                            TextEntry::make('created_at')
                                ->inlineLabel()
                                ->date(fn ($record) => Carbon::parse($record->created_at)->isoFormat('DD/MM/Y'))
                                ->label(__('messages.bill.bill_date').':'),
                            TextEntry::make('patient.user.dob')
                                ->inlineLabel()
                                ->state(function ($record) {
                                    return $record->patient->user->dob ?: __('messages.common.n/a');
                                })
                                ->formatStateUsing(function ($state) {
                                    if ($state !== __('messages.common.n/a')) {
                                        return \Carbon\Carbon::parse($state)->diff(\Carbon\Carbon::now())->y.' '.__('messages.subscription_pricing_plans.year');
                                    } else {
                                        return __('messages.common.n/a');
                                    }
                                })
                                ->label(__('messages.patient_diagnosis_test.age').':'),
                        ]),
                        Group::make()->schema([
                            TextEntry::make('id')
                                ->label(__('messages.common.address').':')
                                ->formatStateUsing(function ($record) {
                                    if (empty($record->doctor->address->address1) && empty($record->doctor->address->address2) && empty($record->doctor->address->city)) {
                                        return __('messages.common.n/a');
                                    }
                                    $phoneNumber = '';
                                    if (str_starts_with($record->doctor->user->phone, '+') && strlen($record->doctor->user->phone) > 4) {
                                        $phoneNumber = $record->doctor->user->phone;
                                    } elseif (empty($record->doctor->user->phone) || empty($record->doctor->user->region_code)) {
                                        $phoneNumber = __('messages.common.n/a');
                                    } else {
                                        $phoneNumber = $record->doctor->user->region_code.$record->doctor->user->phone;
                                    }

                                    return '<p class="mb-3">
                                        '.(! empty($record->doctor->address->address1) ? $record->doctor->address->address1 : '').'
                                        '.(! empty($record->doctor->address->address2) ? (! empty($record->doctor->address->address1) ? ',' : '') : '').'
                                        '.(! empty($record->doctor->address->address2) ? $record->doctor->address->address2 : '').'
                                        '.(! empty($record->doctor->address->city) ? ',' : '').'
                                        '.(! empty($record->doctor->address->city) ? '<br>' : '').'
                                        '.(! empty($record->doctor->address->city) ? $record->doctor->address->city : '').'
                                        '.(! empty($record->doctor->address->zip) ? ',' : '').'
                                        '.(! empty($record->doctor->address->zip) ? '<br>' : '').'
                                        '.(! empty($record->doctor->address->zip) ? $record->doctor->address->zip : '').'
                                    </p>
                                    <p class="opacity-50 mb-4">
                                        '.($phoneNumber).'
                                    </p>
                                    <p class="opacity-50 mb-3">
                                        '.(! empty($record->doctor->user->email) ? $record->doctor->user->email : '').'
                                    </p>';
                                })
                                ->html(),
                        ]),
                        Fieldset::make('')
                            ->schema([
                                TextEntry::make('problem_description')
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.prescription.problem').':'),
                                TextEntry::make('test')
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.prescription.test').':'),
                                TextEntry::make('advice')
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.prescription.advice').':'),
                                Group::make(),
                                Group::make(),
                                Group::make(),
                                TextEntry::make('food_allergies')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->food_allergies))
                                    ->label(__('messages.prescription.food_allergies').':'),
                                TextEntry::make('tendency_bleed')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->tendency_bleed))
                                    ->label(__('messages.prescription.tendency_bleed').':'),
                                TextEntry::make('heart_disease')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->heart_disease))
                                    ->label(__('messages.prescription.heart_disease').':'),
                                TextEntry::make('high_blood_pressure')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->high_blood_pressure))
                                    ->label(__('messages.prescription.high_blood_pressure').':'),
                                TextEntry::make('diabetic')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->diabetic))
                                    ->label(__('messages.prescription.diabetic').':'),
                                TextEntry::make('surgery')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->surgery))
                                    ->label(__('messages.prescription.surgery').':'),
                                TextEntry::make('accident')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->accident))
                                    ->label(__('messages.prescription.accident').':'),
                                TextEntry::make('others')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->others))
                                    ->label(__('messages.prescription.others').':'),
                                TextEntry::make('medical_history')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->medical_history))
                                    ->label(__('messages.prescription.medical_history').':'),
                                TextEntry::make('current_medication')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->current_medication))
                                    ->label(__('messages.prescription.current_medication').':'),
                                TextEntry::make('female_pregnancy')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->female_pregnancy))
                                    ->label(__('messages.prescription.female_pregnancy').':'),
                                TextEntry::make('breast_feeding')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->breast_feeding))
                                    ->label(__('messages.prescription.breast_feeding').':'),
                                TextEntry::make('health_insurance')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->health_insurance))
                                    ->label(__('messages.prescription.health_insurance').':'),
                                TextEntry::make('low_income')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->low_income))
                                    ->label(__('messages.prescription.low_income').':'),
                                TextEntry::make('reference')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()->visible(fn ($record) => ! empty($record->reference))
                                    ->label(__('messages.prescription.reference').':'),
                                TextEntry::make('plus_rate')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()->visible(fn ($record) => ! empty($record->plus_rate))
                                    ->label(__('messages.prescription.plus_rate').':'),
                                TextEntry::make('temperature')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()->visible(fn ($record) => ! empty($record->temperature))
                                    ->label(__('messages.prescription.temperature').':'),
                                TextEntry::make('')
                                    ->default(''),
                                TextEntry::make('problem_description')
                                    ->default(__('messages.common.n/a'))
                                    ->inlineLabel()
                                    ->visible(fn ($record) => ! empty($record->problem_description))
                                    ->label(__('messages.prescription.problem_description').':'),
                            ])->columnSpanFull()->columns(3),
                        Group::make(
                            function ($record) {
                                if ($record->getMedicine->count()) {
                                    return [
                                        Livewire::make(PrescriptionMedicineTable::class),
                                    ];
                                }

                                return [];
                            }
                        )->columnSpanFull(),
                    ])->columns(3),
            ]);
    }
}
