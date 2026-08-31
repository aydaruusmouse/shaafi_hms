<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Resources;

use App\Filament\HospitalAdmin\Clusters\BedManagement;
use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedResource\Pages;
use App\Models\Bed;
use App\Models\BedAssign;
use App\Models\BedType;
use App\Models\IpdPatientDepartment;
use App\Repositories\BedRepository;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BedResource extends Resource
{
    protected static ?string $model = Bed::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        // return auth()->user()->hasRole(['Admin', 'Nurse']);
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Beds')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Beds')) {
            return false;
        }

        return true;
    }

    protected static ?string $cluster = BedManagement::class;

    public static function getNavigationLabel(): string
    {
        return __('messages.beds');
    }

    public static function getLabel(): string
    {
        return __('messages.beds');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Nurse']) && getModuleAccess('Beds')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Nurse']) && getModuleAccess('Beds')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Nurse']) && getModuleAccess('Beds')) {
            return true;
        }

        return false;
    }

    public static function canView(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse'])) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Nurse'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->validationAttribute(__('messages.bed_assign.bed'))
                    ->placeholder(__('messages.bed_assign.bed'))
                    ->label(__('messages.bed_assign.bed').':'),
                Hidden::make('bed_id')
                    ->default(strtoupper(Str::random(8))),
                Forms\Components\Select::make('bed_type')
                    ->native(false)
                    ->label(__('messages.bed_types').':')
                    ->searchable()
                    ->options(BedType::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('title', 'id'))
                    ->placeholder(__('messages.bed.select_bed_type')),
                Forms\Components\TextInput::make('charge')
                    ->required()
                    ->validationAttribute(__('messages.bed.charge'))
                    ->label(__('messages.bed.charge').':')
                    ->placeholder(__('messages.bed.charge'))
                    ->numeric()
                    ->minValue(1),
                Forms\Components\Textarea::make('description')
                    ->label(__('messages.common.description').':')
                    ->placeholder(__('messages.common.description'))
                    ->columnSpanFull(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Nurse']) && ! getModuleAccess('Beds')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('bed_id')
                    ->label(__('messages.bed.bed_id'))
                    ->badge()
                    ->sortable()
                    ->url(fn ($record): string => BedResource::getUrl('view', ['record' => $record->id]))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.bed_assign.bed'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('bedType.title')
                    ->numeric()
                    ->html()
                    ->formatStateUsing(fn ($record) => '<a href="'.BedTypeResource::getUrl('view', ['record' => $record->bedType->id]).'"class="hoverLink">'.$record->bedType->title.'</a>')
                    ->label(__('messages.bed_types'))
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('charge')
                    ->label(__('messages.bed.charge'))
                    ->formatStateUsing(function ($record) {
                        return getCurrencyFormat($record->charge);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_available')
                    ->label(__('messages.bed.available'))
                    ->formatStateUsing(function ($record) {
                        return $record->is_available == 1 ? __('messages.common.yes') : __('messages.common.no');
                    })
                    ->badge()
                    ->color(function ($record) {
                        return $record->is_available == 1 ? 'success' : 'danger';
                    }),
            ])
            ->filters([
                SelectFilter::make('is_available')
                    ->label(__('messages.common.status').':')
                    ->native(false)
                    ->options([
                        '' => __('messages.filter.all'),
                        '1' => __('messages.bed.available'),
                        '0' => __('messages.bed.not_available'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.bed_updated')),
                Tables\Actions\DeleteAction::make()->iconButton()->action(function ($record) {
                    if (! canAccessRecord(Bed::class, $record->id)) {
                        return Notification::make()
                            ->danger()
                            ->title(__('messages.flash.bed_not_found'))
                            ->send();
                    }

                    $bedModel = [
                        BedAssign::class,
                        IpdPatientDepartment::class,
                    ];
                    $result = canDelete($bedModel, 'bed_id', $record->id);
                    if ($result) {
                        return Notification::make()
                            ->danger()
                            ->title(__('messages.flash.bed_cant_deleted'))
                            ->send();
                    }

                    app(BedRepository::class)->delete($record->id);

                    return Notification::make()
                        ->success()
                        ->title(__('messages.flash.bed_deleted'))
                        ->send();
                }),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->recordAction(null)
            ->recordUrl(null)
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
            'index' => Pages\ManageBeds::route('/'),
            'view' => Pages\ViewBed::route('/{record}'),
        ];
    }
}
