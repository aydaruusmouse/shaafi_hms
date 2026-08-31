<?php

namespace App\Livewire;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientAdmissionResource;
use App\Models\PatientAdmission;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class PatientAdmissionRelationTable extends Component implements HasForms, HasTable
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
        $PatientAdmission = PatientAdmission::where('patient_id', $this->id)->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc');

        return $PatientAdmission;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->GetRecord())
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('patient_admission_id')
                    ->badge()
                    ->color('primary')
                    ->label(__('messages.bill.admission_id'))
                    // ->url(fn($record) => PatientAdmissionResource::getUrl('view', ['record' => $record->id]))
                    ->searchable()
                    ->sortable(),
                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.patient_admission.doctor'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->doctor->user->full_name);
                        }
                    })
                    ->url(fn ($record) => auth()->user()->hasRole('Patient') ? '' : DoctorResource::getUrl('view', ['record' => $record->doctor->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('doctor.doctorUser.full_name')
                    ->label('')
                    ->html()
                    ->formatStateUsing(fn ($record) => auth()->user()->hasRole('Patient') ? $record->doctor->doctorUser->full_name : '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'" class="hoverLink">'.$record->doctor->doctorUser->full_name.'</a>')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('admission_date')
                    ->label(__('messages.patient_admission.admission_date'))
                    ->searchable()
                    ->sortable()
                    ->html()
                    ->extraAttributes(['class' => 'text-center'])
                    ->formatStateUsing(function ($record) {
                        return \Carbon\Carbon::parse($record->admission_date)->translatedFormat('g:i A').'<br>'.\Carbon\Carbon::parse($record->admission_date)->translatedFormat('jS M, Y');
                    }),
                TextColumn::make('discharge_date')
                    ->label(__('messages.patient_admission.discharge_date'))
                    ->searchable()
                    ->html()
                    ->extraAttributes(['class' => 'text-center'])
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(function ($record) {
                        return $record->discharge_date === null ? __('messages.common.n/a') : \Carbon\Carbon::parse($record->discharge_date)->translatedFormat('g:i A').'<br>'.\Carbon\Carbon::parse($record->discharge_date)->translatedFormat('jS M, Y');
                    }),
                TextColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        return $record->status === 1 ? __('messages.common.active') : __('messages.common.de_active');
                    })
                    ->color(function ($record) {
                        return $record->status === 1 ? 'success' : 'danger';
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->actionsColumnLabel(function () {
                if (auth()->user()->hasRole('Patient')) {
                    return null;
                }

                return __('messages.common.actions');
            })
            ->actions([
                Actions\EditAction::make()
                    ->url(fn ($record) => PatientAdmissionResource::getUrl('edit', ['record' => $record->id]))
                    ->visible(function () {
                        if (auth()->user()->hasRole('Patient')) {
                            return false;
                        }

                        return true;
                    })
                    ->iconButton(),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(function () {
                        if (auth()->user()->hasRole('Patient')) {
                            return false;
                        }

                        return true;
                    })
                    ->successNotificationTitle(__('messages.flash.patient_admission_deleted')),
            ])
            ->filters([
                //
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public function render()
    {
        return view('livewire.patient-admission-relation-table');
    }
}
