<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontCms\Resources\NoticeBoardsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontCms\Resources\NoticeBoardsResource;
use App\Repositories\NoticeBoardRepository;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageNoticeBoards extends ManageRecords
{
    protected static string $resource = NoticeBoardsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('messages.notice_board.new'))->modalWidth('md')->createAnother(false)->modalHeading(__('messages.notice_board.new'))->successNotificationTitle(__('messages.flash.notice_board_saved'))
                ->after(function ($record) {
                    app(NoticeBoardRepository::class)->createNotification($record);
                }),
        ];
    }
}
