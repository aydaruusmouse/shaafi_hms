<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources;

use App\Filament\HospitalAdmin\Clusters\Billings;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = Billings::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Payments')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Payments')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.payments');
    }

    public static function getLabel(): string
    {
        return __('messages.payments');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Payments')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Payments')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Payments')) {
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
                Section::make()
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->relationship('account', 'name', fn ($query) => $query->whereTenantId(getLoggedInUser()->tenant_id))
                            ->placeholder(__('messages.document.select_account'))
                            ->label(__('messages.payment.account').':')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.payment.account').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label(__('messages.payment.payment_date').':')
                            ->native(false)
                            ->placeholder(__('messages.payment.payment_date'))
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.payment.payment_date').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\TextInput::make('pay_to')
                            ->required()
                            ->validationAttribute(__('messages.payment.pay_to'))
                            ->label(__('messages.payment.pay_to').':')
                            ->placeholder(__('messages.payment.pay_to'))
                            ->maxLength(191),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->validationAttribute(__('messages.payment.amount'))
                            ->label(__('messages.payment.amount').':')
                            ->live()
                            ->minValue(1)
                            ->numeric()
                            ->placeholder(__('messages.payment.amount')),
                        Forms\Components\Textarea::make('description')
                            ->label(__('messages.payment.description').':')
                            ->placeholder(__('messages.payment.description')),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && ! getModuleAccess('Payments')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('tenant_id', auth()->user()->tenant_id);

            return $query;
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('account.name')
                    ->label(__('messages.payment.account'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label(__('messages.payment.payment_date'))
                    ->getStateUsing(fn ($record) => $record->payment_date ? Carbon::parse($record->payment_date)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label(__('messages.payment.account'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('pay_to')
                    ->label(__('messages.payment.pay_to'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('messages.invoice.amount'))
                    ->numeric()
                    ->formatStateUsing(function (Payment $record) {
                        return getCurrencyFormat($record->amount);
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->actions([
                Tables\Actions\ViewAction::make('View')
                    ->iconButton()
                    ->modalHeading(__('messages.payment.payment_details'))
                    ->infolist(function (Infolist $infolist) {
                        return $infolist
                            ->schema([
                                TextEntry::make('account.name')
                                    ->label(__('messages.payment.account').':'),
                                TextEntry::make('payment_date')
                                    ->date()
                                    ->label(__('messages.payment.payment_date').':'),
                                TextEntry::make('pay_to')
                                    ->label(__('messages.payment.pay_to').':'),
                                TextEntry::make('amount')
                                    ->formatStateUsing(function (Payment $record) {
                                        return getCurrencyFormat($record->amount);
                                    })
                                    ->label(__('messages.invoice.amount').':'),
                                TextEntry::make('created_at')
                                    ->since()
                                    ->label(__('messages.common.created_on').': '),
                                TextEntry::make('updated_at')
                                    ->since()
                                    ->label(__('messages.common.updated_at').': '),
                                TextEntry::make('description')
                                    ->label(__('messages.payment.description').':')
                                    ->default(__('messages.common.n/a')),

                            ])->columns(3);
                    })->color('info'),
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.payment_updated')),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.payment_deleted')),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                // ExportBulkAction::make(),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
