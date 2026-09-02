<?php

namespace App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\OpdPatientResource\Pages;

use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\IpdPatientResource;
use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\OpdPatientResource;
use App\Livewire\OpdPatientConsultationTable;
use App\Livewire\OpdPatientDiagnosisTable;
use App\Livewire\OpdPatientPrescriptionTable;
use App\Livewire\OpdPatientTimeLineTable;
use App\Livewire\OpdPatientVisitTable;
use App\Models\Bed;
use App\Models\BedType;
use App\Models\ConsultationMedicalInformation;
use App\Models\ConsultationPathologyTest;
use App\Models\ConsultationRadiologyTest;
use App\Models\IpdDiagnosis;
use App\Models\IpdPatientDepartment;
use App\Models\IpdPrescription;
use App\Models\IpdPrescriptionItem;
use App\Models\OpdDiagnosis;
use App\Models\OpdPatientDepartment;
use App\Models\OpdPrescription;
use App\Models\IpdTimeline;
use App\Models\OpdTimeline;
use App\Models\User;
use App\Models\VitalSign;
use App\Repositories\IpdPatientDepartmentRepository;
use App\Support\DischargeSummaryForm;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\DB;
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

class ViewOpdPatient extends ViewRecord
{
    protected static string $resource = OpdPatientResource::class;

    public $ipdPatientId = null;

    protected function getHeaderActions(): array
    {
        return $this->getActions();
    }

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('transfer_to_ipd')
                ->label('Transfer to IPD')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->modalHeading('Transfer to IPD')
                ->modalWidth('2xl')
                ->modalSubmitActionLabel('Transfer to IPD')
                ->form([
                    FormSection::make()
                        ->schema([
                            Select::make('bed_type_id')
                                ->label(__('messages.bed.bed_type').':')
                                ->placeholder(__('messages.bed.select_bed_type'))
                                ->required()
                                ->live()
                                ->options(fn () => BedType::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('title', 'id'))
                                ->searchable()
                                ->native(false)
                                ->preload(),
                            Select::make('bed_id')
                                ->label(__('messages.bed_assign.bed').':')
                                ->placeholder(__('messages.bed.select_bed'))
                                ->required()
                                ->options(function ($get) {
                                    $bedTypeId = $get('bed_type_id');
                                    if (! $bedTypeId) {
                                        return [];
                                    }

                                    return Bed::availableByType($bedTypeId)->pluck('name', 'id');
                                })
                                ->searchable()
                                ->native(false)
                                ->preload(),
                            DateTimePicker::make('admission_date')
                                ->label(__('messages.ipd_patient.admission_date').':')
                                ->required()
                                ->default(now())
                                ->native(false),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    $this->transferToIpd($data);
                })
                ->after(function () {
                    if ($this->ipdPatientId) {
                        $this->redirect(IpdPatientResource::getUrl('index'), navigate: true);
                    }
                })
                ->visible(function ($record) {
                    if (auth()->user()->hasRole('Patient') || $record->is_discharge) {
                        return false;
                    }

                    if (! $record->case_id) {
                        return true;
                    }

                    return ! IpdPatientDepartment::where('case_id', $record->case_id)
                        ->where('is_discharge', 0)
                        ->exists();
                }),
            Actions\Action::make('discharge')
                ->label(__('messages.ipd_patient.discharged'))
                ->color('success')
                ->modalHeading(__('messages.discharge_summary.title'))
                ->modalWidth('5xl')
                ->form(fn ($record) => DischargeSummaryForm::schema($record))
                ->fillForm(fn ($record) => DischargeSummaryForm::fill($record))
                ->action(function (OpdPatientDepartment $record, array $data) {
                    $record->update(array_merge([
                        'is_discharge' => true,
                    ], DischargeSummaryForm::payload($data)));

                    Notification::make()
                        ->title(__('messages.flash.OPD_Patient_updated') ?: 'OPD patient discharged successfully.')
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

    protected function transferToIpd(array $data): void
    {
        $opdRecord = $this->record;

        if (! $opdRecord) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('OPD patient record not found.')
                ->send();

            return;
        }

        if (! $opdRecord->case_id) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('This OPD patient does not have a case assigned. Please assign a case first.')
                ->send();

            return;
        }

        $existingIpd = IpdPatientDepartment::where('case_id', $opdRecord->case_id)
            ->where('is_discharge', 0)
            ->first();

        if ($existingIpd) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('This patient already has an active IPD admission for this case.')
                ->send();

            return;
        }

        $bed = Bed::find($data['bed_id']);
        if (! $bed || $bed->is_available != 1) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('The selected bed is not available.')
                ->send();

            return;
        }

        $ipdData = [
            'patient_id' => $opdRecord->patient_id,
            'case_id' => $opdRecord->case_id,
            'doctor_id' => $opdRecord->doctor_id,
            'ipd_number' => IpdPatientDepartment::generateUniqueIpdNumber(),
            'admission_id' => IpdPatientDepartment::generateUniqueAdmissionId(),
            'height' => $opdRecord->height ?? null,
            'weight' => $opdRecord->weight ?? null,
            'bp' => $opdRecord->bp ?? null,
            'symptoms' => $opdRecord->symptoms ?? null,
            'notes' => ($opdRecord->notes ?? '').' [Transferred from OPD #'.$opdRecord->opd_number.']',
            'admission_date' => $data['admission_date'],
            'bed_type_id' => $data['bed_type_id'],
            'bed_id' => $data['bed_id'],
            'custom_field' => $opdRecord->custom_field ?? null,
            'tenant_id' => getLoggedInUser()->tenant_id,
        ];

        $hasPreviousIpd = IpdPatientDepartment::where('patient_id', $opdRecord->patient_id)
            ->where('tenant_id', getLoggedInUser()->tenant_id)
            ->exists();

        if ($hasPreviousIpd) {
            $ipdData['is_old_patient'] = true;
        }

        DB::beginTransaction();

        try {
            $ipdRepository = app(IpdPatientDepartmentRepository::class);
            $ipdPatient = $ipdRepository->store($ipdData);

            if (! $ipdPatient || ! $ipdPatient->id) {
                throw new \Exception('Failed to create IPD patient record');
            }

            $ipdRepository->createNotification($ipdData);

            $opdPrescriptions = OpdPrescription::where('opd_patient_department_id', $opdRecord->id)->get();
            $prescriptionMap = [];

            foreach ($opdPrescriptions as $opdPrescription) {
                $ipdPrescription = IpdPrescription::create([
                    'ipd_patient_department_id' => $ipdPatient->id,
                    'header_note' => $opdPrescription->header_note,
                    'footer_note' => $opdPrescription->footer_note,
                ]);

                $prescriptionMap[$opdPrescription->id] = $ipdPrescription->id;

                foreach ($opdPrescription->opdPrescriptionItems as $opdItem) {
                    IpdPrescriptionItem::create([
                        'ipd_prescription_id' => $ipdPrescription->id,
                        'category_id' => $opdItem->category_id,
                        'medicine_id' => $opdItem->medicine_id,
                        'dosage' => $opdItem->dosage,
                        'dose_interval' => $opdItem->dose_interval,
                        'day' => $opdItem->day,
                        'time' => $opdItem->time,
                        'instruction' => $opdItem->instruction,
                    ]);
                }
            }

            foreach ($prescriptionMap as $opdPrescriptionId => $ipdPrescriptionId) {
                ConsultationMedicalInformation::where('caseable_type', OpdPrescription::class)
                    ->where('caseable_id', $opdPrescriptionId)
                    ->update([
                        'caseable_type' => IpdPrescription::class,
                        'caseable_id' => $ipdPrescriptionId,
                    ]);

                ConsultationPathologyTest::where('caseable_type', OpdPrescription::class)
                    ->where('caseable_id', $opdPrescriptionId)
                    ->update([
                        'caseable_type' => IpdPrescription::class,
                        'caseable_id' => $ipdPrescriptionId,
                    ]);

                ConsultationRadiologyTest::where('caseable_type', OpdPrescription::class)
                    ->where('caseable_id', $opdPrescriptionId)
                    ->update([
                        'caseable_type' => IpdPrescription::class,
                        'caseable_id' => $ipdPrescriptionId,
                    ]);
            }

            ConsultationPathologyTest::where('caseable_type', OpdPatientDepartment::class)
                ->where('caseable_id', $opdRecord->id)
                ->update([
                    'caseable_type' => IpdPatientDepartment::class,
                    'caseable_id' => $ipdPatient->id,
                ]);

            ConsultationRadiologyTest::where('caseable_type', OpdPatientDepartment::class)
                ->where('caseable_id', $opdRecord->id)
                ->update([
                    'caseable_type' => IpdPatientDepartment::class,
                    'caseable_id' => $ipdPatient->id,
                ]);

            ConsultationMedicalInformation::where('caseable_type', OpdPrescription::class)
                ->where('caseable_id', $opdRecord->id)
                ->update([
                    'caseable_type' => IpdPrescription::class,
                    'caseable_id' => $ipdPatient->id,
                ]);

            ConsultationPathologyTest::where('caseable_type', OpdPrescription::class)
                ->where('caseable_id', $opdRecord->id)
                ->update([
                    'caseable_type' => IpdPrescription::class,
                    'caseable_id' => $ipdPatient->id,
                ]);

            ConsultationRadiologyTest::where('caseable_type', OpdPrescription::class)
                ->where('caseable_id', $opdRecord->id)
                ->update([
                    'caseable_type' => IpdPrescription::class,
                    'caseable_id' => $ipdPatient->id,
                ]);

            ConsultationMedicalInformation::where('caseable_type', OpdPatientDepartment::class)
                ->where('caseable_id', $opdRecord->id)
                ->update([
                    'caseable_type' => IpdPatientDepartment::class,
                    'caseable_id' => $ipdPatient->id,
                ]);

            foreach (VitalSign::where('ipd_opd_number', $opdRecord->opd_number)->where('type', 'OPD')->get() as $vital) {
                $copy = $vital->replicate();
                $copy->type = 'IPD';
                $copy->ipd_opd_number = $ipdPatient->ipd_number;
                $copy->save();
            }

            foreach (OpdTimeline::where('opd_patient_department_id', $opdRecord->id)->get() as $opdTimeline) {
                $ipdTimeline = IpdTimeline::create([
                    'ipd_patient_department_id' => $ipdPatient->id,
                    'title' => $opdTimeline->title,
                    'date' => $opdTimeline->date,
                    'description' => $opdTimeline->description,
                    'visible_to_person' => $opdTimeline->visible_to_person,
                ]);

                if ($opdTimeline->hasMedia(OpdTimeline::OPD_TIMELINE_PATH)) {
                    $media = $opdTimeline->getFirstMedia(OpdTimeline::OPD_TIMELINE_PATH);
                    if ($media) {
                        $ipdTimeline->addMediaFromUrl($media->getUrl())
                            ->toMediaCollection(IpdTimeline::IPD_TIMELINE_PATH, config('app.media_disk'));
                    }
                }
            }

            $opdDiagnoses = OpdDiagnosis::where('opd_patient_department_id', $opdRecord->id)->get();
            foreach ($opdDiagnoses as $opdDiagnosis) {
                $ipdDiagnosis = IpdDiagnosis::create([
                    'ipd_patient_department_id' => $ipdPatient->id,
                    'report_type' => $opdDiagnosis->report_type,
                    'report_date' => $opdDiagnosis->report_date,
                    'description' => $opdDiagnosis->description,
                ]);

                if ($opdDiagnosis->hasMedia(OpdDiagnosis::OPD_DIAGNOSIS_PATH)) {
                    $media = $opdDiagnosis->getFirstMedia(OpdDiagnosis::OPD_DIAGNOSIS_PATH);
                    if ($media) {
                        $ipdDiagnosis->addMediaFromUrl($media->getUrl())
                            ->toMediaCollection(IpdDiagnosis::IPD_DIAGNOSIS_PATH, config('app.media_disk'));
                    }
                }
            }

            DB::commit();

            Notification::make()
                ->success()
                ->title('Patient Transferred to IPD')
                ->body('Patient has been successfully transferred from OPD to IPD with all consultations, prescriptions, diagnoses, and test results.')
                ->send();

            $this->ipdPatientId = $ipdPatient->id;
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Error Transferring Patient')
                ->body('Failed to transfer patient to IPD: '.$e->getMessage())
                ->persistent()
                ->send();
        }
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->record;
        $vitalSignNumber = $record?->opd_number;

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
                        TextEntry::make('opd_number')
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
                            ->visible(fn () => hasModulePermission('OPD Overview'))
                            ->schema([
                                TextEntry::make('patientCase.case_id')
                                    ->default(__('messages.common.n/a'))
                                    ->badge()
                                    ->color('info')
                                    ->label(__('messages.case.case_id').':'),
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
                                TextEntry::make('appointment_date')
                                    ->default(__('messages.common.n/a'))
                                    ->since()
                                    ->formatStateUsing(fn ($record) => ! empty($record->appointment_date) && strtotime($record->appointment_date) ? date('jS M,Y g:i A', strtotime($record->appointment_date)) : __('messages.common.n/a'))
                                    ->label(__('messages.opd_patient.appointment_date').':'),
                                TextEntry::make('doctor.doctorUser.full_name')
                                    ->default(__('messages.common.n/a'))
                                    ->label(__('messages.case.doctor').':'),
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
                                    ->formatStateUsing(fn ($record) => $record->discharge_date ? date('jS M,Y g:i A', strtotime($record->discharge_date)) : __('messages.common.n/a'))
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
                            ->visible(fn () => hasModulePermission('OPD Vital Information'))
                            ->schema([
                                Livewire::make(
                                    \App\Filament\HospitalAdmin\Clusters\IpdOpd\Widgets\VitalSignTable::class,
                                    [
                                        'ipdOpdNumber' => $vitalSignNumber,
                                        'type' => 'OPD',
                                    ]
                                )->key('opd-'.$record->id.'-vital-signs'),
                            ]),
                        Tabs\Tab::make(__('messages.appointment.consultation'))
                            ->visible(fn () => hasModulePermission('OPD Consultation'))
                            ->schema([
                                Livewire::make(OpdPatientConsultationTable::class)
                                    ->key('opd-'.$record->id.'-consultation'),
                            ]),
                        Tabs\Tab::make(__('messages.appointment.test_results'))
                            ->visible(fn () => hasModulePermission('OPD Test Results'))
                            ->badge(function () use ($record) {
                                $count = ConsultationPathologyTest::where('caseable_id', $record->id)
                                    ->where('caseable_type', OpdPatientDepartment::class)
                                    ->count();

                                $count += \App\Models\ConsultationPathologyTest::where('caseable_id', $record->id)
                                    ->where('caseable_type', \App\Models\OpdPrescription::class)
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
                                            'record_id' => $record->id,
                                            'caseable_type' => \App\Models\OpdPrescription::class,
                                        ];
                                    }),
                            ]),
                        Tabs\Tab::make(__('messages.prescriptions'))
                            ->visible(fn () => hasModulePermission('OPD Prescriptions'))
                            ->schema([
                                Livewire::make(OpdPatientPrescriptionTable::class)
                                    ->key('opd-'.$record->id.'-prescriptions'),
                            ]),
                        Tabs\Tab::make(__('messages.ipd_diagnosis'))
                            ->visible(fn () => hasModulePermission('OPD Diagnosis'))
                            ->schema([
                                Livewire::make(OpdPatientDiagnosisTable::class)
                                    ->key('opd-'.$record->id.'-diagnosis'),
                            ]),
                        Tabs\Tab::make(__('messages.opd_patient.visits'))
                            ->visible(fn () => hasModulePermission('OPD Visits'))
                            ->schema([
                                Livewire::make(OpdPatientVisitTable::class)
                                    ->key('opd-'.$record->id.'-visits'),
                            ]),
                        Tabs\Tab::make(__('messages.ipd_timelines'))
                            ->visible(fn () => hasModulePermission('OPD Timelines'))
                            ->schema([
                                Livewire::make(OpdPatientTimeLineTable::class)
                                    ->key('opd-'.$record->id.'-timeline'),
                            ]),
                        Tabs\Tab::make(__('messages.discharge_summary.title'))
                            ->icon('heroicon-o-document-text')
                            ->schema(DischargeSummaryForm::infolistSchema())
                            ->columns(1),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
