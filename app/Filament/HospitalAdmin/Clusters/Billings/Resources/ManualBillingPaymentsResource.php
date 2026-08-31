<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources;

use App\Filament\HospitalAdmin\Clusters\Billings;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\ManualBillingPaymentsResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Bill;
use App\Models\BillTransaction;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ManualBillingPaymentsResource extends Resource
{
    protected static ?string $model = BillTransaction::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 8;

    protected static ?string $cluster = Billings::class;

    protected static ?string $navigationTitle = 'Manual Billing Payments';

    // public static function shouldRegisterNavigation(): bool
    // {
    //         // return auth()->user()->hasRole(['Admin', 'Nurse']);
    //     // if (auth()->user()->hasRole(['Admin'])  && !getModuleAccess('Advance Payments')) {
    //     //     return false;
    //     // } elseif (!auth()->user()->hasRole(['Admin']) && !getModuleAccess('Advance Payments')) {
    //     //     return false;
    //     // }
    //     // return true;
    // }

    public static function getNavigationLabel(): string
    {
        return __('messages.manual_billing_payments');
    }

    public static function getLabel(): string
    {
        return __('messages.manual_billing_payments');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Advance Payments')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Advance Payments')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Advance Payments')) {
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

    // protected function getTableQuery(): Builder
    // {
    //     return BillTransaction::query()
    //         ->whereHas('bill.patient.patientUser')
    //         ->with(['bill.patient.patientUser.media'])
    //         ->select('bill_transactions.*');
    // }
    public static function table(Table $table): Table
    {
        // if (auth()->user()->hasRole('Admin') && !getModuleAccess('Advance Payments')) {
        //     abort(404);
        // }

        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        // $table = $table->modifyQueryUsing(function ($query) {
        //     $query->withWhereHas('manualPayment', function ($query) {
        //         $query->where('payment_type', 2);
        //     })->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc');
        // });
        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('bill.patient.user.profile')
                    ->label(__('messages.invoice.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->bill->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->bill->patient->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->bill->patient->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('bill.patient.user.full_name')
                    ->label('')
                    ->description(function ($record) {
                        return $record->bill->patient->user->email;
                    })
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($record) => "<a href='".PatientResource::getUrl('view', ['record' => $record->bill->patient->id])."' class='text-primary'>".$record->bill->patient->user->full_name.'</a>')
                    ->html()
                    ->searchable(['first_name', 'last_name', 'email']),
                // SelectColumn::make('is_manual_payment')
                //     ->label(__('messages.subscription.payment_approved')),
                // ->formatState(fn ($record) => dd($record->is_manual_payment) ),
                ViewColumn::make('is_manual_payment')
                    ->label(__('messages.subscription.payment_approved'))
                    ->view('tables.columns.hospitalAdmin.in-manual-payment'),
                TextColumn::make('status')
                    ->formatStateUsing(function ($record) {
                        return $record->status == 1 ? __('messages.paid') : __('messages.unpaid');
                    })
                    ->badge()
                    ->color(function ($record) {
                        return $record->status == 1 ? 'success' : 'danger';
                    })
                    ->label(__('messages.common.status')),

                TextColumn::make('bill.created_at')
                    ->view('tables.columns.hospitalAdmin.createdAt')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('messages.bill.amount'))
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        return getCurrencyFormat($record->amount);
                    }),
            ])

            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
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
            'index' => Pages\ListManualBillingPayments::route('/'),
            // 'create' => Pages\CreateManualBillingPayments::route('/create'),
            // 'edit' => Pages\EditManualBillingPayments::route('/{record}/edit'),
        ];
    }
}
