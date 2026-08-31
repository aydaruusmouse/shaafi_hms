<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\SuperAdminCurrencySettingResource\Pages;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdminCurrencySetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SuperAdminCurrencySettingResource extends Resource
{
    protected static ?string $model = SuperAdminCurrencySetting::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Settings::class;

    public static function getNavigationLabel(): string
    {
        return __('messages.currency.currencies');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.currency.currencies');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('currency_name')
                    ->label(__('messages.currency.currency_name').':')
                    ->placeholder(__('messages.currency.currency_name'))
                    ->unique('super_admin_currency_settings', 'currency_name', ignoreRecord: true)
                    ->validationAttribute(__('messages.currency.currency_name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('currency_code')
                    ->required()
                    ->label(__('messages.currency.currency_code').':')
                    ->placeholder(__('messages.currency.currency_code'))
                    ->validationAttribute(__('messages.currency.currency_code'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('currency_icon')
                    ->label(__('messages.currency.currency_icon').':')
                    ->placeholder(__('messages.currency.currency_icon'))
                    ->validationAttribute(__('messages.currency.currency_icon'))
                    ->unique('super_admin_currency_settings', 'currency_code', ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('currency_name')
                    ->label(__('messages.currency.currency_name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency_icon')
                    ->label(__('messages.currency.currency_code'))
                    ->searchable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency_code')
                    ->label(__('messages.currency.currency_icon'))
                    ->sortable()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->modalWidth('md')
                    ->successNotificationTitle(__('messages.new_change.currency_update'))
                    ->modalHeading(__('messages.currency.edit_currency'))
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->tooltip(__('messages.common.delete'))
                    ->iconButton()
                    ->action(function ($record) {
                        $model = SubscriptionPlan::class;
                        $result = canCurrencyDelete($model, 'currency', $record['currency_code']);
                        if ($result) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.new_change.currency_not_delete'))
                                ->send();
                        }

                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.currency.currencies').' '.__('messages.common.deleted_successfully'))
                            ->send();
                    })
                    ->modalHeading(__('messages.common.delete').' '.__('messages.currency.currencies')),
            ])
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
            'index' => Pages\ManageSuperAdminCurrencySettings::route('/'),
        ];
    }
}
