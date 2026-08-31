<?php

namespace App\Filament\HospitalAdmin\Clusters\Reports\Resources\InvestigationReportResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Reports\Resources\InvestigationReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvestigationReport extends CreateRecord
{
    protected static string $resource = InvestigationReportResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.investigation_report_saved');
    }
}
