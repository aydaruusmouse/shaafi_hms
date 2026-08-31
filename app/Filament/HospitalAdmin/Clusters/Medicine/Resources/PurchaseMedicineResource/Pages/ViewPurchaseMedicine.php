<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\PurchaseMedicineResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\PurchaseMedicineResource;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\PurchaseMedicineResource\Widgets\PurchaseMedicineDetail;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseMedicine extends ViewRecord
{
    protected static string $resource = PurchaseMedicineResource::class;

    protected static ?int $sort = 0;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('')
                ->schema([
                    TextEntry::make('purchase_no')
                        ->badge()
                        ->prefix('#')
                        ->label(__('messages.purchase_medicine.purchase_number').':'),
                    TextEntry::make('total')
                        ->label(__('messages.purchase_medicine.total').':'),
                    TextEntry::make('tax')
                        ->label(__('messages.purchase_medicine.tax_amount').':'),
                    TextEntry::make('discount')
                        ->label(__('messages.purchase_medicine.discount').':'),
                    TextEntry::make('net_amount')
                        ->label(__('messages.purchase_medicine.net_amount').':'),
                    TextEntry::make('note')
                        ->label(__('messages.purchase_medicine.note').':')
                        ->formatStateUsing(fn ($state) => $state ?: __('messages.common.n/a'))
                        ->default(__('messages.common.n/a')),

                    Grid::make(12)
                        ->schema([
                            TextEntry::make('')
                                ->columnSpan(9),

                            Grid::make(3)
                                ->schema([
                                    TextEntry::make('total')
                                        ->label(__('messages.purchase_medicine.total').':')
                                        ->inlineLabel()
                                        ->columnSpan(3),
                                    TextEntry::make('tax')
                                        ->label(__('messages.purchase_medicine.tax').':')
                                        ->inlineLabel()
                                        ->columnSpan(3),
                                    TextEntry::make('discount')
                                        ->label(__('messages.purchase_medicine.discount').':')
                                        ->inlineLabel()
                                        ->columnSpan(3),
                                    TextEntry::make('net_amount')
                                        ->label(__('messages.purchase_medicine.net_amount').':')
                                        ->inlineLabel()
                                        ->columnSpan(3),
                                ])
                                ->columnSpan(3),
                        ]),
                ])->columns(3),
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PurchaseMedicineDetail::class,
        ];
    }
}
