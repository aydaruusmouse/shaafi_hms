<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HospitalResource\Pages;
use App\Models\Doctor;
use App\Models\HospitalType;
use App\Models\MultiTenant;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class HospitalResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'fas-user-group';

    protected static ?string $modelLabel = 'Hospitals';

    protected static ?string $navigationLabel = 'Hospitals';

    protected static ?string $slug = 'hospitals';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('messages.hospitals');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.hospitals');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')
                    ->schema([
                        TextInput::make('hospital_name')
                            ->label(__('messages.hospitals_list.hospital_name').':')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('username', \Str::slug($state));
                            })
                            ->required()
                            ->validationAttribute(__('messages.hospitals_list.hospital_name'))
                            ->placeholder(__('messages.hospitals_list.hospital_name')),
                        TextInput::make('username')
                            ->label(__('messages.user.hospital_slug').':')
                            ->required()
                            ->validationAttribute(__('messages.user.hospital_slug'))
                            ->unique('users', 'username', ignoreRecord: true)
                            ->disabled(function (?string $operation) {
                                return $operation == 'edit';
                            })
                            ->validationMessages([
                                'unique' => __('messages.user.hospital_slug').' '.__('messages.common.is_already_exists'),
                            ])
                            ->placeholder(__('messages.user.hospital_slug')),
                        Select::make('hospital_type_id')
                            ->required()
                            ->validationAttribute(__('messages.hospital_type'))
                            ->relationship('hospitalType', 'name')
                            ->optionsLimit(count(HospitalType::all()))
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->placeholder(__('messages.hospital_type'))
                            ->label(__('messages.hospital_type').':')
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.hospital_type').' '.__('messages.fields.required'),
                            ]),
                        TextInput::make('email')
                            ->label(__('messages.user.email').':')
                            ->required()
                            ->validationAttribute(__('messages.user.email'))
                            ->unique('users', 'email', ignoreRecord: true)
                            ->placeholder(__('messages.user.email'))
                            ->email()
                            ->validationMessages([
                                'unique' => __('messages.user.email').' '.__('messages.common.is_already_exists'),
                            ]),
                        TextInput::make('city')
                            ->label(__('messages.user.city').':')
                            ->placeholder(__('messages.user.city')),
                        PhoneInput::make('phone')
                            ->defaultCountry('IN')
                            ->rules(function (Get $get) {
                                return [
                                    'required',
                                    'phone:AUTO,'.strtoupper($get('prefix_code')),
                                ];
                            })
                            ->countryStatePath('region_code')
                            ->afterStateHydrated(function ($component, $record, $operation, $state) {
                                if ($operation == 'edit') {
                                    if (! empty($record->phone)) {
                                        $phoneNumber = (empty($record->region_code) ? '+' : $record->region_code).getPhoneNumber($record->phone);
                                    } else {
                                        $phoneNumber = null;
                                    }
                                    $component->state($phoneNumber);
                                }
                            })
                            ->validationMessages([
                                'phone' => __('messages.common.invalid_number'),
                            ])
                            ->label(__('messages.user.phone').':')
                            ->validationAttribute(__('messages.user.phone'))
                            ->required(),
                        Hidden::make('region_code'),
                        TextInput::make('password')
                            ->label(__('messages.user.password').':')
                            ->required()
                            ->validationAttribute(__('messages.user.password'))
                            ->placeholder(__('messages.user.password'))
                            ->password()
                            ->confirmed()
                            ->visible(function (?string $operation) {
                                return $operation == 'create';
                            })
                            ->validationMessages([
                                'confirmed' => __('messages.fields.confirm'),
                            ])
                            ->revealable(),
                        TextInput::make('password_confirmation')
                            ->label(__('messages.user.password_confirmation').':')
                            ->dehydrated(false)
                            ->required()
                            ->validationAttribute(__('messages.user.password_confirmation'))
                            ->revealable()
                            ->visible(function (?string $operation) {
                                return $operation == 'create';
                            })
                            ->placeholder(__('messages.user.password_confirmation'))
                            ->password(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table->modifyQueryUsing(function ($query) {
            $query = User::with(['department', 'media', 'hospitalType'])->select('users.*');

            if (getLoggedInUser()->hasRole('Super Admin')) {
                $query->where('department_id', '=', User::USER_ADMIN)->whereNotNull('hospital_name')->whereNotNull('username');
            }

            return $query;
        });

        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')->collection(User::COLLECTION_PROFILE_PICTURES)->rounded()->label(__('messages.hospital'))->width(50)->height(50)->defaultImageUrl(function ($record) {
                    if (! $record->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                        return getUserImageInitial($record->id, $record->first_name);
                    }
                })
                    ->sortable(['first_name'])
                    ->url(fn ($record) => HospitalResource::getUrl('view', ['record' => $record->id])),
                TextColumn::make('first_name')
                    ->label('')
                    ->description(function (User $record) {
                        return $record->email;
                    })
                    ->html()
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($state, $record) => '<a href="'.HospitalResource::getUrl('view', ['record' => $record->id]).'"class="hoverLink">'.$state.'</a>')
                    ->color('primary')
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('username')
                    ->label(__('messages.user.hospital_slug'))
                    ->searchable(['first_name', 'last_name', 'email'])
                    ->html()
                    ->color(fn ($record) => $record->status ? 'primary' : 'secondary')
                    ->formatStateUsing(fn ($state, $record) => $record->status ? '<a href="'.route('front', $state).'" class="hoverLink" target="_blank">'.$state.'</a>' : $state)
                    ->default(__('messages.common.n/a'))
                    ->sortable(),
                TextColumn::make('hospitalType.name')
                    ->label(__('messages.hospital_type'))
                    ->searchable(['first_name', 'last_name', 'email'])
                    ->default(__('messages.common.n/a'))
                    ->sortable(),
                TextColumn::make('city')
                    ->label(__('messages.user.city'))
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->view('tables.columns.created_at')
                    ->label(__('messages.common.created_on'))
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('status')
                    ->label(__('messages.user.status'))
                    ->updateStateUsing(function (User $record, bool $state) {
                        $user = User::find($record->id);
                        if ($state) {
                            $user->status = 1;
                            $user->save();
                        } else {
                            $user->status = 0;
                            $user->save();
                        }
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    }),
                Tables\Columns\ToggleColumn::make('email_verified_at')
                    ->label(__('messages.user.email_verified'))
                    ->disabled(fn ($record) => $record->email_verified_at)
                    ->updateStateUsing(function ($record, $state) {
                        $state ? $record->email_verified_at = now() : ' ';
                        Notification::make()
                            ->title(__('messages.flash.email_verified'))
                            ->success()
                            ->send($record);

                        return $record->save();
                    }),

            ])
            ->recordUrl(null)
            ->filters([
                SelectFilter::make('status')
                    ->label(__('messages.common.status').':')
                    ->options([
                        '' => __('messages.filter.all'),
                        '1' => __('messages.filter.active'),
                        '0' => __('messages.filter.deactive'),
                    ])->native(false),
            ])
            ->actions([
                Impersonate::make()
                    ->tooltip(__('messages.impersonate'))
                    ->redirectTo(route('filament.hospitalAdmin.pages.dashboard'))
                    ->color(function (User $record) {
                        if ($record->email_verified_at == null) {
                            return 'secondary';
                        }
                    })
                    ->disabled(function (User $record) {
                        if ($record->email_verified_at == null) {
                            return true;
                        }

                        return false;
                    })
                    ->label(__('messages.impersonate')),
                Tables\Actions\EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->tooltip(__('messages.common.delete'))
                    ->iconButton()
                    ->action(function (User $record) {
                        $user = User::findOrFail($record->id);
                        $tenant = MultiTenant::where('id', $user->tenant_id);
                        Doctor::whereNotNull('id')->where('tenant_id', $user->tenant_id)->delete();
                        $tenant->delete();
                        if ($tenant) {
                            $user->clearMediaCollection(User::COLLECTION_PROFILE_PICTURES);
                            $user->delete();
                            Notification::make()
                                ->title(__('messages.hospital').' '.__('messages.common.has_been_deleted'))
                                ->success()
                                ->send();
                        }
                    }),
            ])->actionsColumnLabel(__('messages.impersonate').'/'.__('messages.common.action'))
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
            'index' => Pages\ListHospitals::route('/'),
            'create' => Pages\CreateHospital::route('/create'),
            'view' => Pages\ViewHospital::route('/{record}'),
            'edit' => Pages\EditHospital::route('/{record}/edit'),
        ];
    }
}
