<?php

namespace App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources;

use App\Filament\HospitalAdmin\Clusters\HospitalCharge;
use App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources\ChargeResource\Pages;
use App\Models\Charge;
use App\Models\ChargeCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChargeResource extends Resource
{
    protected static ?string $model = Charge::class;

    protected static ?string $cluster = HospitalCharge::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Charges')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Charges')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.charges');
    }

    public static function getLabel(): string
    {
        return __('messages.charges');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Charges')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Charges')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Charges')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('charge_type')
                    ->label(__('messages.charge_category.charge_type').':')
                    ->options(fn () => getChargeTypeOptions())
                    ->placeholder(__('messages.charge_category.select_charge_type'))
                    ->required()
                    ->native(false)
                    ->preload()
                    ->searchable()
                    ->live()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.charge_category.charge_type').' '.__('messages.fields.required'),
                    ]),
                Select::make('charge_category_id')
                    ->live()
                    ->options(function ($get) {
                        if ($get('charge_type')) {
                            return ChargeCategory::where('charge_type', $get('charge_type'))->whereTenantId(auth()->user()->tenant_id)->pluck('name', 'id');
                        }

                        return [];
                    })
                    ->label(__('messages.charge.charge_category').':')
                    ->placeholder(__('messages.pathology_category.select_charge_category'))
                    ->native(false)
                    ->searchable()
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.charge.charge_category').' '.__('messages.fields.required'),
                    ]),
                TextInput::make('code')
                    ->label(__('messages.charge.code').':')
                    ->placeholder(__('messages.charge.code'))
                    ->required()
                    ->validationMessages([
                        'unique' => __('messages.charge.code').' '.__('messages.common.is_already_exists'),
                    ])
                    ->maxLength(255),
                TextInput::make('standard_charge')
                    ->label(__('messages.charge.standard_charge').':')
                    ->placeholder(__('messages.charge.standard_charge'))
                    ->required()
                    ->validationAttribute(__('messages.charge.standard_charge'))
                    ->maxLength(255)
                    ->numeric()
                    ->minValue(1),
                Textarea::make('description')
                    ->label(__('messages.common.description').':')
                    ->placeholder(__('messages.common.description'))
                    ->rows(4)
                    ->required()
                    ->validationAttribute(__('messages.common.description'))
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Charges')) {
            abort(404);
        }

        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(auth()->user()->tenant_id);

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    Tables\Columns\TextColumn::make('code')
                        ->label(__('messages.charge.code'))
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('chargeCategory.name')
                        ->label(__('messages.charge.charge_category'))
                        ->searchable()
                        ->color('primary')
                        ->formatStateUsing(fn ($record) => "<a href='".ChargeResource::getUrl('view', ['record' => $record->id])."' onmouseover=''>".$record->chargeCategory->name.'</a>')
                        ->html()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('charge_type')
                        ->label(__('messages.charge_category.charge_type'))
                        ->getStateUsing(fn ($record) => getChargeTypeName($record->charge_type))
                        ->badge()
                        ->color('primary')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('standard_charge')
                        ->label(__('messages.charge.standard_charge'))
                        ->getStateUsing(fn ($record) => getCurrencyFormat($record->standard_charge) ?? __('messages.common.n/a'))
                        ->searchable()
                        ->sortable(),
                ])
                ->recordAction(null)
                ->recordUrl(null)
                ->filters([
                    SelectFilter::make('charge_type')
                        ->label(__('messages.charge_category.charge_type'))
                        ->options(fn () => getChargeTypeOptions())
                        ->native(false),
                ])
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.charge_updated'))->before(fn ($record, $data, $action) => getUniqueCodeValidation(static::getModel(), $record, $data, $action, isEdit: true)),
                    Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.charge_deleted')),
                ])
                ->actionsColumnLabel(__('messages.common.action'))
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
            'view' => Pages\ViewCharges::route('/{record}'),
            'index' => Pages\ManageCharges::route('/'),
        ];
    }
}
