<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages\CreateAdmin;
use App\Filament\Resources\AdminResource\Pages\EditAdmin;
use App\Filament\Resources\AdminResource\Pages\ListAdmins;
use App\Filament\Resources\AdminResource\Pages\ViewAdmin;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class AdminResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'fas-user';

    protected static ?string $modelLabel = 'Admins';

    protected static ?string $navigationLabel = 'Admins';

    protected static ?string $slug = 'admins';

    public static function getNavigationLabel(): string
    {
        return __('messages.admin');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->validationAttribute(__('messages.user.first_name'))
                            ->label(__('messages.user.first_name').':')
                            ->placeholder(__('messages.user.first_name'))
                            ->maxLength(160),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->validationAttribute(__('messages.user.last_name'))
                            ->label(__('messages.user.last_name').':')
                            ->placeholder(__('messages.user.last_name'))
                            ->maxLength(160),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->validationAttribute(__('messages.user.email'))
                            ->unique('users', 'email', ignoreRecord: true)
                            ->validationMessages([
                                'unique' => __('messages.fields.unique'),
                            ])
                            ->validationMessages([
                                'unique' => __('messages.user.email').' '.__('messages.common.is_already_exists'),
                            ])
                            ->label(__('messages.user.email').':')
                            ->placeholder(__('messages.user.email'))
                            ->maxLength(255),
                        PhoneInput::make('phone')
                            ->defaultCountry('IN')
                            ->required()
                            ->validationAttribute(__('messages.user.phone'))
                            ->rules(function (Get $get) {
                                return [
                                    'required',
                                    'phone:AUTO,'.strtoupper($get('prefix_code')),
                                ];
                            })
                            ->countryStatePath('region_code')
                            ->afterStateHydrated(function ($component, $record, $operation) {
                                if ($operation == 'edit') {
                                    $phoneNumber = (empty($record->region_code) ? '+' : $record->region_code).getPhoneNumber($record->phone);
                                    $component->state($phoneNumber);
                                }
                            })
                            ->validationMessages([
                                'phone' => __('messages.common.invalid_number'),
                            ])
                            ->label(__('messages.user.phone').':'),
                        Hidden::make('region_code'),
                        Forms\Components\TextInput::make('password')
                            ->revealable()
                            ->visible(function (?string $operation) {
                                return $operation == 'create';
                            })
                            ->rules(['min:8', 'max:20'])
                            ->label(__('messages.user.password').':')
                            ->placeholder(__('messages.user.password'))
                            ->confirmed()
                            ->required()
                            ->validationAttribute(__('messages.user.password'))
                            ->password()
                            ->maxLength(20)
                            ->validationMessages([
                                'confirmed' => __('messages.fields.confirm'),
                            ])
                            ->validationMessages([
                                'min' => __('messages.fields.min_char'),
                            ]),
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
                        SpatieMediaLibraryFileUpload::make('profile')
                            ->avatar()
                            ->disk(config('app.media_disk'))
                            ->label(__('messages.common.profile').':')
                            ->avatar()
                            ->collection(User::COLLECTION_PROFILE_PICTURES),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table->modifyQueryUsing(function ($query) {
            $query = User::role('Super Admin')->where('id', '!=', auth()->user()->id);

            return $query;
        });

        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')->collection(User::COLLECTION_PROFILE_PICTURES)->rounded()->label(__('messages.common.name'))->width(50)->height(50)->defaultImageUrl(function ($record) {
                    if (! $record->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                        return getUserImageInitial($record->id, $record->first_name);
                    }
                })
                    ->sortable(['first_name'])
                    ->url(fn ($record) => AdminResource::getUrl('view', ['record' => $record->id])),
                TextColumn::make('full_name')
                    ->label('')
                    ->description(function (User $record) {
                        return $record->email;
                    })
                    ->html()
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($state, $record) => '<a href="'.AdminResource::getUrl('view', ['record' => $record->id]).'"class="hoverLink">'.$state.'</a>')
                    ->color('primary')
                    ->searchable(['first_name', 'last_name', 'email']),
                PhoneColumn::make('phone')
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(function ($state, $record) {
                        if (str_starts_with($state, '+') && strlen($state) > 4) {
                            return $state;
                        }
                        if (empty($record->phone)) {
                            return __('messages.common.n/a');
                        }

                        return $record?->region_code.$record?->phone;
                    })
                    ->label(__('messages.user.phone').':'),
                TextColumn::make('email')
                    ->label(__('messages.user.email'))
                    ->searchable()
                    ->default(__('messages.common.n/a'))
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->recordUrl(false)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconBUtton()
                    ->tooltip(__('messages.common.edit')),
                Tables\Actions\DeleteAction::make()->iconBUtton()
                    ->tooltip(__('messages.common.delete'))
                    ->action(function (User $record) {
                        $checkSuperAdmin = User::whereId($record['id'])->where('is_super_admin_default', 1)->exists();
                        if ($checkSuperAdmin) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.new_change.default_admin_not_delete'))
                                ->send();
                        }

                        $user = User::find($record['id']);

                        if (empty($user) || ! $user->hasRole('Super Admin')) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.admin_not_found'))
                                ->send();
                        }

                        $user->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.admin_user.admin_deleted_successfully'))
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'view' => ViewAdmin::route('/{record}'),
            'edit' => EditAdmin::route('/{record}/edit'),
        ];
    }
}
