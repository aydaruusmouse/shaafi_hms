<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\InsuranceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\InsuranceResource;
use App\Livewire\InsuranceDiseaseTable;
use Filament\Actions;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInsurance extends ViewRecord
{
    protected static string $resource = InsuranceResource::class;

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
                Section::make('')
                    ->schema([
                        TextEntry::make('name')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.insurance.insurance').':'),
                        TextEntry::make('service_tax')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.insurance.service_tax').':'),
                        TextEntry::make('discount')
                            ->default(__('messages.common.n/a'))
                            ->formatStateUsing(fn ($state) => $state.'%')
                            ->label(__('messages.insurance.discount').':'),
                        TextEntry::make('insurance_no')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.insurance.insurance_no').':'),
                        TextEntry::make('insurance_code')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.insurance.insurance_code').':'),
                        TextEntry::make('hospital_rate')
                            ->default(__('messages.common.n/a'))
                            ->formatStateUsing(fn ($state) => number_format($state, 2))
                            ->label(__('messages.insurance.hospital_rate').':'),
                        TextEntry::make('total')
                            ->default(__('messages.common.n/a'))
                            ->formatStateUsing(fn ($state) => number_format($state, 2))
                            ->label(__('messages.insurance.total').':'),
                        TextEntry::make('status')
                            ->default(__('messages.common.n/a'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? __('messages.common.active') : __('messages.common.de_active'))
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->label(__('messages.common.status').':'),
                        TextEntry::make('remark')
                            ->default(__('messages.common.n/a'))
                            ->formatStateUsing(fn ($state) => ! empty($state) ? nl2br(e($state)) : __('messages.common.n/a'))
                            ->label(__('messages.insurance.remark').':'),
                        TextEntry::make('created_at')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.common.created_at').':')
                            ->since(),
                        TextEntry::make('updated_at')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.common.updated_at').':')
                            ->since(),
                    ])->columns(2),
                Group::make(
                    [
                        Livewire::make(InsuranceDiseaseTable::class),
                    ]
                )->columnSpanFull(),
            ]);
    }
}
