<?php

namespace App\Livewire;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\OpdPatientResource as ResourcesOpdPatientResource;
use App\Models\OpdPatientDepartment;
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

class OpdPatientVisitTable extends Component implements HasForms, HasTable
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
        $OpdPatientDepartment_id = OpdPatientDepartment::with('patient')->where('id', $this->id)->orderBy('id', 'desc')->get()->toArray()[0]['patient_id'];
        $OpdPatientDepartment = OpdPatientDepartment::where('patient_id', $OpdPatientDepartment_id)->orderBy('id', 'desc');

        return $OpdPatientDepartment;
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Actions\CreateAction::make()
                    ->visible(fn () => hasModulePermission('OPD Visits', 'create'))
                    ->url(ResourcesOpdPatientResource::getUrl('create', ['revisit' => $this->id]))
                    ->label(__('messages.opd_patient.revisits')),
            ])
            ->query($this->GetRecord())
            ->columns([
                TextColumn::make('opd_number')
                    ->label(__('messages.opd_patient.opd_number'))
                    ->badge()
                    ->color('info')
                    ->url(fn ($record) => ResourcesOpdPatientResource::getUrl('view', ['record' => $record->id]))
                    ->default(__('messages.common.n/a')),
                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.patient_admission.doctor'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->doctor->user->full_name);
                        }
                    })
                    ->url(fn ($record) => DoctorResource::getUrl('view', ['record' => $record->doctor->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('doctor.doctorUser.full_name')
                    ->label('')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'" class="hoverLink">'.$record->doctor->user->full_name.'</a>')
                    ->html()
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('appointment_date')
                    ->label(__('messages.opd_patient.appointment_date'))
                    ->html()
                    ->extraAttributes(['class' => 'text-center'])
                    ->formatStateUsing(function ($state) {
                        return \Carbon\Carbon::parse($state)->translatedFormat('g:i A').'<br>'.\Carbon\Carbon::parse($state)->translatedFormat('jS M, Y');
                    }),
                TextColumn::make('standard_charge')
                    ->label(__('messages.doctor_opd_charge.standard_charge'))
                    ->formatStateUsing(fn ($record) => getCurrencyFormat($record->standard_charge) ?? __('messages.common.n/a')),
                TextColumn::make('payment_mode')
                    ->label(__('messages.ipd_payments.payment_mode'))
                    ->formatStateUsing(function ($state) {
                        if ($state == 1) {
                            return __('messages.transaction_filter.cash');
                        } elseif ($state == 2) {
                            return __('messages.transaction_filter.cheque');
                        }
                    }),
                TextColumn::make('symptoms')
                    ->label(__('messages.ipd_patient.symptoms'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('notes')
                    ->label(__('messages.ipd_patient.notes'))
                    ->default(__('messages.common.n/a')),
            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->paginated(false)
            ->actions([
                Actions\EditAction::make()
                    ->modalWidth('xl')
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('OPD Visits', 'edit'))
                    ->url(function ($record) {
                        return ResourcesOpdPatientResource::getUrl('edit', ['record' => $record->id]);
                    })
                    ->successNotificationTitle(__('messages.flash.OPD_timeline_updated')),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('OPD Visits', 'delete'))
                    ->modalHeading(__('messages.common.delete').' '.__('messages.opd_patient.opd_patient'))
                    ->successNotificationTitle(__('messages.flash.OPD_timeline_deleted')),
            ])
            ->filters([
                //
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'))
            ->emptyStateDescription('');
    }

    public function render()
    {
        return view('livewire.opd-patient-visit-table');
    }
}
