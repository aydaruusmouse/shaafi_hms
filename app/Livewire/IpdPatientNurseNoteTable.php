<?php

namespace App\Livewire;

use App\Models\IpdNurseNote;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class IpdPatientNurseNoteTable extends Component implements HasForms, HasTable
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
            Select::make('nurse_id')
                ->label(__('messages.ipd_patient_nurse_note.nurse'))
                ->options(fn () => getNurseSelectOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->required(),
            DateTimePicker::make('note_date')
                ->label(__('messages.ipd_patient_nurse_note.note_date'))
                ->native(false)
                ->default(now())
                ->required(),
            Textarea::make('note')
                ->label(__('messages.ipd_patient_nurse_note.note'))
                ->rows(4)
                ->required(),
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
                    ->visible(fn () => hasModulePermission('IPD Nurse Notes', 'create'))
                    ->form($this->formFields())
                    ->modalHeading(__('messages.ipd_patient_nurse_note.new_note'))
                    ->label(__('messages.ipd_patient_nurse_note.new_note'))
                    ->successNotificationTitle(__('messages.ipd_patient_nurse_note.saved')),
            ])
            ->query(IpdNurseNote::query()->where('ipd_patient_department_id', $this->id)->orderByDesc('id'))
            ->columns([
                TextColumn::make('nurse.user.full_name')
                    ->label(__('messages.ipd_patient_nurse_note.nurse'))
                    ->default(__('messages.common.n/a'))
                    ->searchable(),
                TextColumn::make('note_date')
                    ->label(__('messages.ipd_patient_nurse_note.note_date'))
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->translatedFormat('jS M, Y h:i A') : __('messages.common.n/a'))
                    ->sortable(),
                TextColumn::make('note')
                    ->label(__('messages.ipd_patient_nurse_note.note'))
                    ->limit(60)
                    ->searchable(),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->iconButton()
                    ->modalWidth('lg')
                    ->visible(fn () => hasModulePermission('IPD Nurse Notes', 'edit'))
                    ->form($this->formFields())
                    ->successNotificationTitle(__('messages.ipd_patient_nurse_note.updated')),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Nurse Notes', 'delete'))
                    ->successNotificationTitle(__('messages.ipd_patient_nurse_note.deleted')),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'))
            ->emptyStateDescription('');
    }

    public function render()
    {
        return view('livewire.ipd-patient-nurse-note-table');
    }
}
