<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorDepartmentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorDepartmentResource;
use App\Livewire\DoctorDepartmentRelationTable;
use Filament\Actions;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewDoctorDepartment extends ViewRecord
{
    protected static string $resource = DoctorDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->modalWidth('md')->successNotificationTitle(__('messages.flash.department_updated')),
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(url()->previous()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()->schema([
                    TextEntry::make('title')
                        ->label(__('messages.appointment.doctor_department').':'),
                    TextEntry::make('created_at')
                        ->formatStateUsing(fn ($state) => $state->diffForHumans())
                        ->label(__('messages.common.created_on').':'),
                    TextEntry::make('updated_at')
                        ->formatStateUsing(fn ($state) => $state->diffForHumans())
                        ->label(__('messages.common.last_updated').':'),
                    TextEntry::make('description')
                        ->default(__('messages.common.n/a'))
                        ->label(__('messages.doctor_department.description').':'),
                ])->columns(2),
                Section::make(__('messages.doctors'))->schema([
                    Livewire::make(DoctorDepartmentRelationTable::class),
                ]),
            ]);
    }
}
