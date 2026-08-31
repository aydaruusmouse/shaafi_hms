<?php

namespace App\Livewire;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Doctor;
use App\Models\PatientCase;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class DoctorPatientRelationTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public function GetRecord()
    {
        $id = Route::current()->parameter('record');

        $patients = Doctor::with('cases')->where('id', $id)->get();

        foreach ($patients as $item) {
            $this->record = $item->cases;
        }

        $patient_ids = $this->record->pluck('case_id')->toArray();

        $data = PatientCase::whereIn('case_id', $patient_ids)->orderBy('id', 'desc');

        return $data;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(self::GetRecord())
            ->paginated([10, 25, 50])
            ->columns([
                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.case.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->patient->patientUser->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->patientUser->first_name);
                        }
                    })
                    ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.patientUser.full_name')
                    ->label('')
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'" class="hoverLink">'.$record->patient->patientUser->full_name.'</a>')
                    ->html()
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn ($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->searchable(['first_name', 'last_name']),
                PhoneColumn::make('phone')
                    ->label(__('messages.user.phone'))
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(function ($state, $record) {
                        if (str_starts_with($state, '+') && strlen($state) > 4) {
                            return $state;
                        }
                        if (empty($record->patient->user->phone) || empty($record->patient->user->region_code)) {
                            return __('messages.common.n/a');
                        }

                        return $record->patient->user->region_code.$record->patient->user->phone;
                    }),
                TextColumn::make('patient.user.blood_group')
                    ->label(__('messages.user.blood_group'))
                    ->badge()
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if (! empty($record->patient->user->blood_group)) {
                            return $record->patient->user->blood_group;
                        } else {
                            return __('messages.common.n/a');
                        }
                    })
                    ->color(function ($record) {
                        if (! empty($record->patient->user->blood_group)) {
                            return 'success';
                        } else {
                            return 'black';
                        }
                    }),
                TextColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->status == 1) {
                            return __('messages.filter.active');
                        } else {
                            return __('messages.filter.deactive');
                        }
                    })
                    ->color(function ($record) {
                        if ($record->status) {
                            return 'success';
                        } else {
                            return 'danger';
                        }
                    })
                    ->searchable()
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public function render()
    {
        return view('livewire.doctor-patient-relation-table');
    }
}
