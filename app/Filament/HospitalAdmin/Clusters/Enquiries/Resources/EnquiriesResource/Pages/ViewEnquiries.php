<?php

namespace App\Filament\HospitalAdmin\Clusters\Enquiries\Resources\EnquiriesResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Enquiries\Resources\EnquiriesResource;
use App\Models\Enquiry;
use App\Models\SuperAdminEnquiry;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewEnquiries extends ViewRecord
{
    public function mount(int|string $record): void
    {
        $this->record = Enquiry::findOrFail($record);
        $this->record->viewed_by = getLoggedInUserId();
        $this->record->status = SuperAdminEnquiry::READ;
        $this->record->save();
    }

    protected static string $resource = EnquiriesResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('back')
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
                    TextEntry::make('full_name')
                        ->label(__('messages.profile.full_name').':'),
                    TextEntry::make('email')
                        ->label(__('messages.enquiry.email').':'),
                    TextEntry::make('contact_no')
                        ->label(__('messages.enquiry.contact').':'),
                    TextEntry::make('type')
                        ->getStateUsing(function ($record) {
                            if ($record->type == 1) {
                                return __('messages.enquiry.general_enquiry');
                            } elseif ($record->type == 2) {
                                return __('messages.enquiry.feedback/suggestions');
                            }

                            return __('messages.enquiry.residential_care');
                        })
                        ->label(__('messages.enquiry.type').':'),
                    TextEntry::make('viewed_by')
                        ->getStateUsing(fn ($record) => $record->user->full_name ?? __('messages.common.n/a'))
                        ->label(__('messages.enquiry.viewed_by').':'),
                    TextEntry::make('status')
                        ->label(__('messages.common.status').':')
                        ->getStateUsing(function ($record) {
                            if ($record->status == 1) {
                                return __('messages.enquiry.read');
                            }

                            return __('messages.enquiry.unread');
                        })
                        ->color(function ($record) {
                            if ($record->status == 1) {
                                return 'success';
                            }

                            return 'danger';
                        })
                        ->badge(),
                    TextEntry::make('created_at')
                        ->getStateUsing(fn ($record) => $record->created_at->diffForHumans())
                        ->label(__('messages.common.created_on').':'),
                    TextEntry::make('updated_at')
                        ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans())
                        ->label(__('messages.common.last_updated').':'),
                    TextEntry::make('message')
                        ->label(__('messages.enquiry.message').':'),
                ])->columns(2),

            ])->columns(1);
    }
}
