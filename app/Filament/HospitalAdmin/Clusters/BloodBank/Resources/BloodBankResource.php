<?php

namespace App\Filament\HospitalAdmin\Clusters\BloodBank\Resources;

use App\Filament\HospitalAdmin\Clusters\BloodBank as BloodBankCluster;
use App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodBankResource\Pages;
use App\Models\BloodBank;
use App\Models\BloodDonor;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BloodBankResource extends Resource
{
    protected static ?string $model = BloodBank::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = BloodBankCluster::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Blood Banks')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Blood Banks')) {
            return false;
        }

        return true;
    }

    public static function getLabel(): string
    {
        return __('messages.blood_bank');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Banks')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Banks')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Banks')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('blood_group')
                    ->required()
                    ->validationAttribute(__('messages.user.blood_group'))
                    ->placeholder(__('messages.user.blood_group'))
                    ->label(__('messages.user.blood_group'))
                    ->maxLength(191),
                Forms\Components\TextInput::make('remained_bags')
                    ->required()
                    ->validationAttribute(__('messages.hospital_blood_bank.remained_bags'))
                    ->placeholder(__('messages.hospital_blood_bank.remained_bags'))
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[e\+\-]/gi, '')"])
                    ->label(__('messages.hospital_blood_bank.remained_bags'))
                    ->numeric(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && ! getModuleAccess('Blood Banks')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('blood_group')
                    ->label(__('messages.user.blood_group'))
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remained_bags')
                    ->numeric()
                    ->label(__('messages.hospital_blood_bank.remained_bags'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordAction(null)
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.blood_bank_updated'))->modalButton(__('messages.common.save'))->modalHeading(__('messages.hospital_blood_bank.edit_blood_group')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (BloodBank $record) {
                        if (! canAccessRecord(BloodBank::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.blood_bank_not_found'))
                                ->send();
                        }

                        $bloodBankModel = [
                            BloodDonor::class,
                            User::class,
                        ];
                        $result = canDelete($bloodBankModel, 'blood_group', $record->blood_group);
                        if ($result) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.blood_bank_cant_deleted'))
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.blood_bank_deleted'))
                            ->send();
                    }),
            ])->actionsColumnLabel(__('messages.common.action'))
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
            'index' => Pages\ManageBloodBanks::route('/'),
        ];
    }
}
