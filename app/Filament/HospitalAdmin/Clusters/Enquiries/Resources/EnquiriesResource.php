<?php

namespace App\Filament\HospitalAdmin\Clusters\Enquiries\Resources;

use App\Filament\HospitalAdmin\Clusters\Enquiries;
use App\Filament\HospitalAdmin\Clusters\Enquiries\Resources\EnquiriesResource\Pages;
use App\Models\Enquiry;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EnquiriesResource extends Resource
{
    protected static ?string $model = Enquiry::class;

    protected static ?string $cluster = Enquiries::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Enquires')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.enquiries');
    }

    public static function getLabel(): ?string
    {
        return __('messages.enquiries');
    }

    // public static function canCreate(): bool
    // {
    //     if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
    //         return true;
    //     }
    //     return false;
    // }
    // public static function canEdit(Model $record): bool
    // {
    //     if (auth()->user()->hasRole(['Admin'])) {
    //         return false;
    //     }
    // }

    // public static function canDelete(Model $record): bool
    // {
    //     if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
    //         return true;
    //     }
    //     return false;
    // }

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
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Enquires')) {
            abort(404);
        }

        return $table = $table->modifyQueryUsing(function (Builder $query) {
            $query->whereTenantId(auth()->user()->tenant_id);

            return $query;
        })
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('messages.profile.full_name'))
                    ->sortable()
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn ($record) => $record->email ?? __('messages.common.n/a'))
                    ->searchable(['email']),
                TextColumn::make('type')
                    ->label(__('messages.enquiry.type'))
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if ($record->type == 1) {
                            return __('messages.enquiry.general_enquiry');
                        } elseif ($record->type == 2) {
                            return __('messages.enquiry.feedback/suggestions');
                        }

                        return __('messages.enquiry.residential_care');
                    })
                    ->color(function ($record) {
                        if ($record->type == 1) {
                            return 'warning';
                        } elseif ($record->type == 2) {
                            return 'primary';
                        }

                        return 'success';
                    })
                    ->badge(),
                TextColumn::make('created_at')
                    ->sortable()
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->created_at->translatedFormat('jS M, Y'))
                    ->label(__('messages.common.created_at')),

                TextColumn::make('viewed_by')
                    ->sortable()
                    //  $record->user->full_name ?? __('messages.common.n/a')
                    ->getStateUsing(function ($record) {
                        if ($record->status != 1) {
                            return __('messages.enquiry.not_viewed');
                        }

                        return $record->user->full_name ?? __('messages.common.n/a');
                    })
                    ->label(__('messages.enquiry.viewed_by')),
                ToggleColumn::make('status')
                    ->label(__('messages.user.status'))
                    ->updateStateUsing(function ($record, $state) {
                        $record->status = $state ? 1 : 0;
                        $record->save();
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('messages.common.status').':')
                    ->options([
                        '' => __('messages.filter.all'),
                        1 => __('messages.enquiry.read'),
                        0 => __('messages.enquiry.unread'),
                    ])->native(false),
            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->iconButton(),
            ])
            ->bulkActions([])
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
            'index' => Pages\ListEnquiries::route('/'),
            // 'create' => Pages\CreateEnquiries::route('/create'),
            // 'edit' => Pages\EditEnquiries::route('/{record}/edit'),
            'view' => Pages\ViewEnquiries::route('/{record}'),
        ];
    }
}
