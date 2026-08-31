<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources;

use App\Filament\HospitalAdmin\Clusters\Billings;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\AccountResource\Pages;
use App\Livewire\AccountPaymentRelationTable;
use App\Models\Account;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Form;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Billings::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Accounts')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Accounts')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.accounts');
    }

    public static function getLabel(): string
    {
        return __('messages.accounts');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Accounts')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Accounts')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Accounts')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label(__('messages.account.account').':')
                    ->placeholder(__('messages.account.account'))
                    ->validationAttribute(__('messages.account.account'))
                    ->columnSpanFull()
                    ->maxLength(160),
                Forms\Components\Textarea::make('description')
                    ->label(__('messages.account.description').':')
                    ->placeholder(__('messages.account.description'))
                    ->rows(5)
                    ->columnSpanFull()
                    ->maxLength(160),
                Forms\Components\Toggle::make('status')
                    ->label(__('messages.common.status').':')
                    ->default(1)
                    ->columnSpanFull(),
                Radio::make('type')
                    ->label(__('messages.account.type').':')
                    ->options([
                        1 => __('messages.accountant.debit'),
                        2 => __('messages.accountant.credit'),
                    ])
                    ->inline(true)
                    ->default(1)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {

        if (auth()->user()->hasRole(['Admin', 'Accountant']) && ! getModuleAccess('Accounts')) {
            abort(404);
        }
        $table = $table->modifyQueryUsing(function (Builder $query) {
            $query->whereTenantId(auth()->user()->tenant_id);

            return $query;
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.accounts'))
                    ->color('primary')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('messages.account.type'))
                    ->color(function (Account $record) {
                        return $record->type == 1 ? 'danger' : 'success';
                    })
                    ->formatStateUsing(function (Account $record) {
                        return $record->type == 1 ? __('messages.accountant.debit') : __('messages.accountant.credit');
                    })
                    ->badge(),
                ToggleColumn::make('status')
                    ->label(__('messages.user.status'))
                    ->updateStateUsing(function (Account $record, bool $state) {
                        $user = Account::find($record->id);
                        $state ? $user->status = 1 : $user->status = 0;
                        $user->save();
                        Notification::make()
                            ->title(__('messages.flash.account_update'))
                            ->success()
                            ->send();
                    }),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        '' => __('messages.filter.all'),
                        '1' => __('messages.filter.active'),
                        '0' => __('messages.filter.deactive'),
                    ])
                    ->native(false)
                    ->label(__('messages.user.status')),
                SelectFilter::make('type')
                    ->label(__('messages.account.type'))
                    ->options([
                        '' => __('messages.filter.all'),
                        '1' => __('messages.accountant.credit'),
                        '2' => __('messages.accountant.debit'),
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->iconButton()->extraAttributes(['class' => 'hidden']),
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.account_update')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->modalWidth('md')
                    ->action(function (Account $record) {
                        if (! canAccessRecord(Account::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.accountant_not_found'))
                                ->send();
                        }
                        $accountModel = [
                            Payment::class,
                        ];
                        $result = canDelete($accountModel, 'account_id', $record->id);
                        if ($result) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.account_cant_delete'))
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.account_delete'))
                            ->send();
                    }),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()->schema([
                    TextEntry::make('name')
                        ->label(__('messages.account.account').':')
                        ->default(__('messages.common.n/a')),
                    TextEntry::make('type')
                        ->label(__('messages.account.type').':')
                        ->getStateUsing(function ($record) {
                            if ($record->type == 1) {
                                return __('messages.accountant.debit');
                            } else {
                                return __('messages.accountant.credit');
                            }
                        })
                        ->badge()
                        ->color(function ($record) {
                            if ($record->type == 1) {
                                return 'danger';
                            } else {
                                return 'success';
                            }
                        })
                        ->default(__('messages.common.n/a')),
                    TextEntry::make('status')
                        ->label(__('messages.common.status').':')
                        ->getStateUsing(function ($record) {
                            if ($record->status == 1) {
                                return __('messages.common.active');
                            } else {
                                return __('messages.common.deactive');
                            }
                        })
                        ->badge()
                        ->color(function ($record) {
                            if ($record->status == 1) {
                                return 'success';
                            } else {
                                return 'danger';
                            }
                        })
                        ->default(__('messages.common.n/a')),
                    TextEntry::make('description')
                        ->label(__('messages.account.description').':')
                        ->default(__('messages.common.n/a')),
                ])->columns(2),
                Section::make(__('messages.payments'))->schema([
                    Livewire::make(AccountPaymentRelationTable::class),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'view' => Pages\ViewAccount::route('/{record}'),
            'index' => Pages\ManageAccounts::route('/'),
        ];
    }
}
