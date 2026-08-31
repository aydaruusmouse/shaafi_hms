<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources;

use App\Filament\HospitalAdmin\Clusters\Services;
use App\Filament\HospitalAdmin\Clusters\Services\Resources\ServiceResource\Pages;
use App\Models\PackageService;
use App\Models\Service;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redirect;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $cluster = Services::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Services')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Services')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.services');
    }

    public static function getLabel(): string
    {
        return __('messages.services');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant', 'Receptionist']) && getModuleAccess('Services')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant', 'Receptionist']) && getModuleAccess('Services')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant', 'Receptionist']) && getModuleAccess('Services')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant', 'Receptionist'])) {
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
                            ->required()
                            ->validationAttribute(__('messages.package.service'))
                            ->label(__('messages.package.service').':')
                            ->maxLength(255)
                            ->placeholder(__('messages.package.service')),

                        TextInput::make('quantity')
                            ->required()
                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[e\+\-]/gi, '')"])
                            ->validationAttribute(__('messages.service.quantity'))
                            ->label(__('messages.service.quantity').':')
                            ->numeric()
                            ->placeholder(__('messages.service.quantity')),

                        TextInput::make('rate')
                            ->required()
                            ->validationAttribute(__('messages.service.rate'))
                            ->label(__('messages.service.rate').':')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder(__('messages.service.rate')),

                        Textarea::make('description')
                            ->label(__('messages.common.description').':')
                            ->placeholder(__('messages.common.description'))
                            ->rows(8)
                            ->columnSpanFull()
                            ->maxLength(255),

                        Toggle::make('status')
                            ->live()
                            ->label(__('messages.common.status').':')
                            ->default(1),

                    ])
                    ->columns(3),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant', 'Receptionist']) && ! getModuleAccess('Services')) {
            abort(404);
        }

        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(getLoggedInUser()->tenant_id);

                return $query;
            })->paginated([10, 25, 50])->defaultSort('id', 'desc')->columns([
                TextColumn::make('name')
                    ->label(__('messages.package.service'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label(__('messages.service.quantity'))
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('rate')
                    ->label(__('messages.service.rate'))
                    ->searchable()
                    ->getStateUsing(fn ($record) => $record->rate != 0 ? getCurrencyFormat($record->rate) : __('messages.common.n/a'))
                    ->sortable(),
                ToggleColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->searchable()
                    ->afterStateUpdated(function () {
                        Notification::make()
                            ->title(__('messages.flash.service_updated'))
                            ->success()
                            ->send();
                    })
                    ->sortable(),
            ])
                ->filters([
                    SelectFilter::make('status')
                        ->label(__('messages.common.status'))
                        ->options([
                            '' => __('messages.filter.all'),
                            '1' => __('messages.filter.active'),
                            '0' => __('messages.filter.deactive'),
                        ])->native(false),
                ])
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton()->action(function ($record) {
                        if (! canAccessRecord($record, $record->id)) {
                            // Flash::error(__('messages.flash.not_allow_access_record'));

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

                        $serviceModel = [
                            PackageService::class,
                        ];
                        $result = canDelete($serviceModel, 'service_id', $record->id);

                        if ($result) {
                            return Notification::make()
                                ->title(__('messages.flash.service_cant_deleted'))
                                ->danger()
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->title(__('messages.flash.service_deleted'))
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'view' => Pages\ViewService::route('/{record}'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
