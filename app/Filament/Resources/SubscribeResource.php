<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscribeResource\Pages;
use App\Models\Subscribe;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscribeResource extends Resource
{
    protected static ?string $model = Subscribe::class;

    protected static ?string $navigationIcon = 'fab-stripe-s';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Subscribers';

    protected static ?string $navigationLabel = 'Subscribers';

    public static function getNavigationLabel(): string
    {
        return __('messages.delete.subscriber');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.delete.subscriber');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('email')
                    ->label(__('messages.user.email'))
                    ->searchable()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->tooltip(__('messages.common.delete'))
                    ->iconButton()
                    ->successNotificationTitle(__('messages.delete.subscriber').' '.__('messages.common.deleted_successfully')),
            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscribes::route('/'),
            // 'create' => Pages\CreateSubscribe::route('/create'),
            // 'edit' => Pages\EditSubscribe::route('/{record}/edit'),
        ];
    }
}
