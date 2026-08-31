<?php

namespace App\Livewire;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\DoctorDepartment;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class DoctorDepartmentRelationTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public function GetRecord()
    {
        $id = Route::current()->parameter('record');

        $DoctorDepartment = DoctorDepartment::with('doctors')->where('id', $id)->get();
        // dd($DoctorDepartment);
        foreach ($DoctorDepartment as $item) {
            $this->record = $item->doctors;
        }

        $doctors_ids = $this->record->pluck('doctor_department_id')->toArray();
        $data = Doctor::with('user')->whereIn('doctor_department_id', $doctors_ids);

        return $data;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(self::GetRecord())
            ->paginated([10, 25, 50])
            ->columns([
                SpatieMediaLibraryImageColumn::make('user.profile')
                    ->label(__('messages.patient_admission.doctor'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        // dd($record);
                        if (! $record->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->user->full_name);
                        }
                    })
                    ->url(fn ($record) => DoctorResource::getUrl('view', ['record' => $record->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('user.full_name')
                    ->label('')
                    ->html()
                    ->formatStateUsing(fn ($record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->id]).'" class="hoverLink">'.$record->user->full_name.'</a>')
                    ->color('primary')
                    ->description(fn ($record) => $record->user->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('specialist')
                    ->label(__('messages.doctor.specialist'))
                    ->default(__('messages.common.n/a'))
                    ->searchable(),
                PhoneColumn::make('user.phone')
                    ->label(__('messages.case.phone'))
                    ->default(__('messages.common.n/a'))
                    ->searchable(),
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
        return view('livewire.doctor-department-relation-table');
    }
}
