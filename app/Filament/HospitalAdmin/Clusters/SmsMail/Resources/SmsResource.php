<?php

namespace App\Filament\HospitalAdmin\Clusters\SmsMail\Resources;

use App\Filament\HospitalAdmin\Clusters\SmsMail;
use App\Filament\HospitalAdmin\Clusters\SmsMail\Resources\SmsResource\Pages;
use App\Models\Sms;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;

class SmsResource extends Resource
{
    protected static ?string $model = Sms::class;

    protected static ?string $cluster = SmsMail::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('SMS')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('SMS')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.sms.sms');
    }

    public static function getLabel(): string
    {
        return __('messages.sms.sms');
    }

    public static function canAccess(): bool
    {
        return ! auth()->user()->hasRole(['Patient']);
    }

    public static function canCreate(): bool
    {
        if (! auth()->user()->hasRole(['Lab Technician|Nurse', 'Patient']) && getModuleAccess('SMS')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (! auth()->user()->hasRole(['Lab Technician|Nurse', 'Patient']) && getModuleAccess('SMS')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (! auth()->user()->hasRole(['Lab Technician|Nurse', 'Patient']) && getModuleAccess('SMS')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (! auth()->user()->hasRole(['Lab Technician|Nurse', 'Patient'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                PhoneInput::make('phone')
                    ->countryStatePath('prefix_code')
                    ->defaultCountry('IN')
                    ->rules(function ($get) {
                        return [
                            'phone:AUTO,'.strtoupper($get('prefix_code')),
                        ];
                    })
                    ->validationMessages([
                        'phone' => __('messages.common.invalid_number'),
                    ])
                    ->visible(fn ($get) => $get('number_directly') == true)
                    ->required(function ($get) {
                        return $get('number_directly') == true ? true : false;
                    })
                    ->validationAttribute(__('messages.user.phone'))
                    ->label(__('messages.user.phone').':'),
                Select::make('role')
                    ->label(__('messages.sms.role').':')
                    ->required(fn ($get) => $get('number_directly') == false ?? true)
                    ->native(false)
                    ->live()
                    ->preload()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, $get) {
                        $set('send_to', null);

                        if (! empty($get('role'))) {
                            $usersData = Sms::CLASS_TYPES[$get('role')]::with('user')
                                ->whereHas('user', function (Builder $query) {
                                    $query->whereNotNull('phone');
                                })
                                ->where('tenant_id', getLoggedInUser()->tenant_id)
                                ->get()->where('user.status', '=', 1)
                                ->pluck('user.full_name', 'user.id');

                            return $usersData;
                        } else {
                            Notification::make()->title(__('messages.flash.user_list_not'))->danger()->send();

                            return [];
                        }
                    })
                    ->visible(fn ($get) => $get('number_directly') == false)
                    ->options(Sms::ROLE_TYPES)
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.sms.role').' '.__('messages.fields.required'),
                    ]),
                Toggle::make('number_directly')
                    ->label(__('messages.sms.send_sms_by_number_directly').':')
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set) {
                        $set('send_to', null);
                        $set('role', null);
                        $set('message', null);
                        $set('phone', null);
                    })
                    ->default(false),
                Select::make('send_to')
                    ->label(__('messages.sms.send_to').':'.__('messages.sms.only_user_with_registered_phone_will_display'))
                    ->multiple()
                    ->visible(fn ($get) => $get('number_directly') == false)
                    ->required(fn ($get) => $get('number_directly') == false ?? true)
                    ->options(function ($get) {
                        if (! empty($get('role'))) {

                            $usersData = Sms::CLASS_TYPES[$get('role')]::with('user')
                                ->whereHas('user', function (Builder $query) {
                                    $query->whereNotNull('phone');
                                })
                                ->where('tenant_id', getLoggedInUser()->tenant_id)
                                ->get()->where('user.status', '=', 1)
                                ->pluck('user.full_name', 'user.id');

                            return $usersData;
                        } else {
                            return [];
                        }
                    })
                    ->live()
                    ->disabled(function ($get) {
                        if ($get('role') == null) {
                            return true;
                        }

                        return false;
                    })
                    ->columnSpanFull()
                    ->placeholder(__('messages.sms.send_to'))
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.sms.send_to').' '.__('messages.fields.required'),
                    ]),

                Textarea::make('message')
                    ->label(__('messages.sms.message').':')
                    ->columnSpanFull()
                    ->required()
                    ->validationAttribute(__('messages.sms.message'))
                    ->rows(4)
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Accountant', 'Case Manager', 'Receptionist', 'Pharmacist']) && ! getModuleAccess('SMS')) {
            abort(404);
        }

        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(getLoggedInUser()->tenant_id);
                $user = Auth::user();
                if (! $user->hasRole('Admin')) {
                    $query->where('send_to', $user->id)->orwhere('send_by', $user->id);
                }

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    TextColumn::make('send_to')
                        ->default(__('messages.common.n/a'))
                        ->label(__('messages.sms.send_to'))
                        ->searchable()
                        ->color(fn ($record) => ! empty($record->user->full_name) ? 'primary' : '')
                        ->formatStateUsing(function ($state) {
                            $user = User::whereId($state)->whereTenantId(getLoggedInUser()->tenant_id)->first();

                            return $user->full_name ?? __('messages.common.n/a');
                        })
                        ->sortable(),
                    TextColumn::make('phone_number')
                        ->label(__('messages.user.phone'))
                        ->formatStateUsing(function ($state, $record) {
                            if (str_starts_with($state, '+')) {
                                return $state;
                            } elseif (! empty($record->region_code)) {
                                return $record->region_code.$state;
                            } else {
                                return __('messages.common.n/a');
                            }
                        })
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('sendBy.first_name')
                        ->searchable()
                        ->sortable()
                        ->label(__('messages.sms.send_by')),
                ])
                ->filters([
                    //
                ])
                ->actions([
                    Tables\Actions\ViewAction::make()->color('info')->iconButton()->extraAttributes(['class' => 'hidden'])->modalHeading(__('messages.sms.sms_details'))->visible(fn ($record) => ! empty($record->user->full_name)),
                    Tables\Actions\DeleteAction::make()
                        ->iconButton()
                        ->action(function (Sms $record) {
                            if (! canAccessRecord(Sms::class, $record->id)) {
                                return Notification::make()
                                    ->danger()
                                    ->title(__('messages.flash.sms_not_found'))
                                    ->send();
                            }

                            if (! getLoggedInUser()->hasRole('Admin')) {
                                if (getLoggedInUser()->id != $record->send_by) {
                                    return Notification::make()
                                        ->danger()
                                        ->title(__('messages.flash.sms_not_found'))
                                        ->send();
                                }
                            }

                            $record->delete();

                            return Notification::make()
                                ->success()
                                ->title(__('messages.flash.sms_delete'))
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('user.full_name')
                    ->default(__('messages.common.n/a'))
                    ->label(__('messages.sms.send_to').':'),
                TextEntry::make('user.roles')
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(fn ($record) => $record->user->roles->pluck('name')->first())
                    ->label(__('messages.sms.role').':'),
                PhoneEntry::make('phone_number')
                    ->default(__('messages.common.n/a'))
                    ->label(__('messages.user.phone').':'),
                TextEntry::make('created_at')
                    ->label(__('messages.sms.date').':')
                    ->since(),
                TextEntry::make('sendBy.full_name')
                    ->default(__('messages.common.n/a'))
                    ->label(__('messages.sms.send_by').':'),
                TextEntry::make('updated_at')
                    ->label(__('messages.common.updated_at').':')
                    ->since(),
                TextEntry::make('message')
                    ->default(__('messages.common.n/a'))
                    ->label(__('messages.sms.message').':'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSms::route('/'),
        ];
    }
}
