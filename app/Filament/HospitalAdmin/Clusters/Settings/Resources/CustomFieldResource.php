<?php

namespace App\Filament\HospitalAdmin\Clusters\Settings\Resources;

use App\Filament\HospitalAdmin\Clusters\Settings;
use App\Filament\HospitalAdmin\Clusters\Settings\Resources\CustomFieldResource\Pages;
use App\Models\CustomField;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomFieldResource extends Resource
{
    protected static ?string $model = CustomField::class;

    protected static ?string $cluster = Settings::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('messages.custom_field.custom_field');
    }

    public static function getLabel(): string
    {
        return __('messages.custom_field.custom_field');
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
                Select::make('module_name')
                    ->label(__('messages.custom_field.module_name').':')
                    ->placeholder(__('messages.custom_field.select_module'))
                    ->options(CustomField::MODULE_TYPE_ARR)
                    ->native(false)
                    ->preload()
                    ->searchable()
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.custom_field.module_name').' '.__('messages.fields.required'),
                    ]),
                Select::make('field_type')
                    ->label(__('messages.custom_field.field_type').':')
                    ->placeholder(__('messages.custom_field.select_field_type'))
                    ->options(CustomField::FIELD_TYPE_ARR)
                    ->native(false)
                    ->live()
                    ->preload()
                    ->searchable()
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.custom_field.field_type').' '.__('messages.fields.required'),
                    ]),
                Forms\Components\TextInput::make('field_name')
                    ->label(__('messages.custom_field.field_name').':')
                    ->placeholder(__('messages.custom_field.field_name'))
                    ->required()
                    ->validationAttribute(__('messages.custom_field.field_name'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('grid')
                    ->label(__('messages.custom_field.grid').':')
                    ->placeholder(__('messages.custom_field.grid'))
                    ->required()
                    ->validationAttribute(__('messages.custom_field.grid'))
                    ->prefix(__('messages.custom_field.grid'))
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('messages.custom_field.grid_tooltip'))
                    ->minValue(6)
                    ->maxValue(12)
                    ->numeric()
                    ->maxLength(255),
                Forms\Components\TextInput::make('values')
                    ->label(__('messages.custom_field.value').' ('.__('messages.custom_field.seperated_by_comma').') :')
                    ->placeholder(__('messages.custom_field.value'))
                    ->visible(function ($get) {
                        if ($get('field_type') && $get('field_type') == 4 || $get('field_type') == 5) {
                            return true;
                        }

                        return false;
                    })
                    ->validationAttribute(__('messages.custom_field.value'))
                    ->required(function ($get) {
                        if ($get('field_type') && $get('field_type') == 4 || $get('field_type') == 5) {
                            return true;
                        }

                        return false;
                    }),
                Toggle::make('is_required')
                    ->label(__('messages.custom_field.is_reqired').':')
                    ->live(),
            ]);
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
                    TextColumn::make('module_name')
                        ->label(__('messages.custom_field.module_name'))
                        ->searchable()
                        ->getStateUsing(function ($record) {
                            if ($record->module_name == 0) {
                                return __('messages.custom_field.appointment');
                            } elseif ($record->module_name == 1) {
                                return __('messages.custom_field.ipd_patient');
                            } elseif ($record->module_name == 2) {
                                return __('messages.custom_field.opd_patient');
                            } elseif ($record->module_name == 3) {
                                return __('messages.custom_field.patient');
                            }
                        })
                        ->sortable(),
                    TextColumn::make('field_type')
                        ->label(__('messages.custom_field.field_type'))
                        ->searchable()
                        ->getStateUsing(function ($record) {
                            if ($record->field_type == 0) {
                                return __('messages.custom_field.text');
                            } elseif ($record->field_type == 1) {
                                return __('messages.custom_field.textarea');
                            } elseif ($record->field_type == 2) {
                                return __('messages.custom_field.toggle');
                            } elseif ($record->field_type == 3) {
                                return __('messages.custom_field.number');
                            } elseif ($record->field_type == 4) {
                                return __('messages.custom_field.dropdown');
                            } elseif ($record->field_type == 5) {
                                return __('messages.custom_field.multi_select');
                            } elseif ($record->field_type == 6) {
                                return __('messages.custom_field.date');
                            } elseif ($record->field_type == 7) {
                                return __('messages.custom_field.date_time');
                            }
                        })
                        ->sortable(),
                    TextColumn::make('field_name')
                        ->label(__('messages.custom_field.field_name'))
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('values')
                        ->label(__('messages.custom_field.value'))
                        ->searchable()
                        ->getStateUsing(function ($record) {
                            if (! empty($record->values)) {
                                return $record->values;
                            } else {
                                return __('messages.common.n/a');
                            }
                        })
                        ->sortable(),
                ])
                ->filters([
                    //
                ])
                ->recordAction(null)
                ->actionsColumnLabel(__('messages.common.action'))
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.custom_field.custom_field').' '.__('messages.common.updated_successfully')),
                    Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.custom_field.custom_field').' '.__('messages.common.deleted_successfully')),
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
            'index' => Pages\ManageCustomFields::route('/'),
        ];
    }
}
