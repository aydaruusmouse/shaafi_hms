<?php

namespace App\Filament\HospitalAdmin\Clusters\Reports\Resources\BirthReportResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Reports\Resources\BirthReportResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBirthReports extends ViewRecord
{
    protected static string $resource = BirthReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
                        TextEntry::make('patient.user.full_name')
                            ->label(__('messages.case.patient').':')
                            ->default(__('messages.common.n/a')),
                        TextEntry::make('case_id')
                            ->label(__('messages.case.case_id').':')
                            ->badge()
                            ->color(fn ($record) => $record->case_id ? 'info' : '')
                            ->default(__('messages.common.n/a')),
                        TextEntry::make('doctor.user.full_name')
                            ->label(__('messages.case.doctor').':')
                            ->default(__('messages.common.n/a')),
                        TextEntry::make('date')
                            ->label(__('messages.death_report.date').':')
                            ->default(__('messages.common.n/a'))
                            ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('jS M, Y g:i A')),
                        TextEntry::make('description')
                            ->default(__('messages.common.n/a'))
                            ->formatStateUsing(fn ($state) => ! empty($state) ? nl2br(e($state)) : __('messages.common.n/a'))
                            ->extraAttributes(['style' => 'word-break: break-all;'])
                            ->label(__('messages.death_report.description').':'),
                        TextEntry::make('created_at')
                            ->label(__('messages.common.created_at').':')
                            ->default(__('messages.common.n/a'))
                            ->since(),
                        TextEntry::make('updated_at')
                            ->label(__('messages.common.last_updated').':')
                            ->default(__('messages.common.n/a'))
                            ->since(),
                    ])->columns(2),
            ]);
    }
}
