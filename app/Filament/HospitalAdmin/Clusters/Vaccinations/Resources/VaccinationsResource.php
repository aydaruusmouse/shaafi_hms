<?php

namespace App\Filament\HospitalAdmin\Clusters\Vaccinations\Resources;

use App\Filament\HospitalAdmin\Clusters\Vaccinations;
use App\Filament\HospitalAdmin\Clusters\Vaccinations\Resources\VaccinationsResource\Pages;
use App\Models\VaccinatedPatients;
use App\Models\Vaccination;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VaccinationsResource extends Resource
{
    protected static ?string $model = Vaccination::class;

    protected static ?string $cluster = Vaccinations::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Vaccinations')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Vaccinations')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.vaccination.vaccinations');
    }

    public static function getLabel(): string
    {
        return __('messages.vaccination.vaccinations');
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
                TextInput::make('name')
                    ->label(__('messages.vaccination.name').':')
                    ->placeholder(__('messages.vaccination.name'))
                    ->required()
                    ->validationAttribute(__('messages.vaccination.name'))
                    ->columnSpanFull(),

                TextInput::make('manufactured_by')
                    ->label(__('messages.vaccination.manufactured_by').':')
                    ->placeholder(__('messages.vaccination.manufactured_by'))
                    ->required()
                    ->validationAttribute(__('messages.vaccination.manufactured_by'))
                    ->columnSpanFull(),

                TextInput::make('brand')
                    ->label(__('messages.vaccination.brand').':')
                    ->placeholder(__('messages.vaccination.brand'))
                    ->required()
                    ->validationAttribute(__('messages.vaccination.brand'))
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Vaccinations')) {
            abort(404);
        }
        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.vaccination.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('manufactured_by')
                    ->label(__('messages.vaccination.manufactured_by'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand')
                    ->label(__('messages.vaccination.brand'))
                    ->searchable()
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.vaccination_updated'))->modalHeading(__('messages.vaccination.edit_vaccination')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (Vaccination $record) {
                        if (! canAccessRecord(Vaccination::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.vaccination_not_found'))
                                ->send();
                        }
                        $vaccinatedModels = [
                            VaccinatedPatients::class,
                        ];

                        $result = canDelete($vaccinatedModels, 'vaccination_id', $record->id);

                        if ($result) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.vaccination_cant_deleted'))
                                ->send();
                        }

                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.vaccination_deleted'))
                            ->send();
                    }),
            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->defaultSort('id', 'desc')
            ->recordAction(null)
            ->bulkActions([])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVaccinations::route('/'),
        ];
    }
}
