<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources;

use App\Filament\HospitalAdmin\Clusters\Medicine;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBrandResource\Pages;
use App\Models\Brand;
use App\Models\Medicine as ModelsMedicine;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class MedicineBrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $cluster = Medicine::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Medicine Brands')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Medicine Brands')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.medicine_brands');
    }

    public static function getLabel(): string
    {
        return __('messages.medicine_brands');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician']) && getModuleAccess('Medicine Brands')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician']) && getModuleAccess('Medicine Brands')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician']) && getModuleAccess('Medicine Brands')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician'])) {
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
                            ->label(__('messages.medicine.brand').':')
                            ->placeholder(__('messages.medicine.brand'))
                            ->validationMessages([
                                'unique' => __('messages.medicine.brand').' '.__('messages.common.is_already_exists'),
                            ])
                            ->required(),
                        PhoneInput::make('phone')
                            ->label(__('messages.user.phone').':')
                            ->defaultCountry('IN')
                            ->validationMessages([
                                'phone' => __('messages.common.invalid_number'),
                            ])
                            ->defaultCountry('IN')
                            ->showSelectedDialCode(true)
                            ->countryStatePath('region_code'),
                        TextInput::make('email')
                            ->label(__('messages.user.email').':')
                            ->placeholder(__('messages.user.email'))
                            ->email()
                            ->validationMessages([
                                'unique' => __('messages.user.email').' '.__('messages.common.is_already_exists'),
                            ])
                            ->unique('brands', 'email', ignoreRecord: true),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Medicine Brands')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.medicine.brand'))
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record): string => route('filament.hospitalAdmin.medicine.resources.medicine-brands.view', $record)),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('messages.user.email'))
                    ->getStateUsing(fn ($record) => $record->email ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('messages.user.phone'))
                    ->getStateUsing(fn ($record) => $record->phone ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (Brand $record) {
                        if (! canAccessRecord(Brand::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.brand_not_found'))
                                ->send();
                        }
                        $medicineBrandModel = [
                            ModelsMedicine::class,
                        ];
                        $result = canDelete($medicineBrandModel, 'brand_id', $record->id);
                        if ($result) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.medicine_brand_cant_deleted'))
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.medicine_brand_deleted'))
                            ->send();
                    }),
            ])
            ->recordUrl(false)
            ->actionsColumnLabel(__('messages.common.action'))
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
            'index' => Pages\ListMedicineBrands::route('/'),
            'create' => Pages\CreateMedicineBrand::route('/create'),
            'edit' => Pages\EditMedicineBrand::route('/{record}/edit'),
            'view' => Pages\ViewMedicineBrand::route('/{record}'),
        ];
    }
}
