<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontCms\Resources\NoticeBoardsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontCms\Resources\NoticeBoardsResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewNoticeBoard extends ViewRecord
{
    protected static string $resource = NoticeBoardsResource::class;

    protected function getActions(): array
    {
        return [
            EditAction::make(),
            Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(url()->previous()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make(__(''))
                ->schema([
                    Grid::make(12)
                        ->columns(12)
                        ->schema([
                            TextEntry::make('title')
                                ->label(__('messages.notice_board.title').':')
                                ->columnSpan(6),

                            TextEntry::make('description')
                                ->label(__('messages.notice_board.description').':')
                                ->columnSpan(6),

                            TextEntry::make('created_at')
                                ->label(__('messages.common.created_at').':')
                                ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a'))
                                ->columnSpan(6),

                            TextEntry::make('updated_at')
                                ->label(__('messages.common.updated_at').':')
                                ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a'))
                                ->columnSpan(6),
                        ]),
                ]),
        ]);
    }
}
