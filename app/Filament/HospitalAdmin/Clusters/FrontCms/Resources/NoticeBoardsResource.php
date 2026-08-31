<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontCms\Resources;

use App\Filament\HospitalAdmin\Clusters\FrontCms;
use App\Filament\HospitalAdmin\Clusters\FrontCms\Resources\NoticeBoardsResource\Pages;
use App\Models\NoticeBoard;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NoticeBoardsResource extends Resource
{
    protected static ?string $model = NoticeBoard::class;

    protected static ?string $cluster = FrontCms::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Notice Boards')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Notice Boards')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.dashboard.notice_boards');
    }

    public static function getLabel(): string
    {
        return __('messages.dashboard.notice_boards');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Notice Boards')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Notice Boards')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Notice Boards')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label(__('messages.notice_board.title').':')
                    ->placeholder(__('messages.notice_board.title'))
                    ->required()
                    ->validationAttribute(__('messages.notice_board.title'))
                    ->columnSpan('full'),

                Forms\Components\Textarea::make('description')
                    ->label(__('messages.notice_board.description').':')
                    ->placeholder(__('messages.notice_board.description'))
                    ->rows(6)
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Notice Boards')) {
            abort(404);
        }

        return $table = $table->modifyQueryUsing(function (Builder $query) {
            $query->whereTenantId(auth()->user()->tenant_id);

            return $query;
        })
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('messages.notice_board.title'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('messages.common.created_at'))
                    ->getStateUsing(fn ($record) => Carbon::parse($record->created_at)->translatedFormat('jS M, Y'))
                    ->badge()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->iconButton(),
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.notice_board_updated'))->modalHeading(__('messages.notice_board.edit')),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.notice_board_deleted')),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageNoticeBoards::route('/'),
            'view' => Pages\ViewNoticeBoard::route('/{record}'),
        ];
    }
}
