<?php

namespace App\Livewire;

use App\Models\ConsultationMedicalInformation;
use App\Models\ConsultationPathologyTest;
use App\Models\ConsultationRadiologyTest;
use App\Models\IpdPatientDepartment;
use App\Models\IpdPrescription;
use App\Models\OpdPatientDepartment;
use App\Models\OpdPrescription;
use App\Models\PathologyCategory;
use App\Models\PathologyParameter; // Add this import
use App\Models\RadiologyCategory;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class OpdPatientConsultationTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;
    public $id;
    public $consultationId;
    public string $departmentType = 'OPD';

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
        $this->consultationId;
    }

    protected function caseableClass(): string
    {
        return $this->departmentType === 'IPD' ? IpdPrescription::class : OpdPrescription::class;
    }

    protected function departmentClass(): string
    {
        return $this->departmentType === 'IPD' ? IpdPatientDepartment::class : OpdPatientDepartment::class;
    }

    protected function permissionModule(): string
    {
        return $this->departmentType === 'IPD' ? 'IPD Consultation' : 'OPD Consultation';
    }

    public function GetRecord()
    {
        return ConsultationMedicalInformation::query()
            ->where(function ($query) {
                $query->where('caseable_type', $this->caseableClass())
                    ->where('caseable_id', $this->id);

                if ($this->departmentType === 'IPD') {
                    $ipd = IpdPatientDepartment::find($this->id);
                    if ($ipd?->case_id) {
                        $opdIds = OpdPatientDepartment::where('case_id', $ipd->case_id)->pluck('id');
                        $query->orWhere(function ($opdQuery) use ($opdIds) {
                            $opdQuery->where('caseable_type', OpdPrescription::class)
                                ->whereIn('caseable_id', $opdIds);
                        })->orWhere(function ($opdQuery) use ($opdIds) {
                            $opdQuery->where('caseable_type', OpdPatientDepartment::class)
                                ->whereIn('caseable_id', $opdIds);
                        });
                    }
                }
            })
            ->orderBy('created_at', 'desc');
    }

    public function getFormFields(): array
    {
        $opdPatient = $this->departmentClass()::findOrFail($this->id);
        
        return [
            Hidden::make('opd_patient_department_id')->default($this->id),
            Hidden::make('patient_id')->default($opdPatient->patient_id),
            Hidden::make('case_id')->default($opdPatient->case_id),
            Hidden::make('doctor_id')->default($opdPatient->doctor_id),
            Hidden::make('caseable_type')->default($this->caseableClass()),
            Hidden::make('caseable_id')->default($this->id),

            // Medical Information Section
            Section::make('Medical Information')
                ->collapsible()
                ->schema([
                    Textarea::make('chief_complain')
                        ->rows(3)
                        ->placeholder('Chief Complain')
                        ->label('Chief Complain')
                        ->columnSpanFull(),

                    Textarea::make('past_medical_surgical_history')
                        ->rows(3)
                        ->placeholder('Past Medical/Surgical History')
                        ->label('Past Medical/Surgical History')
                        ->columnSpanFull(),

                    Textarea::make('family_social_history')
                        ->rows(3)
                        ->placeholder('Family / Social History')
                        ->label('Family / Social History')
                        ->columnSpanFull(),

                    Textarea::make('drug_history_allergy')
                        ->rows(3)
                        ->placeholder('Drug History / Allergy')
                        ->label('Drug History / Allergy')
                        ->columnSpanFull(),

                    Textarea::make('chronic_diseases_history')
                        ->rows(3)
                        ->placeholder('Chronic Diseases History')
                        ->label('Chronic Diseases History')
                        ->columnSpanFull(),

                    Textarea::make('obstetric_gynecology_history')
                        ->rows(3)
                        ->placeholder('Obstetric / Gynaecology History')
                        ->label('Obstetric / Gynaecology History')
                        ->columnSpanFull(),

                    Textarea::make('physical_examination')
                        ->rows(3)
                        ->placeholder('Physical Examination')
                        ->label('Physical Examination')
                        ->columnSpanFull(),

                    Textarea::make('differential_diagnosis')
                        ->rows(3)
                        ->placeholder('Differential Diagnosis')
                        ->label('Differential Diagnosis')
                        ->columnSpanFull(),

                    Textarea::make('professional_diagnosis')
                        ->rows(3)
                        ->placeholder('Professional Diagnosis')
                        ->label('Professional Diagnosis')
                        ->columnSpanFull(),
                ]),

            // Pathology Lab Test Section - UPDATED to match IPD
            Section::make('Pathology Lab Test')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Repeater::make('pathology_tests')
                        ->schema([
                            Select::make('pathology_category_id')
                                ->label('Pathology Category')
                                ->options(PathologyCategory::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(1),

                            Select::make('pathology_parameter_id')
                                ->label('Parameter')
                                ->options(PathologyParameter::pluck('parameter_name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(1),

                            Textarea::make('notes')
                                ->label('Notes')
                                ->placeholder('Additional notes')
                                ->rows(2)
                                ->columnSpan(2),
                        ])
                        ->columns(4)
                        ->addActionLabel('Add Pathology Test')
                        ->deletable(true)
                        ->cloneable(true)
                        ->reorderable(true),
                ]),

            // Radiology Test Section
            Section::make('Radiology Test')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Repeater::make('radiology_tests')
                        ->schema([
                            Select::make('radiology_category_id')
                                ->label('Radiology Category')
                                ->options(RadiologyCategory::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(1),

                            TextInput::make('test_name')
                                ->label('Test Name')
                                ->placeholder('Enter test name')
                                ->required()
                                ->columnSpan(1),

                            Textarea::make('notes')
                                ->label('Notes')
                                ->placeholder('Additional notes')
                                ->rows(2)
                                ->columnSpan(2),
                        ])
                        ->columns(4)
                        ->addActionLabel('Add Radiology Test')
                        ->deletable(true)
                        ->cloneable(true)
                        ->reorderable(true),
                ]),
        ];
    }

    public function getEditFormFields(): array
    {
        return $this->getFormFields();
    }

    public function getTableColumns(): array
    {
        return [
            TextColumn::make('created_at')
                ->label('Date')
                ->dateTime('d M, Y h:i A')
                ->sortable(),
            TextColumn::make('chief_complain')
                ->label('Chief Complain')
                ->limit(50)
                ->tooltip(function ($record) {
                    return $record->chief_complain;
                }),
            TextColumn::make('pathology_tests_count')
                ->label('Pathology Tests')
                ->getStateUsing(function ($record) {
                    return ConsultationPathologyTest::where('caseable_type', $this->caseableClass())
                        ->where('caseable_id', $record->caseable_id)
                        ->where('patient_id', $record->patient_id)
                        ->count();
                })
                ->badge(),
            TextColumn::make('radiology_tests_count')
                ->label('Radiology Tests')
                ->getStateUsing(function ($record) {
                    return ConsultationRadiologyTest::where('caseable_type', $this->caseableClass())
                        ->where('caseable_id', $record->caseable_id)
                        ->where('patient_id', $record->patient_id)
                        ->count();
                })
                ->badge(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50])
            ->headerActions([
                Actions\CreateAction::make()
                    ->modalWidth('7xl')
                    ->createAnother(false)
                    ->form($this->getFormFields())
                    ->using(function (array $data, string $model) {
                        try {
                            // Create the medical information record
                            $this->consultationId = $model::create(Arr::except($data, [
                                'pathology_tests', 
                                'radiology_tests'
                            ]));

                            return $this->consultationId;
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                            throw $e;
                        }
                    })
                    ->after(function (array $data) {
                        if ($this->consultationId->id) {
                            try {
                                // Save Pathology Tests - UPDATED
                                $this->savePathologyTests($data);
                                
                                // Save Radiology Tests
                                $this->saveRadiologyTests($data);

                                Notification::make()
                                    ->success()
                                    ->title('Consultation created successfully')
                                    ->send();

                            } catch (Exception $e) {
                                // Rollback by deleting the created medical info
                                if ($this->consultationId->id) {
                                    $this->consultationId->delete();
                                }
                                Notification::make()
                                    ->danger()
                                    ->title($e->getMessage())
                                    ->send();
                            }
                        }
                    })
                    ->successNotificationTitle('Consultation created successfully')
                    ->modalHeading('New Consultation')
                    ->label('New Consultation')
                    ->visible(fn () => hasModulePermission($this->permissionModule(), 'create')),
            ])
            ->query(self::GetRecord())
            ->columns($this->getTableColumns())
            ->actions([
                Actions\EditAction::make()
                    ->modalWidth('7xl')
                    ->iconButton()
                    ->mutateRecordDataUsing(function (Model $record, array $data): array {
                        // Get pathology tests - UPDATED to include parameter_id
                        $pathologyTests = ConsultationPathologyTest::where('caseable_type', $this->caseableClass())
                            ->where('caseable_id', $record->caseable_id)
                            ->where('patient_id', $record->patient_id)
                            ->get()
                            ->map(function ($test) {
                                return [
                                    'pathology_category_id' => $test->pathology_category_id,
                                    'pathology_parameter_id' => $test->pathology_parameter_id,
                                    'notes' => $test->notes,
                                ];
                            })
                            ->toArray();
                        $data['pathology_tests'] = $pathologyTests;

                        // Get radiology tests
                        $radiologyTests = ConsultationRadiologyTest::where('caseable_type', $this->caseableClass())
                            ->where('caseable_id', $record->caseable_id)
                            ->where('patient_id', $record->patient_id)
                            ->get()
                            ->toArray();
                        $data['radiology_tests'] = $radiologyTests;

                        return $data;
                    })
                    ->using(function (Model $record, array $data): Model {
                        try {
                            $record->update(Arr::except($data, [
                                'pathology_tests', 
                                'radiology_tests'
                            ]));
                            
                            // Update pathology tests - UPDATED
                            $this->updatePathologyTests($record, $data);
                            
                            // Update radiology tests
                            $this->updateRadiologyTests($record, $data);

                            return $record;
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                            throw $e;
                        }
                    })
                    ->visible(function ($record) {
                        
                        $hasProcessedTests = ConsultationPathologyTest::where('caseable_type', $this->caseableClass())
                            ->where('caseable_id', $record->caseable_id)
                            ->where('patient_id', $record->patient_id)
                            ->whereNotNull('pathology_test_id')
                            ->exists();
                            
                        return !$hasProcessedTests;
                    })
                    ->after(function (Model $record, array $data) {
                        try {
                            Notification::make()
                                ->success()
                                ->title('Consultation updated successfully')
                                ->send();

                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                        }
                    })
                    ->form($this->getEditFormFields())
                    ->successNotificationTitle('Consultation updated successfully')
                    ->visible(fn () => hasModulePermission($this->permissionModule(), 'edit')),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => hasModulePermission($this->permissionModule(), 'delete'))
                    ->using(function (Model $record) {
                        try {
                            if (!canAccessRecord($record, $record->id)) {
                                Notification::make()
                                    ->danger()
                                    ->title('Consultation not found or access denied')
                                    ->send();
                                return;
                            }
                            
                            // Delete related data
                            $this->deleteRelatedData($record);
                            
                            $record->delete();

                            Notification::make()
                                ->success()
                                ->title('Consultation deleted successfully')
                                ->send();

                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(function ($record) {
                        $hasProcessedTests = ConsultationPathologyTest::where('caseable_type', $this->caseableClass())
                            ->where('caseable_id', $record->caseable_id)
                            ->where('patient_id', $record->patient_id)
                            ->whereNotNull('pathology_test_id')
                            ->exists();
                            
                        return !$hasProcessedTests;
                    })
                    ->successNotificationTitle('Consultation deleted successfully'),
            ])
            ->filters([
                //
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading('No consultations found')
            ->emptyStateDescription('Create your first consultation to get started.');
    }

    /**
     * Save pathology tests - UPDATED to match IPD
     */
    private function savePathologyTests(array $data): void
    {
        if (isset($data['pathology_tests']) && is_array($data['pathology_tests'])) {
            foreach ($data['pathology_tests'] as $test) {
                if (!empty($test['pathology_category_id']) && !empty($test['pathology_parameter_id'])) {
                    $parameter = PathologyParameter::find($test['pathology_parameter_id']);
                    
                    ConsultationPathologyTest::create([
                        'patient_id' => $data['patient_id'],
                        'caseable_type' => $this->caseableClass(),
                        'caseable_id' => $data['caseable_id'],
                        'pathology_category_id' => $test['pathology_category_id'],
                        'pathology_parameter_id' => $test['pathology_parameter_id'],
                        'test_name' => $parameter ? $parameter->parameter_name : null, // Store parameter name as test_name
                        'notes' => $test['notes'] ?? null,
                    ]);
                }
            }
        }
    }

    /**
     * Save radiology tests
     */
    private function saveRadiologyTests(array $data): void
    {
        if (isset($data['radiology_tests']) && is_array($data['radiology_tests'])) {
            foreach ($data['radiology_tests'] as $test) {
                if (!empty($test['radiology_category_id']) && !empty($test['test_name'])) {
                    ConsultationRadiologyTest::create([
                        'patient_id' => $data['patient_id'],
                        'caseable_type' => $this->caseableClass(),
                        'caseable_id' => $data['caseable_id'],
                        'radiology_category_id' => $test['radiology_category_id'],
                        'test_name' => $test['test_name'],
                        'notes' => $test['notes'] ?? null,
                    ]);
                }
            }
        }
    }

    /**
     * Update pathology tests - UPDATED to match IPD
     */
    private function updatePathologyTests(Model $record, array $data): void
    {
        // Delete existing pathology tests for this consultation
        ConsultationPathologyTest::where('caseable_type', $this->caseableClass())
            ->where('caseable_id', $record->caseable_id)
            ->where('patient_id', $record->patient_id)
            ->delete();

        // Create new pathology tests
        if (isset($data['pathology_tests']) && is_array($data['pathology_tests'])) {
            foreach ($data['pathology_tests'] as $test) {
                if (!empty($test['pathology_category_id']) && !empty($test['pathology_parameter_id'])) {
                    $parameter = PathologyParameter::find($test['pathology_parameter_id']);
                    
                    ConsultationPathologyTest::create([
                        'patient_id' => $data['patient_id'],
                        'caseable_type' => $this->caseableClass(),
                        'caseable_id' => $record->caseable_id,
                        'pathology_category_id' => $test['pathology_category_id'],
                        'pathology_parameter_id' => $test['pathology_parameter_id'],
                        'test_name' => $parameter ? $parameter->parameter_name : null,
                        'notes' => $test['notes'] ?? null,
                    ]);
                }
            }
        }
    }

    /**
     * Update radiology tests
     */
    private function updateRadiologyTests(Model $record, array $data): void
    {
        // Delete existing radiology tests for this consultation
        ConsultationRadiologyTest::where('caseable_type', $this->caseableClass())
            ->where('caseable_id', $record->caseable_id)
            ->where('patient_id', $record->patient_id)
            ->delete();

        // Create new radiology tests
        if (isset($data['radiology_tests']) && is_array($data['radiology_tests'])) {
            foreach ($data['radiology_tests'] as $test) {
                if (!empty($test['radiology_category_id']) && !empty($test['test_name'])) {
                    ConsultationRadiologyTest::create([
                        'patient_id' => $data['patient_id'],
                        'caseable_type' => $this->caseableClass(),
                        'caseable_id' => $record->caseable_id,
                        'radiology_category_id' => $test['radiology_category_id'],
                        'test_name' => $test['test_name'],
                        'notes' => $test['notes'] ?? null,
                    ]);
                }
            }
        }
    }

    /**
     * Delete related data when consultation is deleted
     */
    private function deleteRelatedData(Model $record): void
    {
        // Delete pathology tests
        ConsultationPathologyTest::where('caseable_type', $this->caseableClass())
            ->where('caseable_id', $record->caseable_id)
            ->where('patient_id', $record->patient_id)
            ->delete();

        // Delete radiology tests
        ConsultationRadiologyTest::where('caseable_type', $this->caseableClass())
            ->where('caseable_id', $record->caseable_id)
            ->where('patient_id', $record->patient_id)
            ->delete();
    }

    /**
     * Render the component inline without a separate view file
     */
    public function render()
    {
        return <<<'blade'
            <div>
                {{ $this->table }}
            </div>
        blade;
    }
}