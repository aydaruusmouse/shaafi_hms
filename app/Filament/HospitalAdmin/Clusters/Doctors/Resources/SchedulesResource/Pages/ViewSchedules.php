<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\SchedulesResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\SchedulesResource;
use App\Livewire\ViewSchedulesTable;
use Filament\Actions;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSchedules extends ViewRecord
{
    protected static string $resource = SchedulesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
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
                Section::make()
                    ->schema([
                        TextEntry::make('doctor.user.full_name')
                            ->label(__('messages.case.doctor').':'),
                        TextEntry::make('per_patient_time')
                            ->label(__('messages.schedule.per_patient_time').':')
                            ->formatStateUsing(fn ($record) => date('H:i', strtotime($record->per_patient_time))),
                        TextEntry::make('created_on')
                            ->label(__('messages.common.created_on').':')
                            ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a')),
                        TextEntry::make('updated_on')
                            ->label(__('messages.common.last_updated').':')
                            ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a')),
                    ])->columns(2),
                Section::make(__('messages.schedule_label'))
                    ->schema([
                        Livewire::make(ViewSchedulesTable::class),
                    ])->columnSpanFull(),
            ]);
    }
}
