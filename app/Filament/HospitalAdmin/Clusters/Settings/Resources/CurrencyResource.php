<?php

namespace App\Filament\HospitalAdmin\Clusters\Settings\Resources;

use App\Filament\HospitalAdmin\Clusters\Settings;
use App\Filament\HospitalAdmin\Clusters\Settings\Resources\CurrencyResource\Pages;
use App\Models\CurrencySetting;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CurrencyResource extends Resource
{
    protected static ?string $model = CurrencySetting::class;

    protected static ?string $cluster = Settings::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('messages.setting.currency');
    }

    public static function getLabel(): string
    {
        return __('messages.setting.currency');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('currency_name')
                    ->label(__('messages.currency.currency_name'))
                    ->required()
                    ->validationAttribute(__('messages.currency.currency_name'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('currency_icon')
                    ->label(__('messages.currency.currency_icon'))
                    ->required()
                    ->validationAttribute(__('messages.currency.currency_icon'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('currency_code')
                    ->label(__('messages.currency.currency_code'))
                    ->required()
                    ->validationAttribute(__('messages.currency.currency_code'))
                    ->minLength(3)
                    ->maxLength(3),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(getLoggedInUser()->tenant_id);

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    TextColumn::make('currency_name')
                        ->label(__('messages.currency.currency_name'))
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('currency_icon')
                        ->label(__('messages.currency.currency_icon'))
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('currency_code')
                        ->label(__('messages.currency.currency_code'))
                        ->searchable()
                        ->sortable(),
                ])
                ->filters([
                    //
                ])
                ->recordAction(null)
                ->actionsColumnLabel(__('messages.common.action'))
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.new_change.currency_update'))
                        ->action(function ($data, $record) {
                            $data = [
                                'currency_name' => $data['currency_name'],
                                'currency_code' => strtoupper($data['currency_code']),
                                'currency_icon' => $data['currency_icon'],
                            ];

                            CurrencySetting::find($record->id)->update($data);

                            Notification::make()
                                ->success()
                                ->title(__('messages.new_change.currency_update'))
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->iconButton()
                        ->action(function (CurrencySetting $record) {
                            if (! canAccessRecord(CurrencySetting::class, $record->id)) {
                                return Notification::make()
                                    ->danger()
                                    ->title(__('messages.flash.currency_not_found'))
                                    ->send();
                            }

                            $currency = Setting::where('key', 'current_currency')->where('tenant_id', getLoggedInUser()->tenant_id)->first()->value;
                            if ($currency == strtolower($record['currency_code'])) {
                                return Notification::make()
                                    ->danger()
                                    ->title(__('messages.new_change.default_currency_not_delete'))
                                    ->send();
                            } else {
                                $record->delete();

                                return Notification::make()
                                    ->success()
                                    ->title(__('messages.subscription_plans.currency').' '.__('messages.common.has_been_deleted'))
                                    ->send();
                            }
                        }),
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
            'index' => Pages\ManageCurrencies::route('/'),
        ];
    }
}
