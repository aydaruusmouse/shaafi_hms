<?php

namespace App\Livewire;

use App\Models\IpdDiagnosis;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class IpdPatientDiagnosisTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public $id;

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
    }

    public function GetRecord()
    {
        $ipdPatients = IpdDiagnosis::whereIpdPatientDepartmentId($this->id)->orderBy('id', 'desc');

        return $ipdPatients;
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50])
            ->headerActions([
                Actions\CreateAction::make()
                    ->modalWidth('md')
                    ->createAnother(false)
                    ->visible(fn () => hasModulePermission('IPD Diagnosis', 'create'))
                    ->form([
                        Hidden::make('ipd_patient_department_id')->default($this->id),
                        TextInput::make('report_type')
                            ->required()
                            ->label(__('messages.ipd_patient_diagnosis.report_type')),
                        DateTimePicker::make('report_date')
                            ->required()
                            ->native(false)
                            ->default(now()),
                        TextInput::make('description')
                            ->label(__('messages.ipd_patient_diagnosis.description')),
                        SpatieMediaLibraryFileUpload::make('document')
                            ->label(__('messages.ipd_patient_diagnosis.document'))
                            ->collection(IpdDiagnosis::IPD_DIAGNOSIS_PATH)
                            ->disk(config('app.media_disk')),
                    ])
                    ->successNotificationTitle(__('messages.flash.IPD_diagnosis_saved'))
                    ->modalHeading(__('messages.ipd_patient_diagnosis.new_ipd_diagnosis'))
                    ->label(__('messages.ipd_patient_diagnosis.new_ipd_diagnosis')),
            ])
            ->query(self::GetRecord())
            ->columns([
                TextColumn::make('report_type')
                    ->label(__('messages.ipd_patient_diagnosis.report_type'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('report_date')
                    ->label(__('messages.ipd_patient_diagnosis.report_date'))
                    ->searchable()
                    ->formatStateUsing(fn ($record) => \Carbon\Carbon::parse($record->report_date)->translatedFormat('g:i A').'<br>'.\Carbon\Carbon::parse($record->report_date)->translatedFormat('jS M, Y'))
                    ->html()
                    ->extraAttributes(['class' => 'text-center'])
                    ->sortable(),
                TextColumn::make('id')
                    ->label(__('messages.ipd_patient_diagnosis.document'))
                    ->formatStateUsing(function ($record) {
                        // dd($record->ipd_diagnosis_document_url);
                        if (! $record->ipd_diagnosis_document_url) {
                            return __('messages.common.n/a');
                        }

                        return '<a href="'.$record->ipd_diagnosis_document_url.'" download>'.__('messages.document.download').'</a>';
                    })
                    ->color(fn ($record) => $record->ipd_diagnosis_document_url ? 'primary' : '')
                    ->html()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('messages.ipd_patient_diagnosis.description'))
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->modalWidth('md')
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Diagnosis', 'edit'))
                    ->form([
                        TextInput::make('report_type')
                            ->required()
                            ->label(__('messages.ipd_patient_diagnosis.report_type')),
                        DateTimePicker::make('report_date')
                            ->required()
                            ->native(false)
                            ->default(now()),
                        TextInput::make('description')
                            ->label(__('messages.ipd_patient_diagnosis.description')),
                        SpatieMediaLibraryFileUpload::make('document')
                            ->label(__('messages.ipd_patient_diagnosis.document'))
                            ->collection(IpdDiagnosis::IPD_DIAGNOSIS_PATH)
                            ->disk(config('app.media_disk')),
                    ])->successNotificationTitle(__('messages.flash.IPD_diagnosis_updated')),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Diagnosis', 'delete'))
                    ->successNotificationTitle(__('messages.flash.IPD_diagnosis_deleted')),
            ])
            ->filters([
                //
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'))
            ->emptyStateDescription('');
    }

    public function render()
    {
        return view('livewire.ipd-patient-diagnosis-table');
    }
}
