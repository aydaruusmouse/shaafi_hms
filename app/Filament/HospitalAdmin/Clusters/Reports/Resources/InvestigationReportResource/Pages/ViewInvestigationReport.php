<?php

namespace App\Filament\HospitalAdmin\Clusters\Reports\Resources\InvestigationReportResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Reports\Resources\InvestigationReportResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInvestigationReport extends ViewRecord
{
    protected static string $resource = InvestigationReportResource::class;

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
                        TextEntry::make('doctor.user.full_name')
                            ->label(__('messages.case.doctor').':')
                            ->default(__('messages.common.n/a')),
                        TextEntry::make('date')
                            ->label(__('messages.death_report.date').':')
                            ->default(__('messages.common.n/a'))
                            ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('jS M, Y g:i A')),
                        TextEntry::make('title')
                            ->label(__('messages.investigation_report.title').':')
                            ->default(__('messages.common.n/a')),
                        TextEntry::make('description')
                            ->default(__('messages.common.n/a'))
                            ->formatStateUsing(fn ($state) => ! empty($state) ? nl2br(e($state)) : __('messages.common.n/a'))
                            ->extraAttributes(['style' => 'word-break: break-all;'])
                            ->label(__('messages.investigation_report.description').':'),
                        TextEntry::make('attachment_url')
                            ->default(__('messages.common.n/a'))
                            ->formatStateUsing(fn ($record) => $record->attachment_url ? '<a href="'.$record->attachment_url.'" target="_blank">'.__('messages.common.view').'</a>' : __('messages.common.n/a'))
                            ->html()
                            ->color(fn ($record) => $record->attachment_url ? 'primary' : '')
                            ->label(__('messages.investigation_report.attachment').':'),
                        TextEntry::make('status')
                            ->label(__('messages.common.status').':')
                            ->badge()
                            ->formatStateUsing(fn ($record) => $record->status == 1 ? __('messages.investigation_report.solved') : __('messages.investigation_report.not_solved'))
                            ->color(fn ($record) => $record->status == 1 ? 'success' : 'danger')
                            ->default(__('messages.common.n/a')),
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
