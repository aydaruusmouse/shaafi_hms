<?php

namespace App\Filament\HospitalAdmin\Clusters\BloodBank\Resources;

use App\Filament\HospitalAdmin\Clusters\BloodBank;
use App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodDonorResource\Pages;
use App\Models\BloodBank as BloodBankModel;
use App\Models\BloodDonation;
use App\Models\BloodDonor;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BloodDonorResource extends Resource
{
    protected static ?string $model = BloodDonor::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = BloodBank::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Blood Donors')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Blood Donors')) {
            return false;
        }

        return true;
    }

    public static function getLabel(): string
    {
        return __('messages.delete.blood_donor');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Donors')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Donors')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Donors')) {
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
                Forms\Components\TextInput::make('name')
                    ->label(__('messages.common.name'))
                    ->placeholder(__('messages.common.name'))
                    ->required()
                    ->validationAttribute(__('messages.common.name'))
                    ->maxLength(191),
                Forms\Components\TextInput::make('age')
                    ->label(__('messages.blood_donor.age'))
                    ->placeholder(__('messages.blood_donor.age'))
                    ->required()
                    ->validationAttribute(__('messages.blood_donor.age'))
                    ->minValue(18)
                    ->numeric(),
                Radio::make('gender')
                    ->label(__('messages.blood_donor.gender'))
                    ->inline(true)
                    ->required()
                    ->validationAttribute(__('messages.blood_donor.gender'))
                    ->options([
                        1 => __('messages.user.male'),
                        0 => __('messages.user.female'),
                    ]),
                Forms\Components\Select::make('blood_group')
                    ->required()
                    ->options(BloodBankModel::where('tenant_id', auth()->user()->tenant_id)->orderBy('blood_group', 'asc')->pluck('blood_group', 'blood_group'))
                    ->searchable()
                    ->native(false)
                    ->label(__('messages.user.blood_group'))
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.user.blood_group').' '.__('messages.fields.required'),
                    ]),
                Forms\Components\DatePicker::make('last_donate_date')
                    ->label(__('messages.blood_donor.last_donation_date'))
                    ->native(false)
                    ->validationAttribute(__('messages.blood_donor.last_donation_date'))
                    ->required(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && ! getModuleAccess('Blood Donors')) {
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
                    ->sortable()
                    ->label(__('messages.common.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('age')
                    ->searchable()
                    ->badge()
                    ->label(__('messages.blood_donor.age'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label(__('messages.blood_donor.gender'))
                    ->formatStateUsing(function ($record) {
                        return $record->gender == 0 ? 'Female' : 'Male';
                    })
                    ->badge()
                    ->color(function ($record) {
                        return $record->gender == 0 ? 'success' : 'primary';
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('blood_group')
                    ->label(__('messages.blood_donor.blood_group'))
                    ->badge()
                    ->color('danger')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_donate_date')
                    ->view('tables.columns.hospitalAdmin.last_donate_date')
                    ->label(__('messages.blood_donor.last_donation_date'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordAction(null)
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.blood_donor_updated'))->modalButton(__('messages.common.save'))->modalHeading(__('messages.blood_donor.edit_blood_donor')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (BloodDonor $record) {
                        if (! canAccessRecord(BloodDonor::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.blood_donor_not_found'))
                                ->send();
                        }
                        $bloodDonorModel = [BloodDonation::class];
                        $result = canDelete($bloodDonorModel, 'blood_donor_id', $record->id);
                        if ($result) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.blood_donor_cant_delete'))
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.blood_donor_delete'))
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
            'index' => Pages\ManageBloodDonors::route('/'),
        ];
    }
}
