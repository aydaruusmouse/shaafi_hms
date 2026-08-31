<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources;

use App\Filament\HospitalAdmin\Clusters\Services;
use App\Filament\HospitalAdmin\Clusters\Services\Resources\PackageResource\Pages;
use App\Models\Package;
use App\Models\PatientAdmission;
use App\Repositories\PackageRepository;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redirect;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $cluster = Services::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Packages')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Packages')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.packages');
    }

    public static function getLabel(): string
    {
        return __('messages.packages');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Packages')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Packages')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Packages')) {
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
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('messages.package.package').':')
                            ->placeholder(__('messages.package.package'))
                            ->validationMessages([
                                'unique' => __('messages.package.package').' '.__('messages.common.is_already_exists'),
                            ])
                            ->validationAttribute(__('messages.package.package'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('discount')
                            ->label(__('messages.package.discount').'(%) :')
                            ->live()
                            ->required()
                            ->validationAttribute(__('messages.package.discount'))
                            ->placeholder(__('messages.package.discount'))
                            ->default(0.00)
                            ->afterStateUpdated(function ($state, $get, $set) {
                                if ($state > 100) {
                                    $set('discount', 100);
                                }
                                if (empty($get('discount')) || ! is_string($get('discount'))) {
                                    $set('discount', 0);
                                }
                                self::updateTotal($get, $set);
                            })
                            ->numeric(),
                        Textarea::make('description')
                            ->label(__('messages.package.description').':')
                            ->placeholder(__('messages.package.description'))
                            ->rows(8)
                            ->columnSpanFull()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Repeater::make('package')
                    ->label('')
                    ->live()
                    ->schema([
                        Select::make('service_id')
                            ->label(__('messages.package.service').':')
                            ->placeholder(__('messages.package.select_service'))
                            ->options(app(PackageRepository::class)->getServices())
                            ->native(false)
                            ->searchable()
                            ->required()
                            ->preload()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.package.service').' '.__('messages.fields.required'),
                            ]),
                        TextInput::make('quantity')
                            ->live(debounce: 500)
                            ->numeric()
                            ->required()
                            ->validationAttribute(__('messages.package.qty'))
                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[e\+\-]/gi, '')"])
                            ->afterStateUpdated(function ($get, $set) {
                                if ($get('quantity') == '' || empty($get('quantity')) || ! is_string($get('quantity'))) {
                                    $set('quantity', 0);
                                }
                                self::updateTotal($get, $set);
                            })
                            ->label(__('messages.package.qty'))
                            ->placeholder(__('messages.package.qty')),
                        TextInput::make('rate')
                            ->live(debounce: 500)
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->validationAttribute(__('messages.package.rate'))
                            ->afterStateUpdated(function ($get, $set) {
                                if ($get('rate') == '' || empty($get('rate')) || ! is_string($get('rate'))) {
                                    $set('rate', 0);
                                }
                                self::updateTotal($get, $set);
                            })
                            ->label(__('messages.package.rate'))
                            ->placeholder(__('messages.package.rate')),
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(1)
                            ->default(0)
                            ->label(__('messages.package.amount'))
                            ->live()
                            ->readOnly(),
                        // ->disabled(),
                    ])
                    ->afterStateUpdated(function ($get, $set) {
                        self::updateTotal($get, $set);
                    })
                    ->addActionLabel('Add')
                    ->columns(4)->columnSpanFull(),
                Grid::make('')->columns(6)->schema([
                    Grid::make('')->columns(1)->columnSpan(4),
                    Grid::make('Main')->schema([
                        TextInput::make('total_amount')
                            ->live()
                            ->readOnly()
                            ->label(__('messages.insurance.total_amount').'('.getCurrencySymbol().')'.':')
                            ->inlineLabel(),
                    ])->columnSpan(2),
                ]),
            ]);
    }

    public static function updateTotal($get, $set): void
    {
        $items = collect($get('package'))->values()->toArray();

        $total_amount = 0;

        foreach ($items as $item) {
            $total_amount += (int) $item['rate'] * (int) $item['quantity'];
        }

        $set('amount', ($get('rate')) * ($get('quantity')));
        $set('total_amount', ($total_amount - $total_amount * (int) $get('discount') / 100));
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Packages')) {
            abort(404);
        }

        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(getLoggedInUser()->tenant_id);

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    TextColumn::make('name')
                        ->label(__('messages.package.package'))
                        ->searchable()
                        ->html()
                        ->formatStateUsing(fn ($record) => '<a href="'.PackageResource::getUrl('view', ['record' => $record->id]).'" class="hoverLink">'.$record->name.'</a>')
                        ->color('primary')
                        ->sortable(),
                    TextColumn::make('discount')
                        ->label(__('messages.insurance.service_tax'))
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('total_amount')
                        ->label(__('messages.package.total_amount'))
                        ->getStateUsing(fn ($record) => getCurrencyFormat($record->total_amount) ?? __('messages.common.n/a'))
                        ->searchable()
                        ->sortable(),
                ])
                ->filters([
                    //
                ])
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton()->action(function ($record) {
                        if (! canAccessRecord($record, $record->id)) {
                            Notification::make()
                                ->title(__('messages.flash.not_allow_access_record'))
                                ->danger()
                                ->send();

                            return Redirect::back();
                        }
                    }),
                    Tables\Actions\DeleteAction::make()->iconButton()->action(function ($record) {
                        if (! canAccessRecord($record, $record->id)) {
                            Notification::make()
                                ->title(__('messages.flash.not_allow_access_record'))
                                ->danger()
                                ->send();
                        }
                        $packageModel = [
                            PatientAdmission::class,
                        ];
                        $result = canDelete($packageModel, 'package_id', $record->id);
                        if ($result) {
                            Notification::make()
                                ->title(__('messages.flash.package_cant_deleted'))
                                ->danger()
                                ->send();
                        }
                        app(PackageRepository::class)->delete($record->id);

                        Notification::make()
                            ->title(__('messages.flash.package_deleted'))
                            ->success()
                            ->send();
                    }),
                ])
                ->actionsColumnLabel(__('messages.common.action'))
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
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'view' => Pages\ViewPackage::route('/{record}'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}
