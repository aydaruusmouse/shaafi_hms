<?php

namespace App\Filament\HospitalAdmin\Widgets;

use App\Models\NoticeBoard;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class NoticeBoardOverView extends BaseWidget
{
    protected static ?int $sort = 3;

    public function getTableRecordsPerPageSelectOptions(): array
    {
        return [
            5 => 5,
            10 => 10,
            20 => 20,
            50 => 50,
            100 => 100,
        ];
    }

    public static function canView(): bool
    {
        $query = NoticeBoard::orderBy('id', 'DESC')->where('tenant_id', getLoggedInUser()->tenant_id)->count();

        return $query > 0 && auth()->user()->hasRole('Admin');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('messages.dashboard.notice_boards'))
            ->query(NoticeBoard::orderBy('id', 'DESC')->where('tenant_id', getLoggedInUser()->tenant_id)->take(5))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('messages.dashboard.title'))
                    ->formatStateUsing(
                        fn ($record) => '<a href="'.route('filament.hospitalAdmin.front-cms.resources.notice-boards.view', $record->id).'"class="hoverLink">'.$record->title.'</a>'
                    )
                    ->html()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('messages.common.created_on'))
                    ->getStateUsing(fn ($record) => $record->created_at ? \Carbon\Carbon::parse($record->created_at)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                    ->badge(),
            ])->paginated(false)
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }
}
