<?php

namespace App\Livewire;

use App\Models\IpdOperation;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class IpdPatientOperationTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $id;

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
    }

    public function formFields(): array
    {
        return [
            Hidden::make('ipd_patient_department_id')->default($this->id),
            TextInput::make('operation_name')
                ->label(__('messages.ipd_patient_operation.operation_name'))
                ->required()
                ->maxLength(255),
            DateTimePicker::make('operation_date')
                ->label(__('messages.ipd_patient_operation.operation_date'))
                ->native(false)
                ->default(now())
                ->required(),
            Select::make('surgeon_id')
                ->label(__('messages.ipd_patient_operation.surgeon'))
                ->options(fn () => getDoctorSelectOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->required(),
            Select::make('assistant_id')
                ->label(__('messages.ipd_patient_operation.assistant'))
                ->options(fn () => getDoctorSelectOptions())
                ->searchable()
                ->preload()
                ->native(false),
            Select::make('anesthetist_id')
                ->label(__('messages.ipd_patient_operation.anesthetist'))
                ->options(fn () => getDoctorSelectOptions())
                ->searchable()
                ->preload()
                ->native(false),
            Textarea::make('notes')
                ->label(__('messages.ipd_patient_operation.notes'))
                ->rows(3),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50])
            ->headerActions([
                Actions\CreateAction::make()
                    ->createAnother(false)
                    ->modalWidth('lg')
                    ->visible(fn () => hasModulePermission('IPD Operations', 'create'))
                    ->form($this->formFields())
                    ->modalHeading(__('messages.ipd_patient_operation.new_operation'))
                    ->label(__('messages.ipd_patient_operation.new_operation'))
                    ->successNotificationTitle(__('messages.ipd_patient_operation.saved')),
            ])
            ->query(IpdOperation::query()->where('ipd_patient_department_id', $this->id)->orderByDesc('id'))
            ->columns([
                TextColumn::make('operation_name')
                    ->label(__('messages.ipd_patient_operation.operation_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('operation_date')
                    ->label(__('messages.ipd_patient_operation.operation_date'))
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->translatedFormat('jS M, Y h:i A') : __('messages.common.n/a'))
                    ->sortable(),
                TextColumn::make('surgeon.doctorUser.full_name')
                    ->label(__('messages.ipd_patient_operation.surgeon'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('assistant.doctorUser.full_name')
                    ->label(__('messages.ipd_patient_operation.assistant'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('anesthetist.doctorUser.full_name')
                    ->label(__('messages.ipd_patient_operation.anesthetist'))
                    ->default(__('messages.common.n/a')),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->iconButton()
                    ->modalWidth('lg')
                    ->visible(fn () => hasModulePermission('IPD Operations', 'edit'))
                    ->form($this->formFields())
                    ->successNotificationTitle(__('messages.ipd_patient_operation.updated')),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Operations', 'delete'))
                    ->successNotificationTitle(__('messages.ipd_patient_operation.deleted')),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'))
            ->emptyStateDescription('');
    }

    public function render()
    {
        return view('livewire.ipd-patient-operation-table');
    }
}
