<?php

namespace App\Filament\HospitalAdmin\Clusters\BloodBank\Resources;

use App\Filament\HospitalAdmin\Clusters\BloodBank;
use App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodDonationResource\Pages;
use App\Models\BloodDonation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BloodDonationResource extends Resource
{
    protected static ?string $model = BloodDonation::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = BloodBank::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Blood Donations')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Blood Donations')) {
            return false;
        }

        return true;
    }

    public static function getLabel(): string
    {
        return __('messages.delete.blood_donation');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Donations')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Donations')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Donations')) {
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
                Forms\Components\Select::make('blood_donor_id')
                    ->label(__('messages.delete.blood_donor'))
                    ->relationship('bloodDonor', 'name', fn ($query) => $query->whereTenantId(getLoggedInUser()->tenant_id))
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.delete.blood_donor').' '.__('messages.fields.required'),
                    ]),
                Forms\Components\TextInput::make('bags')
                    ->label(__('messages.blood_donation.bags'))
                    ->required()
                    ->validationAttribute(__('messages.blood_donation.bags'))
                    ->numeric()
                    ->default(1),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && ! getModuleAccess('Blood Donations')) {
            abort(404);
        }
        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('bloodDonor.name')
                    ->label(__('messages.blood_issue.donor_name'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('bags')
                    ->numeric()
                    ->label(__('messages.blood_donation.bags'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordAction(null)
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.blood_donation_updated'))->modalButton(__('messages.common.save'))->modalHeading(__('messages.blood_donation.edit_blood_donation')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (BloodDonation $record) {
                        if (! canAccessRecord(BloodDonation::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.blood_donation_not_found'))
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.blood_donation_deleted'))
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
            'index' => Pages\ManageBloodDonations::route('/'),
        ];
    }
}
