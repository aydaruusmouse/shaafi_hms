<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources;

use App\Filament\HospitalAdmin\Clusters\FrontOffice;
use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\CallLogResource\Pages;
use App\Models\CallLog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CallLogResource extends Resource
{
    protected static ?string $model = CallLog::class;

    protected static ?string $cluster = FrontOffice::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 0;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Call Logs')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Call Logs')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.call_logs');
    }

    public static function getLabel(): string
    {
        return __('messages.call_logs');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Call Logs')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Call Logs')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Call Logs')) {
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
                        TextInput::make('name')
                            ->label(__('messages.user.name').':')
                            ->placeholder(__('messages.user.name'))
                            ->validationAttribute(__('messages.user.name'))
                            ->required(),
                        PhoneInput::make('phone')
                            ->label(__('messages.user.phone').':')
                            ->defaultCountry('IN')
                            ->rules(function (Get $get) {
                                return [
                                    'phone:AUTO,'.strtoupper($get('prefix_code')),
                                ];
                            })
                            ->validationMessages([
                                'phone' => __('messages.common.invalid_number'),
                            ])
                            ->defaultCountry('IN')
                            ->showSelectedDialCode(true),
                        DatePicker::make('date')
                            ->native(false)
                            ->label(__('messages.call_log.received_on').':'),
                        DatePicker::make('follow_up_date')
                            ->native(false)
                            ->label(__('messages.call_log.follow_up_date').':'),
                        Textarea::make('note')
                            ->label(__('messages.call_log.note').':')
                            ->placeholder(__('messages.call_log.note'))
                            ->rows(5),
                        Group::make()->schema([
                            Radio::make('call_type')
                                ->label(__('messages.call_log.call_type').':')
                                ->required()
                                ->validationAttribute(__('messages.call_log.call_type'))
                                ->options([
                                    '1' => __('messages.call_log.incoming'),
                                    '2' => __('messages.call_log.outgoing'),
                                ])
                                ->default('0')
                                ->columns(2),
                        ])->columns(2),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Call Logs')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            $query->whereTenantId(getLoggedInUser()->tenant_id);

            return $query;
        });

        return $table
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.user.name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->getStateUsing(fn ($record) => $record->phone ?? __('messages.common.n/a'))
                    ->label(__('messages.user.phone'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('date')
                    ->label(__('messages.call_log.received_on'))
                    ->getStateUsing(fn ($record) => $record->date ? Carbon::parse($record->date)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                    ->badge(),
                TextColumn::make('follow_up_date')
                    ->label(__('messages.call_log.follow_up_date'))
                    ->getStateUsing(fn ($record) => $record->follow_up_date ? Carbon::parse($record->follow_up_date)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                    ->badge(),
                BadgeColumn::make('call_type')
                    ->formatStateUsing(function ($state) {
                        return $state == 1 ? __('messages.call_log.incoming') : __('messages.call_log.outgoing');
                    })
                    ->color(function ($state) {
                        return $state == 1 ? 'success' : 'primary'; // Customize colors here
                    }),
            ])
            ->filters([
                SelectFilter::make('call_type')
                    ->options([
                        '' => __('messages.filter.all'),
                        '1' => __('messages.call_log.incoming'),
                        '2' => __('messages.call_log.outgoing'),
                    ])
                    ->label(__('messages.common.status').':')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.call_log_deleted')),
            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([

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
            'index' => Pages\ListCallLogs::route('/'),
            'create' => Pages\CreateCallLog::route('/create'),
            'edit' => Pages\EditCallLog::route('/{record}/edit'),
        ];
    }
}
