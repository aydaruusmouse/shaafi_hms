<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\AdvancedPaymentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\AdvancedPaymentResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewAdvancedPayment extends ViewRecord
{
    protected static string $resource = AdvancedPaymentResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make()->successNotificationTitle(__('messages.flash.advanced_payment_updated')),
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
                Section::make('')
                    ->schema([
                        TextEntry::make('patient.user.full_name')
                            ->label(__('messages.advanced_payment.patient').':'),
                        TextEntry::make('receipt_no')
                            ->badge()
                            ->label(__('messages.advanced_payment.receipt_no').':'),
                        TextEntry::make('amount')
                            ->formatStateUsing(fn ($state) => getCurrencyFormat($state))
                            ->label(__('messages.advanced_payment.amount').':'),
                        TextEntry::make('date')
                            ->label(__('messages.advanced_payment.date').':')
                            ->getStateUsing(fn ($record) => \Carbon\Carbon::parse($record->date)->translatedFormat('jS M, Y')),
                        TextEntry::make('created_at')
                            ->since()
                            ->label(__('messages.common.created_on').':'),
                        TextEntry::make('updated_at')
                            ->since()
                            ->label(__('messages.common.updated_on').':'),
                    ])->columns(2),
            ]);
    }
}
