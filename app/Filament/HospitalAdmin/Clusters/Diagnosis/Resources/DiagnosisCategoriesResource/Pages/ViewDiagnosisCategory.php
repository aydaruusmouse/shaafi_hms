<?php

namespace App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisCategoriesResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisCategoriesResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewDiagnosisCategory extends ViewRecord
{
    protected static string $resource = DiagnosisCategoriesResource::class;

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
                Section::make()->schema([
                    TextEntry::make('name')
                        ->default(__('messages.common.n/a'))
                        ->label(__('messages.diagnosis_category.diagnosis_category').':'),
                    TextEntry::make('description')
                        ->default(__('messages.common.n/a'))
                        ->label(__('messages.diagnosis_category.description').':'),
                    TextEntry::make('created_at')
                        ->since()
                        ->label(__('messages.common.created_at').':'),
                    TextEntry::make('updated_at')
                        ->since()
                        ->label(__('messages.common.last_updated').':'),
                ])->columns(2),
            ]);
    }
}
