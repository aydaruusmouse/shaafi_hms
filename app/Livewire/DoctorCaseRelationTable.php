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

class DoctorCaseRelationTable extends Component implements HasForms, HasTable
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

        $cases_ids = $this->record->pluck('case_id')->toArray();

        $cases = PatientCase::whereIn('case_id', $cases_ids);

        return $cases;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(self::GetRecord())
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('case_id')
                    ->badge()
                    ->color('primary')
                    ->label(__('messages.case.case_id'))
                    ->searchable()
                    ->sortable(),
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
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('date')
                    ->label(__('messages.case.date'))
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        $date = \Carbon\Carbon::parse($record->date)->translatedFormat('jS M, Y');
                        $time = \Carbon\Carbon::parse($record->date)->translatedFormat('g:i A');

                        return "<div class='text-center'><span>{$time}</span><br><span class='text-sm'>{$date}</span></div>";
                    })
                    ->html()
                    ->sortable(),
                TextColumn::make('fee')
                    ->label(__('messages.case.fee'))
                    ->getStateUsing(fn ($record) => getCurrencyFormat($record->fee))
                    ->searchable(),
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
        return view('livewire.doctor-case-relation-table');
    }
}
