<?php

namespace App\Livewire;

use App\Models\IpdMedicationAdministration;
use App\Models\IpdPrescription;
use App\Models\IpdPrescriptionItem;
use App\Models\Medicine;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class IpdPatientMarTable extends Component implements HasForms, HasTable
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
            Hidden::make('ipd_prescription_item_id')->dehydrated(),
            Hidden::make('medicine_name')->dehydrated(),
            Select::make('medicine_id')
                ->label(__('messages.ipd_patient_mar.medications'))
                ->options(fn () => $this->medicineOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->live()
                ->required()
                ->afterStateUpdated(function ($state, $set) {
                    $item = $this->prescriptionItemForMedicine($state);
                    $medicine = Medicine::find($state);

                    $set('ipd_prescription_item_id', $item?->id);
                    $set('dosage', $item?->dosage ?: null);
                    $set('medicine_name', $medicine?->name ?? $item?->medicine?->name);
                }),
            TextInput::make('dosage')
                ->label(__('messages.ipd_patient_prescription.dosage'))
                ->maxLength(255),
            Select::make('route')
                ->label(__('messages.ipd_patient_mar.route'))
                ->options(IpdMedicationAdministration::routeOptions())
                ->searchable()
                ->native(false)
                ->placeholder(__('messages.ipd_patient_mar.route')),
            DateTimePicker::make('given_at')
                ->label(__('messages.ipd_patient_mar.given_at'))
                ->native(false)
                ->default(now())
                ->required(),
            Select::make('status')
                ->label(__('messages.common.status'))
                ->options(IpdMedicationAdministration::statusOptions())
                ->default(IpdMedicationAdministration::STATUS_GIVEN)
                ->native(false)
                ->required(),
            Select::make('nurse_id')
                ->label(__('messages.ipd_patient_mar.nurse_name'))
                ->options(fn () => getNurseSelectOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->default(fn () => auth()->user()?->hasRole('Nurse') ? auth()->user()->owner_id : null)
                ->required(),
            Textarea::make('notes')
                ->label(__('messages.ipd_patient_mar.notes'))
                ->rows(2)
                ->columnSpanFull(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('messages.ipd_patient_mar.title'))
            ->description(__('messages.ipd_patient_mar.description'))
            ->paginated([10, 25, 50])
            ->headerActions([
                Actions\CreateAction::make()
                    ->createAnother(false)
                    ->modalWidth('lg')
                    ->visible(fn () => hasModulePermission('IPD Nurse Notes', 'create'))
                    ->form($this->formFields())
                    ->modalHeading(__('messages.ipd_patient_mar.new_record'))
                    ->label(__('messages.ipd_patient_mar.new_record'))
                    ->mutateFormDataUsing(fn (array $data) => $this->prepareMarData($data))
                    ->successNotificationTitle(__('messages.ipd_patient_mar.saved')),
            ])
            ->query(IpdMedicationAdministration::query()->where('ipd_patient_department_id', $this->id)->orderByDesc('given_at')->orderByDesc('id'))
            ->columns([
                TextColumn::make('medicine_name')
                    ->label(__('messages.ipd_patient_mar.medications'))
                    ->getStateUsing(fn ($record) => $record->medicine_name ?: ($record->medicine->name ?? __('messages.common.n/a')))
                    ->searchable(),
                TextColumn::make('dosage')
                    ->label(__('messages.ipd_patient_prescription.dosage'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('route')
                    ->label(__('messages.ipd_patient_mar.route'))
                    ->formatStateUsing(fn ($state) => IpdMedicationAdministration::routeOptions()[$state] ?? ($state ?: __('messages.common.n/a')))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('given_at')
                    ->label(__('messages.ipd_patient_mar.given_at'))
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->translatedFormat('jS M, Y h:i A') : __('messages.common.n/a'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => IpdMedicationAdministration::statusOptions()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        IpdMedicationAdministration::STATUS_GIVEN => 'success',
                        IpdMedicationAdministration::STATUS_HELD => 'warning',
                        IpdMedicationAdministration::STATUS_REFUSED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('nurse.user.full_name')
                    ->label(__('messages.ipd_patient_mar.nurse_name'))
                    ->default(__('messages.common.n/a'))
                    ->searchable(),
                TextColumn::make('notes')
                    ->label(__('messages.ipd_patient_mar.notes'))
                    ->limit(40)
                    ->default(__('messages.common.n/a')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('messages.common.status'))
                    ->options(IpdMedicationAdministration::statusOptions())
                    ->native(false),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->iconButton()
                    ->modalWidth('lg')
                    ->visible(fn () => hasModulePermission('IPD Nurse Notes', 'edit'))
                    ->form($this->formFields())
                    ->mutateFormDataUsing(fn (array $data) => $this->prepareMarData($data))
                    ->successNotificationTitle(__('messages.ipd_patient_mar.updated')),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Nurse Notes', 'delete'))
                    ->successNotificationTitle(__('messages.ipd_patient_mar.deleted')),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'))
            ->emptyStateDescription('');
    }

    public function render()
    {
        return view('livewire.ipd-patient-mar-table');
    }

    private function medicineOptions(): array
    {
        $prescribed = $this->prescriptionItems()
            ->filter(fn ($item) => $item->medicine_id)
            ->unique('medicine_id');

        if ($prescribed->isNotEmpty()) {
            return $prescribed->mapWithKeys(function ($item) {
                $name = $item->medicine->name ?? __('messages.common.n/a');
                $dose = $item->dosage ? ' — '.$item->dosage : '';

                return [$item->medicine_id => $name.$dose];
            })->toArray();
        }

        return Medicine::where('tenant_id', getLoggedInUser()->tenant_id)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    private function prescriptionItems()
    {
        $prescriptionIds = IpdPrescription::where('ipd_patient_department_id', $this->id)->pluck('id');

        return IpdPrescriptionItem::with('medicine')
            ->whereIn('ipd_prescription_id', $prescriptionIds)
            ->get();
    }

    private function prescriptionItemForMedicine($medicineId): ?IpdPrescriptionItem
    {
        if (! $medicineId) {
            return null;
        }

        return $this->prescriptionItems()
            ->where('medicine_id', (int) $medicineId)
            ->sortByDesc('id')
            ->first();
    }

    private function prepareMarData(array $data): array
    {
        $data['ipd_patient_department_id'] = $this->id;

        if (empty($data['medicine_name']) && ! empty($data['medicine_id'])) {
            $data['medicine_name'] = Medicine::where('id', $data['medicine_id'])->value('name');
        }

        if (empty($data['ipd_prescription_item_id']) && ! empty($data['medicine_id'])) {
            $data['ipd_prescription_item_id'] = $this->prescriptionItemForMedicine($data['medicine_id'])?->id;
        }

        return $data;
    }
}
