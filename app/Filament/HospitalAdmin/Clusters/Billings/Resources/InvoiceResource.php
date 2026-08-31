<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources;

use App\Filament\HospitalAdmin\Clusters\Billings;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\InvoiceResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Billings::class;

    public static function getNavigationLabel(): string
    {
        return __('messages.invoices');
    }

    public static function getLabel(): string
    {
        return __('messages.invoices');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Receptionist', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole('Admin') && ! getModuleAccess('Invoices')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Invoices')) {
            return false;
        }

        return true;
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Doctor', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Invoices')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Doctor', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Invoices')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Doctor', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Invoices')) {
            return true;
        }

        return false;
    }

    // public static function canViewAny(): bool
    // {
    //     if(auth()->user()->hasRole('Admin','Patient'))
    //     {
    //         return true;
    //     }
    //     return false;
    // }
    public static function form(Form $form): Form
    {
        $accounts = Account::whereStatus(1)->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('name', 'asc')->pluck('name', 'id');

        return $form
            ->live()
            ->schema([
                Section::make('')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_id')
                            ->label(__('messages.invoice.invoice_id').' #')
                            ->default(function () {
                                return Invoice::generateUniqueInvoiceId();
                            })
                            ->readonly(),
                        Forms\Components\Select::make('patient_id')
                            ->options(Patient::with('patientUser')->get()->where('patientUser.status', '=', 1)->where('patientUser.tenant_id', '=', getLoggedInUser()->tenant_id)->pluck('patientUser.full_name', 'id')->sort())
                            ->label(__('messages.invoice.patient').':')
                            ->searchable()
                            ->preload()
                            ->placeholder(__('messages.invoice.patient'))
                            ->native(false)
                            ->required()
                            ->validationAttribute(__('messages.invoice.patient'))
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.invoice.patient').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\DatePicker::make('invoice_date')
                            ->label(__('messages.invoice.invoice_date').':')
                            ->native(false)
                            ->required()
                            ->validationAttribute(__('messages.invoice.invoice_date')),
                    ])->columns(3),
                Section::make('')
                    ->schema([
                        Forms\Components\TextInput::make('discount')
                            ->live()
                            ->required()
                            ->validationAttribute(__('messages.invoice.discount'))
                            ->label(__('messages.invoice.discount').': (%)')
                            ->placeholder(__('messages.document.in_percentage'))
                            ->afterStateUpdated(function ($get, $set, $state) {
                                if ($state > 100) {
                                    $set('discount', 100);
                                }
                                if (empty($get('discount')) || ! is_string($get('discount'))) {
                                    $set('discount', 0);
                                }
                                self::updateTotal($get, $set);
                            })
                            ->minValue(0)
                            ->maxValue(100)
                            ->numeric(),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->placeholder(__('messages.common.select_status'))
                            ->label(__('messages.common.status').':')
                            ->options([
                                1 => __('messages.paid'),
                                0 => __('messages.unpaid'),
                            ])
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.common.status').' '.__('messages.fields.required'),
                            ]),
                    ])->columns(2),
                Repeater::make('invoice')
                    ->label('')
                    ->live()
                    ->schema([
                        Select::make('account_id')
                            ->options($accounts)
                            ->label(__('messages.account.account').':')
                            ->placeholder(__('messages.document.select_account'))
                            ->native(false)
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('description')
                            ->label(__('messages.invoice.description'))
                            ->placeholder(__('messages.invoice.description')),
                        TextInput::make('quantity')
                            ->live()
                            ->numeric()
                            ->required()
                            ->validationAttribute(__('messages.invoice.qty'))
                            ->afterStateUpdated(function ($get, $set) {
                                if ($get('quantity') == '' || empty($get('quantity')) || ! is_string($get('quantity'))) {
                                    $set('quantity', 0);
                                }
                                self::updateTotal($get, $set);
                            })
                            ->label(__('messages.invoice.qty'))
                            ->placeholder(__('messages.invoice.qty')),
                        TextInput::make('price')
                            ->live()
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->validationAttribute(__('messages.invoice.price'))
                            ->afterStateUpdated(function ($get, $set) {
                                if ($get('price') == '' || empty($get('price')) || ! is_string($get('price'))) {
                                    $set('price', 0);
                                }
                                self::updateTotal($get, $set);
                            })
                            ->label(__('messages.invoice.price'))
                            ->placeholder(__('messages.invoice.price')),
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(1)
                            ->default(0)
                            ->label(__('messages.invoice.amount'))
                            ->live()
                            ->readOnly(),
                        // ->disabled(),
                    ])
                    ->afterStateUpdated(function ($get, $set) {
                        self::updateTotal($get, $set);
                    })
                    ->addActionLabel(__('messages.common.add'))
                    ->deletable(function ($state) {
                        if (count($state) === 1) {
                            return false;
                        }

                        return true;
                    })
                    ->columns(5)->columnSpanFull(),
                Grid::make('')->columns(6)->schema([
                    Grid::make('')->columns(1)->columnSpan(4),
                    Grid::make('Main')->schema([
                        TextInput::make('sub_total')
                            ->live()
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotal($get, $set);
                            })
                            ->readOnly()
                            ->placeholder(__('messages.invoice.sub_total').'(TK)')
                            ->label(__('messages.invoice.sub_total').':')
                            ->inlineLabel(),
                        TextInput::make('discount_amount')
                            ->live()
                            ->placeholder(__('messages.invoice.discount').'(%)')
                            ->readOnly()
                            ->maxValue(100)
                            ->minValue(0)
                            ->label(__('messages.invoice.discount').':')
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotal($get, $set);
                            })
                            ->inlineLabel(),
                        TextInput::make('total_amount')
                            ->readOnly()
                            ->label(__('messages.invoice.total').':')
                            ->inlineLabel()
                            ->placeholder(__('messages.invoice.total').'(TK)')
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotal($get, $set);
                            }),
                    ])->columnSpan(2),
                ]),
            ]);
    }

    public static function updateTotal($get, $set): void
    {
        $items = collect($get('invoice'))->values()->toArray();

        $subtotal = 0;

        foreach ($items as $item) {
            // if (empty($item['price']) || $item['price'] == '' || !is_numeric($item['price'])) {
            //     $item['price'] = 0;
            // }

            // if (empty($item['quantity']) || $item['quantity'] == '' || !is_string($item['quantity'])) {
            //     $item['quantity'] = 0;
            // }
            $subtotal += round((float) $item['price'] * (float) $item['quantity'], 2);
        }

        $set('amount', round((float) $get('price') * (float) $get('quantity'), 2));
        $set('sub_total', round((float) $subtotal, 2));
        $set('discount_amount', round((float) $get('discount'), 2));
        $set('total_amount', round((float) $subtotal - (float) $subtotal * (float) $get('discount') / 100, 2));
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Doctor', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse', 'Patient'])) {
            abort(404);
        } elseif (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Invoices')) {
            abort(404);
        }
        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_id')
                    ->sortable()
                    ->label(__('messages.invoice.invoice_id'))
                    ->badge()
                    ->url(fn ($record) => InvoiceResource::getUrl('view', ['record' => $record->id]))
                    ->searchable(),

                SpatieMediaLibraryImageColumn::make('patient.user.profile')
                    ->label(__('messages.invoice.patient'))
                    ->circular()
                    ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                    ->defaultImageUrl(function ($record) {
                        if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->collection('profile')
                    ->width(50)->height(50),
                Tables\Columns\TextColumn::make('patient.id')
                    ->label('')
                    ->formatStateUsing(function (Invoice $record) {
                        return "<a href='".PatientResource::getUrl('view', ['record' => $record->patient->id])."' class='text-primary'>".$record->patient->user->full_name.'</a>';
                    })
                    ->html()
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->description(function (Invoice $record) {
                        return $record->patient->user->email;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label(__('messages.invoice.invoice_date'))
                    ->getStateUsing(fn ($record) => $record->invoice_date ? Carbon::parse($record->invoice_date)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('messages.invoice.amount'))
                    ->formatStateUsing(function (Invoice $record) {
                        return getCurrencyFormat($record->amount);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->color(fn (Invoice $record) => $record->status == 1 ? 'primary' : 'warning')
                    ->formatStateUsing(function (Invoice $record) {
                        $record = $record->status == 1 ? 'Paid' : 'Pending';

                        return $record;
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('messages.user.status').':')
                    ->native(false)
                    ->options([
                        '' => __('messages.filter.all'),
                        1 => __('messages.paid'),
                        0 => __('messages.appointment.pending'),
                    ]),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.invoice_updated')),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.invoice_deleted')),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}
