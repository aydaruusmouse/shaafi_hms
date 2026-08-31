<?php

namespace App\Filament\HospitalAdmin\Clusters\IpdOpd\Widgets;

use App\Models\VitalSign;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VitalSignTable extends TableWidget
{
    protected static ?string $heading = 'Vital Information';

    public string $ipdOpdNumber;
    public string $type; // 'IPD' or 'OPD'

    protected function vitalPermissionModule(): string
    {
        return $this->type === 'IPD' ? 'IPD Vital Information' : 'OPD Vital Information';
    }

    protected function getTableQuery(): Builder
    {
        return VitalSign::query()
            ->where('tenant_id', getLoggedInUser()->tenant_id)
            ->where(function ($query) {
                // Original condition - get records by ipd_opd_number and type
                $query->where('ipd_opd_number', $this->ipdOpdNumber)
                      ->where('type', $this->type);
                
                // Additional condition - get records linked through case_id from IPD/OPD
                if ($this->type == 'IPD') {
                    $query->orWhere(function ($subQuery) {
                        $subQuery->where('type', 'Case')
                                ->whereExists(function ($existsQuery) {
                                    $existsQuery->select(DB::raw(1))
                                        ->from('ipd_patient_departments')
                                        ->where('ipd_patient_departments.ipd_number', $this->ipdOpdNumber)
                                        ->whereColumn('ipd_patient_departments.case_id', 'vital_signs.case_id');
                                });
                    })->orWhere(function ($subQuery) {
                        $subQuery->where('type', 'OPD')
                            ->whereExists(function ($existsQuery) {
                                $existsQuery->select(DB::raw(1))
                                    ->from('ipd_patient_departments')
                                    ->join('opd_patient_departments', 'opd_patient_departments.case_id', '=', 'ipd_patient_departments.case_id')
                                    ->where('ipd_patient_departments.ipd_number', $this->ipdOpdNumber)
                                    ->whereColumn('opd_patient_departments.opd_number', 'vital_signs.ipd_opd_number');
                            });
                    });
                } 
                elseif ($this->type == 'OPD') {
                    $query->orWhere(function ($subQuery) {
                        $subQuery->where('type', 'Case')
                                ->whereExists(function ($existsQuery) {
                                    $existsQuery->select(DB::raw(1))
                                        ->from('opd_patient_departments')
                                        ->where('opd_patient_departments.opd_number', $this->ipdOpdNumber)
                                        ->whereColumn('opd_patient_departments.case_id', 'vital_signs.case_id');
                                });
                    })->orWhere(function ($subQuery) {
                        $subQuery->where('type', 'Appointment')
                            ->whereExists(function ($existsQuery) {
                                $existsQuery->select(DB::raw(1))
                                    ->from('opd_patient_departments')
                                    ->where('opd_patient_departments.opd_number', $this->ipdOpdNumber)
                                    ->whereColumn('opd_patient_departments.patient_id', 'vital_signs.patient_id');
                            });
                    });
                }
            });
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('created_at')
                ->label('Date & Time')
                ->dateTime('M j, Y g:i A'),
            Tables\Columns\TextColumn::make('temperature')
                ->label('Temperature (°C)')
                ->placeholder('Not recorded'),
            Tables\Columns\TextColumn::make('height')
                ->label('Height (cm)')
                ->placeholder('Not recorded'),
            Tables\Columns\TextColumn::make('weight')
                ->label('Weight (kg)')
                ->placeholder('Not recorded'),
            Tables\Columns\TextColumn::make('pulse_rate')
                ->label('Pulse Rate (bpm)')
                ->placeholder('Not recorded'),
            Tables\Columns\TextColumn::make('blood_pressure')
                ->label('Blood Pressure')
                ->placeholder('Not recorded'),
            Tables\Columns\TextColumn::make('respiratory_rate')
                ->label('Respiratory Rate (br/min)')
                ->placeholder('Not recorded'),
            Tables\Columns\TextColumn::make('oxygen_saturation')
                ->label('O₂ Saturation (%)')
                ->placeholder('Not recorded'),
            Tables\Columns\TextColumn::make('random_blood_sugar')
                ->label('Random Sugar (mg/dl)')
                ->placeholder('Not recorded'),
            Tables\Columns\TextColumn::make('fasting_blood_sugar')
                ->label('Fasting Sugar (mg/dl)')
                ->placeholder('Not recorded'),
            Tables\Columns\TextColumn::make('drug_allergies')
                ->label('Drug Allergies')
                ->placeholder('None recorded')
                ->limit(50),
            Tables\Columns\TextColumn::make('created_at')
                ->since()
                ->label('Recorded'),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        /*if (! canModule('Vital Information', 'create')) {
            return [];
        }*/

        return [
            Tables\Actions\CreateAction::make()
                ->label('Add Vital Sign')
                ->modalHeading('Add Vital Sign')
                ->modalWidth('5xl')
                ->form($this->getCreateFormSchema())
                ->mutateFormDataUsing(function (array $data): array {
                    $data['ipd_opd_number'] = $this->ipdOpdNumber;
                    $data['type'] = $this->type;
                    $data['tenant_id'] = getLoggedInUser()->tenant_id;
                    return $data;
                })
                ->visible(fn () => hasModulePermission($this->vitalPermissionModule(), 'create')),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\EditAction::make()
                ->form($this->getEditFormSchema())
                ->visible(fn () => hasModulePermission($this->vitalPermissionModule(), 'edit')),
            Tables\Actions\DeleteAction::make()
                ->visible(fn () => hasModulePermission($this->vitalPermissionModule(), 'delete')),
        ];
    }

    protected function getCreateFormSchema(): array
    {
        return [
            

            Forms\Components\Section::make('Vital Signs')
                ->schema([
                    Forms\Components\TextInput::make('blood_pressure')
                        ->label('Blood Pressure (mmHg)')
                        ->placeholder('e.g., 120/80')
                        ->string()
                        ->nullable()
                        ->maxLength(300),
                    
                    Forms\Components\TextInput::make('pulse_rate')
                        ->label('Pulse Rate (bpm)')
                        ->numeric()
                        ->nullable()
                        ->minValue(1),
                    
                    Forms\Components\TextInput::make('respiratory_rate')
                        ->label('Respiratory Rate (br/min)')
                        ->numeric()
                        ->nullable()
                        ->minValue(1),
                    
                    Forms\Components\TextInput::make('oxygen_saturation')
                        ->label('Oxygen Saturation (%)')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->maxValue(100),
                    
                    Forms\Components\TextInput::make('temperature')
                        ->label('Temperature (°C)')
                        ->numeric()
                        ->nullable()
                        ->minValue(30)
                        ->maxValue(200),
                ])
                ->columns(2),
            Forms\Components\Section::make('Physical Measurements')
                ->schema([
                    Forms\Components\TextInput::make('height')
                        ->label('Height (cm)')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->maxValue(300)
                        ->suffix('cm'),
                    
                    Forms\Components\TextInput::make('weight')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->maxValue(500)
                        ->suffix('kg'),
                ])
                ->columns(2),
            Forms\Components\Section::make('Additional Information')
                ->schema([
                    Forms\Components\TextInput::make('random_blood_sugar')
                        ->label('Random Blood Sugar (mg/dl)')
                        ->numeric()
                        ->nullable()
                        ->minValue(0),
                    
                    Forms\Components\TextInput::make('fasting_blood_sugar')
                        ->label('Fasting Blood Sugar (mg/dl)')
                        ->numeric()
                        ->nullable()
                        ->minValue(0),
                    
                    Forms\Components\Textarea::make('drug_allergies')
                        ->label('Drug Allergies')
                        ->rows(2)
                        ->nullable()
                        ->string(),
                ])
                ->columns(2),
        ];
    }

    protected function getEditFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Vital Signs')
                ->schema([
                    Forms\Components\TextInput::make('blood_pressure')
                        ->label('Blood Pressure (mmHg)')
                        ->placeholder('e.g., 120/80')
                        ->string()
                        ->nullable()
                        ->maxLength(300),
                    
                    Forms\Components\TextInput::make('pulse_rate')
                        ->label('Pulse Rate (bpm)')
                        ->numeric()
                        ->nullable()
                        ->minValue(1),
                    
                    Forms\Components\TextInput::make('respiratory_rate')
                        ->label('Respiratory Rate (br/min)')
                        ->numeric()
                        ->nullable()
                        ->minValue(1),
                    
                    Forms\Components\TextInput::make('oxygen_saturation')
                        ->label('Oxygen Saturation (%)')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->maxValue(100),
                    
                    Forms\Components\TextInput::make('temperature')
                        ->label('Temperature (°C)')
                        ->numeric()
                        ->nullable()
                        ->minValue(30)
                        ->maxValue(200),
                ])
                ->columns(2),
             Forms\Components\Section::make('Physical Measurements')
                ->schema([
                    Forms\Components\TextInput::make('height')
                        ->label('Height (cm)')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->maxValue(300)
                        ->suffix('cm'),
                    
                    Forms\Components\TextInput::make('weight')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->maxValue(500)
                        ->suffix('kg'),
                ])
                ->columns(2),

           
            Forms\Components\Section::make('Additional Information')
                ->schema([
                    Forms\Components\TextInput::make('random_blood_sugar')
                        ->label('Random Blood Sugar (mg/dl)')
                        ->numeric()
                        ->nullable()
                        ->minValue(0),
                    
                    Forms\Components\TextInput::make('fasting_blood_sugar')
                        ->label('Fasting Blood Sugar (mg/dl)')
                        ->numeric()
                        ->nullable()
                        ->minValue(0),
                    
                    Forms\Components\Textarea::make('drug_allergies')
                        ->label('Drug Allergies')
                        ->rows(2)
                        ->nullable()
                        ->string(),
                ])
                ->columns(2),
        ];
    }
}