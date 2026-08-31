<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources;

use App\Filament\HospitalAdmin\Clusters\Doctors;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorHolidaysResource\Pages;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class DoctorHolidaysResource extends Resource
{
    protected static ?string $model = DoctorHoliday::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $cluster = Doctors::class;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('messages.holiday.doctor_holiday');
    }

    public static function getLabel(): string
    {
        return __('messages.holiday.doctor_holiday');
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
                            ->label(__('messages.doctor_opd_charge.doctor').':')
                            ->required()
                            ->options(fn () => Doctor::with('user')->get()->where('user.status', User::ACTIVE)->where('user.tenant_id', getLoggedInUser()->tenant_id)->pluck('user.full_name', 'id'))
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->placeholder(__('messages.schedule.select_doctor_name'))
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.doctor_opd_charge.doctor').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\DatePicker::make('date')
                            ->label(__('messages.case.date').':')
                            ->required()
                            ->validationAttribute(__('messages.case.date'))
                            ->native(false)
                            ->minDate(now()->format('Y-m-d'))
                            ->date('Y-m-d', 'd-m-Y'),
                        Forms\Components\TextInput::make('name')
                            ->label(__('messages.holiday.reason').':')
                            ->placeholder(__('messages.holiday.reason'))
                            ->maxLength(255),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table = $table->modifyQueryUsing(function ($query) {
            $query = $query->where('tenant_id', getLoggedInUser()->tenant_id);

            return $query;
            // if ($this->dateFilter != '' && $this->dateFilter != getWeekDate()) {
            //     $timeEntryDate = explode(' - ', $this->dateFilter);
            //     $startDate = Carbon::parse($timeEntryDate[0])->format('Y-m-d');
            //     $endDate = Carbon::parse($timeEntryDate[1])->format('Y-m-d');
            //     $query->whereBetween('date', [$startDate, $endDate]);
            // } else {
            //     $timeEntryDate = explode(' - ', getWeekDate());
            //     $startDate = Carbon::parse($timeEntryDate[0])->format('Y-m-d');
            //     $endDate = Carbon::parse($timeEntryDate[1])->format('Y-m-d');
            //     $query->whereBetween('date', [$startDate, $endDate]);
            // }
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
                TextColumn::make('name')
                    ->label(__('messages.holiday.reason'))
                    ->getStateUsing(fn ($record) => $record->name ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('messages.sms.date'))
                    ->getStateUsing(fn ($record) => $record->date ? \Carbon\Carbon::parse($record->date)->isoFormat('DD MMM YYYY') : __('messages.common.n/a'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                DateRangeFilter::make('created_at')->label(__('messages.case.date'))->showWeekNumbers(),
            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->actions([
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.doctor_delete')),
            ])
            ->recordUrl(false)
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
            'index' => Pages\ListDoctorHolidays::route('/'),
            'create' => Pages\CreateDoctorHolidays::route('/create'),
            'edit' => Pages\EditDoctorHolidays::route('/{record}/edit'),
        ];
    }
}
