<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\PackageResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\PackageResource;
use App\Livewire\PackageServiceTable;
use Filament\Actions;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPackage extends ViewRecord
{
    protected static string $resource = PackageResource::class;

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
                            ->label(__('messages.package.package').':'),
                        TextEntry::make('discount')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.insurance.service_tax').':'),
                        TextEntry::make('created_at')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.common.created_at').':')
                            ->since(),
                        TextEntry::make('updated_at')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.common.updated_at').':')
                            ->since(),
                        TextEntry::make('description')
                            ->default(__('messages.common.n/a'))
                            ->extraAttributes(['style' => 'word-break: break-all;'])
                            ->label(__('messages.package.description').':'),
                    ])->columns(2),
                Group::make(
                    [
                        Livewire::make(PackageServiceTable::class),
                    ]
                )->columnSpanFull(),
            ]);
    }
}
