<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources;

use App\Filament\HospitalAdmin\Clusters\Billings;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\PaymentReportResource\Pages;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PaymentReportResource extends Resource
{
    protected static ?string $model = Payment::class;

    public $statusFilter;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = Billings::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Payment Reports')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Payment Reports')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.payment.payment_reports');
    }

    public static function getLabel(): string
    {
        return __('messages.payment.payment_reports');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Payment Reports')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Payment Reports')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Payment Reports')) {
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
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Payment Reports')) {
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
                Tables\Columns\TextColumn::make('payment_date')
                    ->label(__('messages.payment.payment_date'))
                    ->getStateUsing(fn ($record) => $record->payment_date ? Carbon::parse($record->payment_date)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label(__('messages.payment.account'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->numeric()
                    ->label(__('messages.payment.account'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('pay_to')
                    ->label(__('messages.payment.pay_to'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('account.type')
                    ->label(__('messages.account.type'))
                    ->color(function (Payment $record) {
                        return $record->account->type == 1 ? 'danger' : 'success';
                    })
                    ->formatStateUsing(function (Payment $record) {
                        return $record->account->type == 1 ? __('messages.accountant.debit') : __('messages.accountant.credit');
                    })
                    ->badge()
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
                SelectFilter::make('accounts.type')
                    ->label(__('messages.account.type').':')
                    ->options([
                        '' => __('messages.filter.all'),
                        1 => __('messages.accountant.debit'),
                        2 => __('messages.accountant.credit'),
                    ])
                    ->query(function (Builder $query, $state) {
                        if ($state['value'] == 1) {
                            $query->whereHas('accounts', function (Builder $query) {
                                $query->where('type', '=', 1);
                            });
                        } elseif ($state['value'] == 2) {
                            $query->whereHas('accounts', function (Builder $query) {
                                $query->where('type', '=', 2);
                            });
                        }
                    })
                    ->default('')
                    ->native(false),
            ])
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
            'index' => Pages\ListPaymentReports::route('/'),

        ];
    }
}
