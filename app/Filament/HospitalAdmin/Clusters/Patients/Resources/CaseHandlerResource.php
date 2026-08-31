<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources;

use App\Filament\HospitalAdmin\Clusters\Patients;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\CaseHandlerResource\Pages;
use App\Models\CaseHandler;
use App\Models\EmployeePayroll;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class CaseHandlerResource extends Resource
{
    protected static ?string $model = CaseHandler::class;

    protected static ?string $cluster = Patients::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Case Manager'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Case Handlers')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Case Handlers')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.case_handlers');
    }

    public static function getLabel(): string
    {
        return __('messages.case_handlers');
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
        if ($form->getOperation() === 'edit') {
            $caseHandler = $form->model;
            $form->model = User::find($caseHandler->user_id);
        }

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Hidden::make('avatar_remove'),
                        TextInput::make('first_name')
                            ->label(__('messages.user.first_name').':')
                            ->placeholder(__('messages.user.first_name'))
                            ->required()
                            ->validationAttribute(__('messages.user.first_name'))
                            ->live()
                            ->afterStateUpdated(fn ($set, $get) => $set('hospital_name', $get('first_name')))
                            ->maxLength(500),
                        Hidden::make('hospital_name'),
                        // ->getStateToDehydrate(fn($set, $get) => $set('hospital_name', $get('first_name'))),
                        TextInput::make('last_name')
                            ->label(__('messages.user.last_name').':')
                            ->placeholder(__('messages.user.last_name'))
                            ->required()
                            ->validationAttribute(__('messages.user.last_name'))
                            ->maxLength(500),
                        TextInput::make('email')
                            ->unique(
                                'users',
                                'email',
                                null,
                                false,
                                function ($rule, $record, $operation) {
                                    if ($record) {
                                        if ($operation == 'edit') {
                                            $rule->whereNot('id', $record->id);
                                        } else {
                                            $rule->whereNot('id', $record->user->id);
                                        }
                                    }

                                    return $rule;
                                }
                            )
                            ->validationMessages([
                                'unique' => __('messages.user.email').' '.__('messages.common.is_already_exists'),
                            ])
                            ->label(__('messages.user.email').':')
                            ->placeholder(__('messages.user.email'))
                            ->email()
                            ->validationAttribute(__('messages.user.email'))
                            ->required(),
                        TextInput::make('designation')
                            ->label(__('messages.user.designation').': ')
                            ->required()
                            ->validationAttribute(__('messages.user.designation'))
                            ->placeholder(__('messages.user.designation')),
                        PhoneInput::make('phone')
                            ->defaultCountry('IN')
                            ->rules(function ($get) {
                                return [
                                    'phone:AUTO,'.strtoupper($get('prefix_code')),
                                ];
                            })
                            ->countryStatePath('region_code')
                            ->afterStateHydrated(function ($component, $record, $operation) {
                                if ($operation == 'edit') {
                                    if (! empty($record->phone)) {
                                        $phoneNumber = (empty($record->region_code) ? '+' : $record->region_code).getPhoneNumber($record->phone);
                                    } else {
                                        $phoneNumber = getPhoneNumber($record->phone);
                                    }
                                    $component->state($phoneNumber);
                                }
                            })
                            ->validationMessages([
                                'phone' => __('messages.common.invalid_number'),
                            ])
                            ->label(__('messages.user.phone').':'),
                        Hidden::make('region_code'),
                        Group::make()->schema([
                            Radio::make('gender')
                                ->label(__('messages.user.gender').':')
                                ->required()
                                ->validationAttribute(__('messages.user.gender'))
                                ->options([
                                    '0' => __('messages.user.male'),
                                    '1' => __('messages.user.female'),
                                ])
                                ->columns(2),
                            Toggle::make('status')
                                ->default(1)
                                ->label(__('messages.user.status').':')
                                ->inline(false),
                        ])->columns(2),
                        TextInput::make('qualification')
                            ->label(__('messages.user.qualification').':')
                            ->placeholder(__('messages.user.qualification'))
                            ->validationAttribute(__('messages.user.qualification'))
                            ->required(),
                        DatePicker::make('dob')
                            ->native(false)
                            ->maxDate(today())
                            ->live()
                            ->placeholder(__('messages.user.dob'))
                            ->suffix(fn($state) => $state
                                ? \Carbon\Carbon::parse($state)->age . ' years'
                                : '')
                            ->label(__('messages.user.dob') . ':'),
                        Select::make('blood_group')
                            ->label(__('messages.user.blood_group').':')
                            ->options(
                                getBloodGroups()
                            )
                            ->native(false),
                        Group::make()->schema([
                            Forms\Components\TextInput::make('password')
                                ->revealable()
                                ->visible(function (?string $operation) {
                                    return $operation == 'create';
                                })
                                ->rules(['min:8', 'max:20'])
                                ->confirmed()
                                ->label(__('messages.user.password').':')
                                ->placeholder(__('messages.user.password'))
                                ->required()
                                ->validationAttribute(__('messages.user.password'))
                                ->password()
                                ->maxLength(20),

                            TextInput::make('password_confirmation')
                                ->dehydrated(false)
                                ->visible(function (?string $operation) {
                                    return $operation == 'create';
                                })
                                ->label(__('messages.user.password_confirmation').':')
                                ->placeholder(__('messages.user.password_confirmation'))
                                ->revealable()
                                ->required()
                                ->validationAttribute(__('messages.user.password_confirmation'))
                                ->password()
                                ->maxLength(20),
                        ])->columns(2),
                        SpatieMediaLibraryFileUpload::make('user.profile')
                            ->label(__('messages.common.profile').':')
                            ->avatar()
                            ->image()
                            ->disk(config('app.media_disk'))
                            ->collection(User::COLLECTION_PROFILE_PICTURES),
                        Fieldset::make(__('messages.user.address_details'))
                            ->schema([
                                TextInput::make('address1')
                                    ->label(__('messages.user.address1').':')
                                    ->placeholder(__('messages.user.address1')),
                                TextInput::make('address2')
                                    ->label(__('messages.user.address2').':')
                                    ->placeholder(__('messages.user.address2')),
                                Group::make()->schema([
                                    TextInput::make('city')
                                        ->label(__('messages.user.city').':')
                                        ->placeholder(__('messages.user.city')),
                                    TextInput::make('zip')
                                        ->label(__('messages.user.zip').':')
                                        ->placeholder(__('messages.user.zip')),
                                ])->columns(2),
                            ])->columns(2),

                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Case Handlers')) {
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
                    SpatieMediaLibraryImageColumn::make('user.profile')
                        ->label(__('messages.invoice.patient'))
                        ->circular()
                        ->defaultImageUrl(function ($record) {
                            if (! $record->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                                return getUserImageInitial($record->id, $record->user->full_name);
                            }
                        })
                        ->sortable(['first_name'])
                        ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->id]))
                        ->collection('profile')
                        ->width(50)->height(50),
                    TextColumn::make('user.full_name')
                        ->label('')
                        ->html()
                        ->color('primary')
                        ->weight(FontWeight::SemiBold)
                        ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->id]).'"class="hoverLink">'.$record->user->full_name.'</a>')
                        ->description(fn ($record) => $record->user->email ?? __('messages.common.n/a'))
                        ->searchable(['first_name', 'last_name', 'email']),
                    PhoneColumn::make('user.phone')
                        ->label(__('messages.user.phone'))
                        ->default(__('messages.common.n/a'))
                        ->formatStateUsing(function ($state, $record) {
                            if (str_starts_with($state, '+') && strlen($state) > 4) {
                                return $state;
                            }
                            if (empty($record->user->phone)) {
                                return __('messages.common.n/a');
                            }

                            return $record->user->region_code.$record->user->phone;
                        })
                        ->sortable()
                        ->searchable(),
                    TextColumn::make('user.qualification')
                        ->label(__('messages.user.qualification'))
                        ->getStateUsing(fn ($record) => $record->user->qualification ?? __('messages.common.n/a'))
                        ->sortable()
                        ->searchable(),
                    TextColumn::make('user.dob')
                        ->label(__('messages.user.dob'))
                        ->sortable()
                        ->searchable()
                        ->badge()
                        ->getStateUsing(fn ($record) => $record->user->dob ? \Carbon\Carbon::parse($record->user->dob)->isoFormat('Do MMM, Y') : __('messages.common.n/a')),
                    ToggleColumn::make('user.status')
                        ->label(__('messages.user.status'))
                        ->afterStateUpdated(function () {
                            Notification::make()
                                ->title(__('messages.common.status_updated_successfully'))
                                ->success()
                                ->send();
                        })
                        ->searchable(),
                ])
                ->filters([
                    Filter::make(__('messages.user.status'))
                        ->form([
                            Select::make(__('messages.user.status'))
                                ->options([
                                    'all' => __('messages.filter.all'),
                                    1 => __('messages.filter.active'),
                                    0 => __('messages.filter.deactive'),
                                ])->default('all')->native(false)
                                ->label(__('messages.user.status').':'),
                        ])->query(function (Builder $query, array $data) {
                            if ($data[__('messages.common.status')] == 'All') {
                                $query->with('user');
                            }
                            if ($data[__('messages.common.status')] == 1) {
                                $query->with('user')->whereHas('user', fn (Builder $query) => $query->where('status', 1));
                            } elseif ($data[__('messages.common.status')] == 0) {
                                $query->with('user')->whereHas('user', fn (Builder $query) => $query->where('status', 0));
                            }
                        }),
                ])
                ->recordAction(null)
                ->recordUrl(null)
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton(),
                    Tables\Actions\DeleteAction::make()->iconButton()->action(function ($record) {
                        $caseHandler = $record;
                        if (! canAccessRecord($caseHandler, $caseHandler->id)) {
                            return Notification::make()
                                ->title(__('messages.flash.case_handler_not_found'))
                                ->success()
                                ->send();
                        }

                        $caseHandlersModels = [
                            EmployeePayroll::class,
                        ];
                        $result = canDelete($caseHandlersModels, 'owner_id', $caseHandler->id);
                        if ($result) {
                            return Notification::make()
                                ->title(__('messages.flash.case_handler_cant_deleted'))
                                ->success()
                                ->send();
                        }

                        $caseHandler->user()->delete();
                        $caseHandler->address()->delete();
                        $caseHandler->delete();

                        return Notification::make()
                            ->title(__('messages.flash.case_handler_deleted'))
                            ->success()
                            ->send();
                    }),
                ])
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
            'index' => Pages\ListCaseHandlers::route('/'),
            'create' => Pages\CreateCaseHandler::route('/create'),
            'view' => Pages\ViewCaseHandler::route('/{record}'),
            'edit' => Pages\EditCaseHandler::route('/{record}/edit'),
        ];
    }
}
