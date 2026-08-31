<?php

namespace App\Filament\Resources\SuperAdminEnquiryResource\Pages;

use App\Filament\Resources\SuperAdminEnquiryResource;
use App\Models\SuperAdminEnquiry;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSuperAdminEnquiry extends ViewRecord
{
    protected static string $resource = SuperAdminEnquiryResource::class;

    public function getTitle(): string
    {
        return __('messages.enquiry.enquiry_details');
    }

    public function mount(string|int $record): void
    {
        $this->record = SuperAdminEnquiry::findOrFail($record);
        $this->record->status = SuperAdminEnquiry::READ;
        $this->record->save();
    }

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
