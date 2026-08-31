<?php

namespace App\Filament\HospitalAdmin\Clusters\Reports\Resources\InvestigationReportResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Reports\Resources\InvestigationReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvestigationReport extends EditRecord
{
    protected static string $resource = InvestigationReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.investigation_report_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
