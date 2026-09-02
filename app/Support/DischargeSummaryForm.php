<?php

namespace App\Support;

use App\Models\IpdDiagnosis;
use App\Models\IpdPatientDepartment;
use App\Models\IpdPrescription;
use App\Models\IpdPrescriptionItem;
use App\Models\OpdDiagnosis;
use App\Models\OpdPatientDepartment;
use App\Models\OpdPrescription;
use App\Models\OpdPrescriptionItem;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

class DischargeSummaryForm
{
    public const CONDITIONS = [
        'stable' => 'Stable',
        'improved' => 'Improved',
        'unchanged' => 'Unchanged',
        'critical' => 'Critical',
    ];

    public const DISPOSITIONS = [
        'home' => 'Home',
        'skilled_nursing' => 'Skilled nursing facility',
        'transfer' => 'Transfer to another hospital',
        'lama' => 'Left against medical advice',
        'other' => 'Other',
    ];

    public static function schema(Model $record): array
    {
        $record->loadMissing(['patient.user', 'doctor.user']);
        if ($record instanceof IpdPatientDepartment) {
            $record->loadMissing(['bed']);
        }

        $user = $record->patient?->user;
        $details = is_array($record->discharge_details) ? $record->discharge_details : [];

        return [
            Section::make(__('messages.discharge_summary.patient_information'))
                ->schema([
                    Placeholder::make('patient_name')
                        ->label(__('messages.user.name'))
                        ->content($user->full_name ?? __('messages.common.n/a')),
                    Placeholder::make('patient_dob')
                        ->label(__('messages.user.dob'))
                        ->content(! empty($user?->dob) ? date('jS M, Y', strtotime($user->dob)) : __('messages.common.n/a')),
                    Placeholder::make('patient_age')
                        ->label(__('messages.discharge_summary.age'))
                        ->content(self::patientAge($user)),
                    Placeholder::make('patient_gender')
                        ->label(__('messages.user.gender'))
                        ->content(displayPatientGender($user)),
                    Placeholder::make('patient_mrn')
                        ->label(__('messages.discharge_summary.mrn'))
                        ->content($record->patient?->patient_unique_id ?? __('messages.common.n/a')),
                ])->columns(5),
            Section::make(__('messages.discharge_summary.admission_discharge_details'))
                ->schema([
                    Placeholder::make('admission_at')
                        ->label(__('messages.discharge_summary.admission_at'))
                        ->content(self::admissionAt($record)),
                    DateTimePicker::make('discharge_date')
                        ->label(__('messages.patient_admission.discharge_date'))
                        ->native(false)
                        ->required()
                        ->default(now()),
                    Placeholder::make('attending_physician')
                        ->label(__('messages.discharge_summary.attending_physician'))
                        ->content($record->doctor?->user?->full_name ?? __('messages.common.n/a')),
                    Placeholder::make('unit_room')
                        ->label(__('messages.discharge_summary.unit_room'))
                        ->content(self::unitRoom($record)),
                ])->columns(4),
            Section::make(__('messages.discharge_summary.diagnoses'))
                ->schema([
                    Textarea::make('admission_reason')
                        ->label(__('messages.discharge_summary.admission_reason'))
                        ->rows(2)
                        ->default($details['admission_reason'] ?? ($record->symptoms ?: null)),
                    Textarea::make('discharge_diagnoses')
                        ->label(__('messages.discharge_summary.discharge_diagnoses'))
                        ->rows(2)
                        ->default($details['discharge_diagnoses'] ?? self::diagnosesText($record)),
                    Textarea::make('secondary_conditions')
                        ->label(__('messages.discharge_summary.secondary_conditions'))
                        ->rows(2)
                        ->default($details['secondary_conditions'] ?? null),
                ])->columns(1),
            Section::make(__('messages.discharge_summary.hospital_course'))
                ->schema([
                    Textarea::make('hospital_course')
                        ->label(__('messages.discharge_summary.hospital_course'))
                        ->rows(4)
                        ->required()
                        ->default($details['hospital_course'] ?? $record->discharge_summary),
                    Textarea::make('procedures')
                        ->label(__('messages.discharge_summary.procedures'))
                        ->rows(2)
                        ->default($details['procedures'] ?? null),
                    Textarea::make('key_results')
                        ->label(__('messages.discharge_summary.key_results'))
                        ->rows(2)
                        ->default($details['key_results'] ?? null),
                ])->columns(1),
            Section::make(__('messages.discharge_summary.medication_plan'))
                ->schema([
                    Textarea::make('new_medications')
                        ->label(__('messages.discharge_summary.new_medications'))
                        ->rows(2)
                        ->default($details['new_medications'] ?? self::medicationsText($record)),
                    Textarea::make('continued_medications')
                        ->label(__('messages.discharge_summary.continued_medications'))
                        ->rows(2)
                        ->default($details['continued_medications'] ?? null),
                    Textarea::make('discontinued_medications')
                        ->label(__('messages.discharge_summary.discontinued_medications'))
                        ->rows(2)
                        ->default($details['discontinued_medications'] ?? null),
                ])->columns(1),
            Section::make(__('messages.discharge_summary.follow_up'))
                ->schema([
                    Textarea::make('follow_up_appointments')
                        ->label(__('messages.discharge_summary.follow_up_appointments'))
                        ->rows(2)
                        ->default($details['follow_up_appointments'] ?? null),
                    Textarea::make('diet_activity')
                        ->label(__('messages.discharge_summary.diet_activity'))
                        ->rows(2)
                        ->default($details['diet_activity'] ?? null),
                    Textarea::make('wound_care')
                        ->label(__('messages.discharge_summary.wound_care'))
                        ->rows(2)
                        ->default($details['wound_care'] ?? null),
                    Textarea::make('warning_signs')
                        ->label(__('messages.discharge_summary.warning_signs'))
                        ->rows(2)
                        ->default($details['warning_signs'] ?? null),
                ])->columns(2),
            Section::make(__('messages.discharge_summary.discharge_status'))
                ->schema([
                    Select::make('condition')
                        ->label(__('messages.discharge_summary.condition'))
                        ->options(self::CONDITIONS)
                        ->native(false)
                        ->required()
                        ->default($details['condition'] ?? 'stable'),
                    Select::make('disposition')
                        ->label(__('messages.discharge_summary.disposition'))
                        ->options(self::DISPOSITIONS)
                        ->native(false)
                        ->required()
                        ->default($details['disposition'] ?? 'home'),
                ])->columns(2),
        ];
    }

    public static function fill(Model $record): array
    {
        $details = is_array($record->discharge_details) ? $record->discharge_details : [];

        return [
            'discharge_date' => $record->discharge_date ?? now(),
            'admission_reason' => $details['admission_reason'] ?? ($record->symptoms ?: null),
            'discharge_diagnoses' => $details['discharge_diagnoses'] ?? self::diagnosesText($record),
            'secondary_conditions' => $details['secondary_conditions'] ?? null,
            'hospital_course' => $details['hospital_course'] ?? $record->discharge_summary,
            'procedures' => $details['procedures'] ?? null,
            'key_results' => $details['key_results'] ?? null,
            'new_medications' => $details['new_medications'] ?? self::medicationsText($record),
            'continued_medications' => $details['continued_medications'] ?? null,
            'discontinued_medications' => $details['discontinued_medications'] ?? null,
            'follow_up_appointments' => $details['follow_up_appointments'] ?? null,
            'diet_activity' => $details['diet_activity'] ?? null,
            'wound_care' => $details['wound_care'] ?? null,
            'warning_signs' => $details['warning_signs'] ?? null,
            'condition' => $details['condition'] ?? 'stable',
            'disposition' => $details['disposition'] ?? 'home',
        ];
    }

    public static function infolistSchema(): array
    {
        $text = fn (string $key, string $label) => TextEntry::make('discharge_details.'.$key)
            ->label(__('messages.discharge_summary.'.$label).':')
            ->getStateUsing(function ($record) use ($key) {
                $value = self::detail($record, $key);
                if ($key === 'hospital_course' && blank($value)) {
                    $value = $record->discharge_summary;
                }
                if ($key === 'condition') {
                    return self::conditionLabel($value);
                }
                if ($key === 'disposition') {
                    return self::dispositionLabel($value);
                }

                return ! blank($value) ? nl2br(e($value)) : __('messages.common.n/a');
            })
            ->html()
            ->columnSpanFull();

        return [
            InfoSection::make(__('messages.discharge_summary.patient_information'))
                ->schema([
                    TextEntry::make('patient.user.full_name')
                        ->label(__('messages.user.name').':')
                        ->default(__('messages.common.n/a')),
                    TextEntry::make('patient.user.dob')
                        ->label(__('messages.user.dob').':')
                        ->formatStateUsing(fn ($state) => $state ? date('jS M, Y', strtotime($state)) : __('messages.common.n/a')),
                    TextEntry::make('patient_age')
                        ->label(__('messages.discharge_summary.age').':')
                        ->getStateUsing(fn ($record) => self::patientAge($record->patient?->user)),
                    TextEntry::make('patient_gender')
                        ->label(__('messages.user.gender').':')
                        ->getStateUsing(fn ($record) => displayPatientGender($record->patient?->user)),
                    TextEntry::make('patient.patient_unique_id')
                        ->label(__('messages.discharge_summary.mrn').':')
                        ->default(__('messages.common.n/a')),
                ])->columns(5),
            InfoSection::make(__('messages.discharge_summary.admission_discharge_details'))
                ->schema([
                    TextEntry::make('admission_at')
                        ->label(__('messages.discharge_summary.admission_at').':')
                        ->getStateUsing(fn ($record) => self::admissionAt($record)),
                    TextEntry::make('discharge_date')
                        ->label(__('messages.patient_admission.discharge_date').':')
                        ->formatStateUsing(fn ($state) => $state ? date('jS M, Y h:i A', strtotime($state)) : __('messages.common.n/a')),
                    TextEntry::make('doctor.user.full_name')
                        ->label(__('messages.discharge_summary.attending_physician').':')
                        ->default(__('messages.common.n/a')),
                    TextEntry::make('unit_room')
                        ->label(__('messages.discharge_summary.unit_room').':')
                        ->getStateUsing(fn ($record) => self::unitRoom($record)),
                    TextEntry::make('is_discharge')
                        ->label(__('messages.common.status').':')
                        ->badge()
                        ->getStateUsing(fn ($record) => $record->is_discharge ? __('messages.ipd_patient.discharged') : __('messages.filter.active'))
                        ->color(fn ($record) => $record->is_discharge ? 'success' : 'warning'),
                ])->columns(5),
            InfoSection::make(__('messages.discharge_summary.diagnoses'))
                ->schema([
                    $text('admission_reason', 'admission_reason'),
                    $text('discharge_diagnoses', 'discharge_diagnoses'),
                    $text('secondary_conditions', 'secondary_conditions'),
                ]),
            InfoSection::make(__('messages.discharge_summary.hospital_course'))
                ->schema([
                    $text('hospital_course', 'hospital_course'),
                    $text('procedures', 'procedures'),
                    $text('key_results', 'key_results'),
                ]),
            InfoSection::make(__('messages.discharge_summary.medication_plan'))
                ->schema([
                    $text('new_medications', 'new_medications'),
                    $text('continued_medications', 'continued_medications'),
                    $text('discontinued_medications', 'discontinued_medications'),
                ]),
            InfoSection::make(__('messages.discharge_summary.follow_up'))
                ->schema([
                    $text('follow_up_appointments', 'follow_up_appointments'),
                    $text('diet_activity', 'diet_activity'),
                    $text('wound_care', 'wound_care'),
                    $text('warning_signs', 'warning_signs'),
                ]),
            InfoSection::make(__('messages.discharge_summary.discharge_status'))
                ->schema([
                    $text('condition', 'condition'),
                    $text('disposition', 'disposition'),
                ])->columns(2),
        ];
    }

    public static function payload(array $data): array
    {
        $details = [
            'admission_reason' => $data['admission_reason'] ?? null,
            'discharge_diagnoses' => $data['discharge_diagnoses'] ?? null,
            'secondary_conditions' => $data['secondary_conditions'] ?? null,
            'hospital_course' => $data['hospital_course'] ?? null,
            'procedures' => $data['procedures'] ?? null,
            'key_results' => $data['key_results'] ?? null,
            'new_medications' => $data['new_medications'] ?? null,
            'continued_medications' => $data['continued_medications'] ?? null,
            'discontinued_medications' => $data['discontinued_medications'] ?? null,
            'follow_up_appointments' => $data['follow_up_appointments'] ?? null,
            'diet_activity' => $data['diet_activity'] ?? null,
            'wound_care' => $data['wound_care'] ?? null,
            'warning_signs' => $data['warning_signs'] ?? null,
            'condition' => $data['condition'] ?? null,
            'disposition' => $data['disposition'] ?? null,
        ];

        return [
            'discharge_date' => $data['discharge_date'],
            'discharge_summary' => $data['hospital_course'] ?? null,
            'discharge_details' => $details,
        ];
    }

    public static function conditionLabel(?string $value): string
    {
        return self::CONDITIONS[$value] ?? ($value ?: __('messages.common.n/a'));
    }

    public static function dispositionLabel(?string $value): string
    {
        return self::DISPOSITIONS[$value] ?? ($value ?: __('messages.common.n/a'));
    }

    public static function detail(Model $record, string $key): ?string
    {
        $details = is_array($record->discharge_details) ? $record->discharge_details : [];

        return $details[$key] ?? null;
    }

    private static function patientAge($user): string
    {
        if (empty($user?->dob)) {
            return __('messages.common.n/a');
        }

        try {
            return \Carbon\Carbon::parse($user->dob)->age.' '.__('messages.discharge_summary.years');
        } catch (\Throwable) {
            return __('messages.common.n/a');
        }
    }

    private static function admissionAt(Model $record): string
    {
        $date = $record instanceof IpdPatientDepartment
            ? $record->admission_date
            : $record->appointment_date;

        return $date ? date('jS M, Y h:i A', strtotime($date)) : __('messages.common.n/a');
    }

    private static function unitRoom(Model $record): string
    {
        if ($record instanceof IpdPatientDepartment) {
            return $record->bed?->name ?? __('messages.common.n/a');
        }

        return __('messages.opd_patients');
    }

    private static function diagnosesText(Model $record): ?string
    {
        if ($record instanceof IpdPatientDepartment) {
            $items = IpdDiagnosis::where('ipd_patient_department_id', $record->id)->get();

            return $items->map(fn ($item) => trim($item->report_type.($item->description ? ': '.$item->description : '')))->filter()->implode("\n") ?: null;
        }

        $items = OpdDiagnosis::where('opd_patient_department_id', $record->id)->get();

        return $items->map(fn ($item) => trim($item->report_type.($item->description ? ': '.$item->description : '')))->filter()->implode("\n") ?: null;
    }

    private static function medicationsText(Model $record): ?string
    {
        if ($record instanceof IpdPatientDepartment) {
            $ids = IpdPrescription::where('ipd_patient_department_id', $record->id)->pluck('id');
            $items = IpdPrescriptionItem::with('medicine')->whereIn('ipd_prescription_id', $ids)->get();
        } else {
            $ids = OpdPrescription::where('opd_patient_department_id', $record->id)->pluck('id');
            $items = OpdPrescriptionItem::with('medicine')->whereIn('opd_prescription_id', $ids)->get();
        }

        $lines = $items->map(function ($item) {
            $name = $item->medicine->name ?? __('messages.common.n/a');
            $dose = $item->dosage ? ' — '.$item->dosage : '';

            return $name.$dose;
        })->filter()->unique()->values();

        return $lines->isNotEmpty() ? $lines->implode("\n") : null;
    }
}
