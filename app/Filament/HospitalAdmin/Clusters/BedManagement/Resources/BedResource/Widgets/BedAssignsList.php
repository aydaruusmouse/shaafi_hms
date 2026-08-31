<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedResource\Widgets;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\IpdPatientDepartment;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BedAssignsList extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public $record;

    public function mount($record)
    {
        $this->record = $record->id;
    }

    protected function getTableHeading(): string
    {
        return __('messages.bed_assigns');
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50])
            ->query(
                IpdPatientDepartment::where('bed_id', $this->record)
            )
            ->columns([
                TextColumn::make('patientCase.case_id')
                    ->label(__('messages.bed_assign.case_id'))
                    ->searchable()
                    ->sortable()
                    ->badge(),
                SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                    ->label(__('messages.role.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
                    ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.patientUser.full_name')
                    ->label('')
                    ->color('primary')
                    ->description(fn ($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->action(function ($record) {
                        Action::make()->url(route('filament.hospitalAdmin.patients.resources.patients.view', $record->patient->id));
                    })
                    ->color('primary')
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'" class="hoverLink">'.$record->patient->patientUser->full_name.'</a>')
                    ->html()
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('bedAssign.assign_date')
                    ->label(__('messages.bed_assign.assign_date'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(
                        fn ($state) => Carbon::parse($state)->format('g:i A').'<br>'.Carbon::parse($state)->format('jS M, Y')
                    )
                    ->html(),
                TextColumn::make('bedAssign.discharge_date')
                    ->label(__('messages.bed_assign.discharge_date'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(
                        fn ($state) => Carbon::parse($state)->format('g:i A').'<br>'.Carbon::parse($state)->format('jS M, Y')
                    )
                    ->html(),
                TextColumn::make('bedAssign.status')
                    ->label(__('messages.common.status'))
                    ->formatStateUsing(function ($record) {
                        return $record->bedAssign->status == 1 ? __('messages.bed_assign.assigned') : __('messages.bed_assign.not_assigned');
                    })
                    ->badge()
                    ->color(function ($record) {
                        return $record->bedAssign->status == 1 ? 'success' : 'danger';
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }
}
