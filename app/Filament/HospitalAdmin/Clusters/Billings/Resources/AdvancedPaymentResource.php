<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources;

use App\Filament\HospitalAdmin\Clusters\Billings;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\AdvancedPaymentResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\AdvancedPaymentResource\Pages\ViewAdvancedPayment;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\AdvancedPayment;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdvancedPaymentResource extends Resource
{
    protected static ?string $model = AdvancedPayment::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 6;

    protected static ?string $cluster = Billings::class;

    public static function shouldRegisterNavigation(): bool
    {
        // return auth()->user()->hasRole(['Admin', 'Nurse']);
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Advance Payments')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Advance Payments')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.advanced_payments');
    }

    public static function getLabel(): string
    {
        return __('messages.advanced_payments');
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
                Forms\Components\Select::make('patient_id')
                    ->label(__('messages.advanced_payment.patient'))
                    ->options(Patient::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id'))
                    ->label(__('messages.invoice.patient_id').':')
                    ->native(false)
                    ->searchable()
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.invoice.patient_id').' '.__('messages.fields.required'),
                    ]),
                Forms\Components\TextInput::make('receipt_no')
                    ->required()
                    ->validationAttribute(__('messages.advanced_payment.receipt_no'))
                    ->label(__('messages.advanced_payment.receipt_no').':')
                    ->default(strtoupper(Str::random(8)))
                    ->readOnly()
                    ->maxLength(191),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->validationAttribute(__('messages.invoice.amount'))
                    ->label(__('messages.invoice.amount').':')
                    ->placeholder(__('messages.invoice.amount').':')
                    ->numeric()
                    ->minValue(1),
                Forms\Components\DatePicker::make('date')
                    ->label(__('messages.advanced_payment.date').':')
                    ->native(false)
                    ->required()
                    ->validationAttribute(__('messages.advanced_payment.date')),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Advance Payments')) {
            abort(404);
        }
        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('receipt_no')
                    ->label(__('messages.advanced_payment.receipt_no'))
                    ->badge()
                    ->url(fn ($record) => AdvancedPaymentResource::getUrl('view', ['record' => $record->id]))
                    ->searchable()
                    ->sortable(),
                SpatieMediaLibraryImageColumn::make('patient.user.profile')
                    ->label(__('messages.invoice.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if ($record && $record->patient && $record->patient->user && ! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }

                        return '';
                    })
                    ->sortable(['first_name'])
                    ->url(fn ($record) => $record && $record->patient ? PatientResource::getUrl('view', ['record' => $record->patient->id]) : '')
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.user.full_name')
                    ->label('')
                    ->description(function (AdvancedPayment $record) {
                        return $record && $record->patient && $record->patient->user ? $record->patient->user->email : __('messages.common.n/a');
                    })
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($record) => $record && $record->patient && $record->patient->user
                        ? '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'" class="hoverLink">'.$record->patient->user->full_name.'</a>'
                        : __('messages.common.n/a'))
                    ->html()
                    ->searchable(['first_name', 'last_name', 'email']),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('messages.advanced_payment.date'))
                    ->getStateUsing(fn ($record) => $record->date ? Carbon::parse($record->date)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->formatStateUsing(function (AdvancedPayment $record) {
                        return getCurrencyFormat($record->amount);
                    })
                    ->label(__('messages.invoice.amount'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->modalWidth('md')->successNotificationTitle(__('messages.flash.advanced_payment_updated'))->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->modalWidth('md')
                    ->action(function (AdvancedPayment $record) {
                        if (! canAccessRecord(AdvancedPayment::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.advance_payment_not_found'))
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.advanced_payment_deleted'))
                            ->send();
                    }),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->recordAction(null)
            ->recordUrl(null)
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
            'index' => Pages\ManageAdvancedPayments::route('/'),
            'view' => ViewAdvancedPayment::route('/{record}'),
        ];
    }
}
