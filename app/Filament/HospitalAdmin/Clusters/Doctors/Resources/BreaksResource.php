<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources;

use App\Filament\HospitalAdmin\Clusters\Doctors;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\BreaksResource\Pages;
use App\Models\Doctor;
use App\Models\LunchBreak;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Google\Service\AdExchangeBuyerII\Date;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BreaksResource extends Resource
{
    protected static ?string $model = LunchBreak::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = Doctors::class;

    public static function getNavigationLabel(): string
    {
        return __('messages.lunch_break.lunch_breaks');
    }

    public static function getLabel(): string
    {
        return __('messages.lunch_break.lunch_breaks');
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
                Section::make()
                    ->schema([
                        Forms\Components\Select::make('doctor_id')
                            ->required()
                            ->options(fn () => Doctor::with('user')->get()->where('user.tenant_id', getLoggedInUser()->tenant_id)->where('user.status', User::ACTIVE)->pluck('user.full_name', 'id'))
                            ->label(__('messages.doctor_opd_charge.doctor').':')
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->placeholder(__('messages.doctor_opd_charge.doctor'))
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.doctor_opd_charge.doctor').' '.__('messages.fields.required'),
                            ]),
                        Radio::make('every_day')
                            ->live()
                            ->label('')
                            ->options([
                                1 => __('messages.lunch_break.every_day'),
                                '' => __('messages.lunch_break.single_day'),
                            ])
                            ->inline()
                            ->default(true),
                        Forms\Components\TimePicker::make('break_from')
                            ->required()
                            ->validationAttribute(__('messages.lunch_break.lunch_break'))
                            ->time()
                            ->default('00:00:00')
                            ->label(__('messages.lunch_break.lunch_break').' '.__('messages.common.from').':')
                            ->placeholder(__('messages.lunch_break.lunch_break').' '.__('messages.common.from')),
                        Forms\Components\TimePicker::make('break_to')
                            ->required()
                            ->validationAttribute(__('messages.lunch_break.lunch_break'))
                            ->time()
                            ->default('00:00')
                            ->label(__('messages.lunch_break.lunch_break').' '.__('messages.common.to').':')
                            ->placeholder(__('messages.lunch_break.lunch_break').' '.__('messages.common.to')),
                        DatePicker::make('date')
                            ->required(function ($get) {
                                if ($get('every_day')) {
                                    return false;
                                }

                                return true;
                            })
                            ->validationAttribute(__('messages.sms.date'))
                            ->native(false)
                            ->default(Carbon::now()->format('Y-m-d'))
                            ->label(__('messages.sms.date'))
                            ->visible(function ($get) {
                                if ($get('every_day')) {
                                    return false;
                                }

                                return true;
                            })
                            ->placeholder(__('messages.lunch_break.lunch_break').' '.__('messages.common.date')),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(auth()->user()->tenant_id);

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    SpatieMediaLibraryImageColumn::make('doctor.user.profile')->collection(User::COLLECTION_PROFILE_PICTURES)->rounded()->label(__('messages.case.doctor'))->width(50)->height(50)
                        ->url(fn ($record) => DoctorResource::getUrl('view', ['record' => $record->doctor->id]))
                        ->sortable(['first_name'])
                        ->defaultImageUrl(function ($record) {
                            if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                                return getUserImageInitial($record->id, $record->doctor->user->first_name);
                            }
                        }),
                    TextColumn::make('doctor.user.full_name')
                        ->label('')
                        ->description(function ($record) {
                            return $record->doctor->user->email;
                        })
                        ->formatStateUsing(fn ($record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'" class="hoverLink">'.$record->doctor->user->full_name.'</a>')
                        ->html()
                        ->color('primary')
                        ->weight(FontWeight::SemiBold)
                        ->searchable(['first_name', 'last_name', 'email']),
                    TextColumn::make('break_from')
                        ->label(__('messages.lunch_break.lunch_break').' '.__('messages.common.from'))
                        ->searchable()
                        ->badge()
                        ->color('primary')
                        ->sortable(),
                    TextColumn::make('break_to')
                        ->label(__('messages.lunch_break.lunch_break').' '.__('messages.common.to'))
                        ->searchable()
                        ->badge()
                        ->color('primary')
                        ->sortable(),
                    TextColumn::make('date')
                        ->label(__('messages.sms.date'))
                        // \Carbon\Carbon::parse($row->date)->isoFormat('DD MMM YYYY')
                        ->getStateUsing(fn ($record) => $record->date ? Carbon::parse($record->date)->translatedFormat('jS M, Y') : __('messages.lunch_break.every_day'))
                        ->badge()
                        ->color('info')
                        ->sortable(),
                ])
                ->filters([
                    //
                ])
                ->recordUrl(false)
                ->actionsColumnLabel(__('messages.common.action'))
                ->actions([
                    Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('js.lunch_break').' '.(__('messages.common.has_been_deleted'))),
                ])
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
            'index' => Pages\ListBreaks::route('/'),
            'create' => Pages\CreateBreaks::route('/create'),
            'edit' => Pages\EditBreaks::route('/{record}/edit'),
        ];
    }
}
