<?php

namespace App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\IpdPatientResource\Pages;

use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\IpdPatientResource;
use App\Filament\HospitalAdmin\Clusters\IpdOpd\Widgets\VitalSignTable;
use App\Livewire\IpdPatientBillChargeTable;
use App\Livewire\IpdPatientBillPaymentTable;
use App\Livewire\IpdPatientBillSummaryTable;
use App\Livewire\IpdPatientChargeTable;
use App\Livewire\IpdPatientConsultantInstructionTable;
use App\Livewire\IpdPatientDiagnosisTable;
use App\Livewire\IpdOxygenMonitoringTable;
use App\Livewire\IpdPatientMarTable;
use App\Livewire\IpdPatientNurseNoteTable;
use App\Livewire\IpdPatientOperationTable;
use App\Livewire\IpdPatientPaymentTable;
use App\Livewire\IpdPatientPrescriptionTable;
use App\Livewire\IpdPatientTimeLineTable;
use App\Livewire\OpdPatientConsultationTable;
use App\Models\ConsultationPathologyTest;
use App\Models\IpdPatientDepartment;
use App\Models\IpdPrescription;
use App\Models\OpdPatientDepartment;
use App\Models\OpdPrescription;
use App\Models\User;
use App\Repositories\IpdPatientDepartmentRepository;
use App\Support\DischargeSummaryForm;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewIpdPatient extends ViewRecord
{
    protected static string $resource = IpdPatientResource::class;

    protected function getHeaderActions(): array
    {
        return $this->getActions();
    }

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('discharge')
                ->label(__('messages.ipd_patient.discharged'))
                ->color('success')
                ->modalHeading(__('messages.discharge_summary.title'))
                ->modalWidth('5xl')
                ->form(fn ($record) => DischargeSummaryForm::schema($record))
                ->fillForm(fn ($record) => DischargeSummaryForm::fill($record))
                ->action(function (IpdPatientDepartment $record, array $data) {
                    app(IpdPatientDepartmentRepository::class)->dischargePatient($record, $data);

                    Notification::make()
                        ->title(__('IPD patient discharged successfully.'))
                        ->success()
                        ->send();
                })
                ->visible(fn ($record) => ! $record->is_discharge && ! auth()->user()->hasRole('Patient')),
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(url()->previous()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->record;
        $vitalSignNumber = $record?->ipd_number;

        return $infolist
            ->schema([
                Section::make()->schema([
                    SpatieMediaLibraryImageEntry::make('patient.user.profile')->collection(User::COLLECTION_PROFILE_PICTURES)->label('')->columnSpan(2)->width(100)->height(100)
                        ->defaultImageUrl(function ($record) {
                            if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                                return getUserImageInitial($record->id, $record->patient->user->first_name);
                            }
                        })->circular()->columnSpan(1),
                    Group::make([
                        TextEntry::make('ipd_number')
                            ->label('')
                            ->badge()
                            ->formatStateUsing(fn ($state) => '#'.$state)
                            ->color('warning')
                            ->columnSpan(1),
                        TextEntry::make('patient.user.full_name')
                            ->label('')
                            ->extraAttributes(['class' => 'font-black'])
                            ->color('primary')
                            ->columnSpan(1),
                        TextEntry::make('patient.user.email')
                            ->label('')
                            ->icon('fas-envelope')
                            ->formatStateUsing(function ($state) {
                                $email = displayPatientEmail($state);
                                if ($email === __('messages.common.n/a')) {
                                    return $email;
                                }

                                return '<a href="mailto:'.e($email).'">'.e($email).'</a>';
                            })
                            ->default(__('messages.common.n/a'))
                            ->html()
                            ->columnSpan(1),
                    ])->extraAttributes(['class' => 'display-block']),
                    Group::make([]),
                    Group::make([]),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".(isset($record->patient->cases) && ($record->patient->cases) ? $record->patient->cases->count() : '0').'</span> <br> '.__('messages.patient.total_cases'))
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])
                        ->columnSpan(2),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".(isset($record->patient->admissions) && $record->patient->admissions ? $record->patient->admissions->count() : '0').'</span> <br> '.__('messages.patient.total_admissions'))
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])->columnSpan(2),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".(isset($record->patient->appointments) && $record->patient->appointments ? $record->patient->appointments->count() : '0').'</span> <br> '.'<span>'.__('messages.patient.total_appointments').'</span>')
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])
                        ->columnSpan(2),
                ])->columns(10),
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make(__('messages.overview'))
                            ->visible(fn () => hasModulePermission('IPD Overview'))
                            ->schema([
                                TextEntry::make('patientCase.case_id')
                                    ->default(__('messages.common.n/a'))
                                    ->badge()
                                    ->color('info')
                                    ->label(__('messages.case.case_id').':'),
                                TextEntry::make('admission_id')
                                    ->default(__('messages.common.n/a'))
                                    ->badge()
                                    ->color('success')
                                    ->label(__('messages.ipd_patient.admission_id').':'),
                                TextEntry::make('height')
                                    ->formatStateUsing(fn ($record) => ($record->height == 0 ? __('messages.common.n/a') : $record->height))
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.ipd_patient.height').':'),
                                TextEntry::make('weight')
                                    ->formatStateUsing(fn ($record) => ($record->weight == 0 ? __('messages.common.n/a') : $record->weight))
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.ipd_patient.weight').':'),
                                TextEntry::make('bp')
                                    ->formatStateUsing(fn ($record) => ($record->bp == 0 ? __('messages.common.n/a') : $record->bp))
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.ipd_patient.bp').':'),
                                TextEntry::make('admission_date')
                                    ->default(__('messages.common.n/a'))
                                    ->since()
                                    ->formatStateUsing(fn ($record) => date('jS M, Y h:i A', strtotime($record->admission_date)))
                                    ->label(__('messages.ipd_patient.admission_date').':'),
                                TextEntry::make('doctor.doctorUser.full_name')
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.ipd_patient.doctor_id').':'),
                                TextEntry::make('bedType.title')
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.ipd_patient.bed_type_id').':'),
                                TextEntry::make('bed.name')
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.ipd_patient.bed_id').':'),
                                TextEntry::make('is_old_patient')
                                    ->default(__('messages.common.n/a'))
                                    ->formatStateUsing(fn ($record) => ($record->is_old_patient == 1 ? __('messages.common.yes') : __('messages.common.no')))
                                    ->label(__('messages.ipd_patient.is_old_patient').':'),
                                TextEntry::make('is_discharge')
                                    ->badge()
                                    ->color(fn ($record) => $record->is_discharge ? 'success' : 'warning')
                                    ->formatStateUsing(fn ($record) => $record->is_discharge ? __('messages.common.yes') : __('messages.common.no'))
                                    ->label(__('messages.ipd_patient.discharged').':'),
                                TextEntry::make('discharge_date')
                                    ->default(__('messages.common.n/a'))
                                    ->formatStateUsing(fn ($record) => $record->discharge_date ? date('jS M, Y h:i A', strtotime($record->discharge_date)) : __('messages.common.n/a'))
                                    ->label(__('messages.patient_admission.discharge_date').':'),
                                TextEntry::make('created_at')
                                    ->default(__('messages.common.n/a'))
                                    ->since()
                                    ->label(__('messages.common.created_at').':'),
                                TextEntry::make('updated_at')
                                    ->default(__('messages.common.n/a'))
                                    ->since()
                                    ->label(__('messages.common.last_updated').':'),
                                TextEntry::make('symptoms')
                                    ->default(__('messages.common.n/a'))
                                    ->formatStateUsing(fn ($record) => ! empty($record->symptoms) ? nl2br(e($record->symptoms)) : __('messages.common.n/a'))
                                    ->label(__('messages.ipd_patient.symptoms').':'),
                                TextEntry::make('notes')
                                    ->default(__('messages.common.n/a'))
                                    ->formatStateUsing(fn ($record) => ! empty($record->notes) ? nl2br(e($record->notes)) : __('messages.common.n/a'))
                                    ->label(__('messages.ipd_patient.notes').':'),
                            ])->columns(2),
                        Tabs\Tab::make(__('messages.appointment.vital_information'))
                            ->visible(fn () => hasModulePermission('IPD Vital Information'))
                            ->schema([
                                Livewire::make(VitalSignTable::class, [
                                    'ipdOpdNumber' => $vitalSignNumber,
                                    'type' => 'IPD',
                                ])->key('ipd-'.$record->id.'-vital-signs'),
                                Livewire::make(IpdOxygenMonitoringTable::class)
                                    ->key('ipd-'.$record->id.'-oxygen-monitoring'),
                            ]),
                        Tabs\Tab::make(__('messages.appointment.consultation'))
                            ->visible(fn () => hasModulePermission('IPD Consultation'))
                            ->schema([
                                Livewire::make(OpdPatientConsultationTable::class, [
                                    'departmentType' => 'IPD',
                                ])->key('ipd-'.$record->id.'-consultation'),
                            ]),
                        Tabs\Tab::make(__('messages.appointment.test_results'))
                            ->visible(fn () => hasModulePermission('IPD Test Results'))
                            ->badge(function () use ($record) {
                                $lookups = $this->testResultLookups($record);
                                $count = ConsultationPathologyTest::query()
                                    ->where(function ($query) use ($lookups) {
                                        foreach ($lookups as $lookup) {
                                            $query->orWhere(function ($inner) use ($lookup) {
                                                $inner->where('caseable_type', $lookup['caseable_type'])
                                                    ->whereIn('caseable_id', (array) $lookup['caseable_id']);
                                            });
                                        }
                                    })
                                    ->count();

                                return $count > 0 ? $count : null;
                            })
                            ->schema([
                                ViewEntry::make('test_results_view')
                                    ->view('filament.hospital-admin.clusters.ipd-opd.resources.opd-patient-resource.pages.test-results-tab')
                                    ->extraAttributes(['class' => 'p-0 m-0'])
                                    ->columnSpanFull()
                                    ->state(function () use ($record) {
                                        return [
                                            'lookups' => $this->testResultLookups($record),
                                        ];
                                    }),
                            ]),
                        Tabs\Tab::make(__('messages.patient_diagnosis_test.diagnosis'))
                            ->visible(fn () => hasModulePermission('IPD Diagnosis'))
                            ->schema([
                                Livewire::make(IpdPatientDiagnosisTable::class),
                            ]),
                        Tabs\Tab::make(__('messages.ipd_consultant_register'))
                            ->visible(fn () => hasModulePermission('IPD Consultant Instruction'))
                            ->schema([
                                Livewire::make(IpdPatientConsultantInstructionTable::class),
                            ]),
                        Tabs\Tab::make(__('messages.charges'))
                            ->visible(fn () => hasModulePermission('IPD Charges'))
                            ->schema([
                                Livewire::make(IpdPatientChargeTable::class),
                            ]),
                        Tabs\Tab::make(__('messages.prescriptions'))
                            ->visible(fn () => hasModulePermission('IPD Prescriptions'))
                            ->schema([
                                Livewire::make(IpdPatientPrescriptionTable::class),
                            ]),
                        Tabs\Tab::make(__('messages.ipd_timelines'))
                            ->visible(fn () => hasModulePermission('IPD Timelines'))
                            ->schema([
                                Livewire::make(IpdPatientTimeLineTable::class),
                            ]),
                        Tabs\Tab::make(__('messages.ipd_patient.operations'))
                            ->visible(fn () => hasModulePermission('IPD Operations'))
                            ->schema([
                                Livewire::make(IpdPatientOperationTable::class),
                            ]),
                        Tabs\Tab::make(__('messages.ipd_patient.nurse_notes'))
                            ->visible(fn () => hasModulePermission('IPD Nurse Notes'))
                            ->schema([
                                Livewire::make(IpdPatientNurseNoteTable::class),
                                Livewire::make(IpdPatientMarTable::class),
                            ])->columns(1),
                        Tabs\Tab::make(__('messages.payments'))
                            ->visible(fn () => hasModulePermission('IPD Payments'))
                            ->schema([
                                Livewire::make(IpdPatientPaymentTable::class),
                            ]),
                        Tabs\Tab::make(__('messages.bills'))
                            ->visible(fn () => hasModulePermission('IPD Bills'))
                            ->schema([
                                Livewire::make(IpdPatientBillChargeTable::class),
                                Livewire::make(IpdPatientBillPaymentTable::class),
                                Group::make([]),
                                Livewire::make(IpdPatientBillSummaryTable::class),
                            ])->columns(2),
                        Tabs\Tab::make(__('messages.discharge_summary.title'))
                            ->icon('heroicon-o-document-text')
                            ->schema(DischargeSummaryForm::infolistSchema())
                            ->columns(1),
                    ])
                    ->activeTab(1)
                    ->columnSpanFull(),
            ]);
    }

    protected function testResultLookups($record): array
    {
        $opdIds = OpdPatientDepartment::where('case_id', $record->case_id)->pluck('id')->all();
        $ipdPrescriptionIds = IpdPrescription::where('ipd_patient_department_id', $record->id)->pluck('id')->all();
        $opdPrescriptionIds = empty($opdIds)
            ? []
            : OpdPrescription::whereIn('opd_patient_department_id', $opdIds)->pluck('id')->all();

        return [
            ['caseable_type' => IpdPrescription::class, 'caseable_id' => array_merge([$record->id], $ipdPrescriptionIds)],
            ['caseable_type' => IpdPatientDepartment::class, 'caseable_id' => $record->id],
            ['caseable_type' => OpdPrescription::class, 'caseable_id' => array_merge($opdIds, $opdPrescriptionIds)],
            ['caseable_type' => OpdPatientDepartment::class, 'caseable_id' => $opdIds],
        ];
    }
}
