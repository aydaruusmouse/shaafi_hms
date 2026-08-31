<?php

namespace App\Filament\HospitalAdmin\Clusters\Finance\Resources;

use App\Filament\HospitalAdmin\Clusters\Finance;
use App\Filament\HospitalAdmin\Clusters\Finance\Resources\IncomesResource\Pages;
use App\Models\Income;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IncomesResource extends Resource
{
    protected static ?string $model = Income::class;

    protected static ?string $cluster = Finance::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Income')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Income')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.incomes.incomes');
    }

    public static function getLabel(): ?string
    {
        return __('messages.incomes.incomes');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Income')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Income')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Income')) {
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
                Forms\Components\Select::make('income_head')
                    ->label(__('messages.incomes.income_head').':')
                    ->placeholder(__('messages.incomes.select_income_head'))
                    ->options([
                        '' => __('messages.incomes.select_income_head'),
                        1 => __('messages.income_filter.canteen_rate'),
                        2 => __('messages.income_filter.hospital_charges'),
                        3 => __('messages.income_filter.special_campaign'),
                        4 => __('messages.income_filter.vehicle_stand_charge'),
                    ])
                    ->native(false)
                    ->preload()
                    ->searchable()
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.incomes.income_head').' '.__('messages.fields.required'),
                    ]),
                TextInput::make('name')
                    ->validationMessages([
                        'unique' => __('messages.user.name').' '.__('messages.common.is_already_exists'),
                    ])
                    ->label(__('messages.incomes.name').':')
                    ->placeholder(__('messages.incomes.name'))
                    ->required(),
                DatePicker::make('date')
                    ->native(false)
                    ->label(__('messages.incomes.date').':')
                    ->validationAttribute(__('messages.incomes.date'))
                    ->required(),
                TextInput::make('invoice_number')
                    ->label(__('messages.incomes.invoice_number'))
                    ->placeholder(__('messages.incomes.invoice_number')),
                TextInput::make('amount')
                    ->label(__('messages.incomes.amount'))
                    ->placeholder(__('messages.incomes.amount'))
                    ->numeric()
                    ->minValue(1)
                    ->validationAttribute(__('messages.incomes.amount'))
                    ->required(),
                Forms\Components\SpatieMediaLibraryFileUpload::make('document_url')
                    ->label(__('messages.document.attachment').':')
                    ->disk(config('app.media_disk'))
                    ->placeholder(__('messages.document.attachment')),
                Textarea::make('description')
                    ->label(__('messages.incomes.description'))
                    ->placeholder(__('messages.incomes.description'))->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant']) && ! getModuleAccess('Income')) {
            abort(404);
        }

        return $table = $table->modifyQueryUsing(function (Builder $query) {
            $query->whereTenantId(auth()->user()->tenant_id);

            return $query;
        })
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('messages.incomes.invoice_number'))
                    ->searchable()
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->invoice_number ?? __('messages.common.n/a'))
                    ->color('info')
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('messages.incomes.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('income_head')
                    ->label(__('messages.incomes.income_head'))
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if ($record->income_head == 2) {
                            return __('messages.income_filter.hospital_charges');
                        } elseif ($record->income_head == 3) {
                            return __('messages.income_filter.special_campaign');
                        } elseif ($record->income_head == 4) {
                            return __('messages.income_filter.vehicle_stand_charge');
                        } else {
                            return __('messages.income_filter.canteen_rate');
                        }
                    })
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('messages.incomes.date'))
                    ->getStateUsing(fn ($record) => $record->date ? Carbon::parse($record->date)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('messages.incomes.amount'))
                    ->getStateUsing(fn ($record) => getCurrencyFormat($record->amount) ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attachment')
                    ->label(__('messages.incomes.attachment'))
                    ->getStateUsing(function ($record) {
                        if ($record->document_url) {
                            return '<a href="'.$record->document_url.'" style="margin-left: -17px; color: #4F46E5;" download>Download</a>';
                        }

                        return __('messages.common.n/a');
                    })
                    ->html(),
            ])
            ->filters([
                SelectFilter::make('income_head')
                    ->label(__('messages.common.status'))
                    ->placeholder(__('messages.filter.all'))
                    ->options([
                        __('messages.common.all') => __('messages.incomes.select_income_head'),
                        1 => __('messages.income_filter.canteen_rate'),
                        2 => __('messages.income_filter.hospital_charges'),
                        3 => __('messages.income_filter.special_campaign'),
                        4 => __('messages.income_filter.vehicle_stand_charge'),
                    ])->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.income_updated'))->before(fn ($record, $data, $action) => getUniqueNameValidation(static::getModel(), $record, $data, $action, isEdit: true)),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.income_deleted')),
            ])->actionsColumnLabel(__('messages.common.actions'))
            ->recordAction(null)
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
            'index' => Pages\ManageIncomes::route('/'),
        ];
    }
}
