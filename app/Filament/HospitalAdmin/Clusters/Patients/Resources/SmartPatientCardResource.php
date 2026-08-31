<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources;

use App\Filament\HospitalAdmin\Clusters\Patients;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\SmartPatientCardResource\Pages;
use App\Models\Patient;
use App\Models\SmartPatientCard;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SmartPatientCardResource extends Resource
{
    protected static ?string $model = SmartPatientCard::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = Patients::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Case Manager'])) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Patients')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.lunch_break.smart_patient_card_template');
    }

    public static function getLabel(): string
    {
        return __('messages.lunch_break.smart_patient_card_template');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
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
                        Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('template_name')
                                    ->label(__('messages.lunch_break.template_name'))
                                    ->placeholder(__('messages.lunch_break.template_name'))
                                    ->required()
                                    ->validationAttribute(__('messages.lunch_break.template_name'))
                                    ->maxLength(191),
                                ViewField::make('header_color')
                                    ->live()
                                    ->view('forms.components.color-picker'),
                                // TextInput::make('header_color')
                                //     ->live()
                                //     ->afterStateUpdated(function (Forms\Set $set, $state, $component) {
                                //         dd($component->getState());
                                //         $record = $state;
                                //         return $record;
                                //     })
                                //     ->id('header_color'),

                                Forms\Components\Checkbox::make('show_email')
                                    ->default(true)
                                    ->id('show_email'),

                                Forms\Components\Checkbox::make('show_phone')
                                    ->id('show_phone')
                                    ->default(true),

                                Forms\Components\Checkbox::make('show_dob')
                                    ->id('show_dob')
                                    ->default(true),

                                Forms\Components\Checkbox::make('show_blood_group')
                                    ->id('show_blood_group')
                                    ->default(true),

                                Forms\Components\Checkbox::make('show_address')
                                    ->id('show_address')
                                    ->default(true),

                                Forms\Components\Checkbox::make('show_patient_unique_id')
                                    ->id('show_patient_unique_id')
                                    ->default(true),

                                Forms\Components\Checkbox::make('show_insurance')
                                    ->id('show_insurance')
                                    ->default(true),
                            ]),
                        Group::make()
                            ->schema([
                                ViewField::make('')
                                    ->live()
                                    ->view('forms.components.smart-card'),
                            ]),

                    ])->columns(2),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        $table = $table->modifyQueryUsing(function (Builder $query) {
            $query->where('tenant_id', Auth::user()->tenant_id);

            return $query;
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('template_name')
                    ->sortable()
                    ->label(__('messages.lunch_break.template_name'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('header_color')
                    ->sortable()
                    ->label(__('messages.lunch_break.header_color'))
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\ToggleColumn::make('show_email')
                    ->sortable()
                    ->updateStateUsing(function (SmartPatientCard $user, bool $state) {
                        $state ? $user->show_email = 1 : $user->show_email = 0;
                        $user->save();
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    })
                    ->label(__('messages.lunch_break.show_email')),

                Tables\Columns\ToggleColumn::make('show_phone')
                    ->sortable()
                    ->updateStateUsing(function (SmartPatientCard $user, bool $state) {
                        $state ? $user->show_phone = 1 : $user->show_phone = 0;
                        $user->save();
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    })
                    ->label(__('messages.lunch_break.show_phone')),

                Tables\Columns\ToggleColumn::make('show_dob')
                    ->sortable()
                    ->updateStateUsing(function (SmartPatientCard $user, bool $state) {
                        $state ? $user->show_dob = 1 : $user->show_dob = 0;
                        $user->save();
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    })
                    ->label(__('messages.lunch_break.show_dob')),

                Tables\Columns\ToggleColumn::make('show_blood_group')
                    ->sortable()
                    ->updateStateUsing(function (SmartPatientCard $user, bool $state) {
                        $state ? $user->show_blood_group = 1 : $user->show_blood_group = 0;
                        $user->save();
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    })
                    ->label(__('messages.lunch_break.show_blood_group')),

                Tables\Columns\ToggleColumn::make('show_address')
                    ->sortable()
                    ->updateStateUsing(function (SmartPatientCard $user, bool $state) {
                        $state ? $user->show_address = 1 : $user->show_address = 0;
                        $user->save();
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    })
                    ->label(__('messages.lunch_break.show_address')),

                Tables\Columns\ToggleColumn::make('show_patient_unique_id')
                    ->sortable()
                    ->updateStateUsing(function (SmartPatientCard $user, bool $state) {
                        $state ? $user->show_patient_unique_id = 1 : $user->show_patient_unique_id = 0;
                        $user->save();
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    })
                    ->label(__('messages.lunch_break.show_patient_unique_id')),

                Tables\Columns\ToggleColumn::make('show_insurance')
                    ->sortable()
                    ->updateStateUsing(function (SmartPatientCard $user, bool $state) {
                        $state ? $user->show_insurance = 1 : $user->show_insurance = 0;
                        $user->save();
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    })
                    ->label(__('messages.lunch_break.show_insurance')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->url(fn ($record) => static::getUrl('edit', ['record' => $record->id]).'?record=1'),
                Tables\Actions\DeleteAction::make()->iconButton()
                    ->action(function ($record) {
                        $templateExist = Patient::whereTenantId(getLoggedInUser()->tenant_id)->where('template_id', $record->id)->exists();

                        if ($templateExist) {
                            return Notification::make()
                                ->title(__('messages.flash.smart_patient_card_template_already_in_use'))
                                ->warning()
                                ->send();
                        } else {
                            $record->delete();

                            return Notification::make()
                                ->title(__('messages.lunch_break.smart_patient_card').' '.__('messages.common.deleted_successfully'))
                                ->success()
                                ->send();
                        }
                    }),

            ])
            ->actionsColumnLabel(__('messages.common.actions'))
            ->recordUrl(null)
            ->bulkActions([
                //
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
            'index' => Pages\ListSmartPatientCards::route('/'),
            'create' => Pages\CreateSmartPatientCard::route('/create'),
            'edit' => Pages\EditSmartPatientCard::route('/{record}/edit'),
        ];
    }
}
