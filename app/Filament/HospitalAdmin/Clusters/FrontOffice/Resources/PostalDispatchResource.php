<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources;

use App\Filament\HospitalAdmin\Clusters\FrontOffice;
use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\PostalDispatchResource\Pages;
use App\Models\Postal;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PostalDispatchResource extends Resource
{
    protected static ?string $model = Postal::class;

    protected static ?string $cluster = FrontOffice::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Postal Dispatch')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Postal Dispatch')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.postal_dispatch');
    }

    public static function getLabel(): string
    {
        return __('messages.postal_dispatch');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Postal Dispatch')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Postal Dispatch')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Postal Dispatch')) {
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
                Forms\Components\TextInput::make('to_title')
                    ->label(__('messages.postal.to_title').':')
                    ->placeholder(__('messages.postal.to_title'))
                    ->validationAttribute(__('messages.postal.to_title'))
                    ->required(),
                Forms\Components\TextInput::make('reference_no')
                    ->label(__('messages.postal.reference_no').':')
                    ->placeholder(__('messages.postal.reference_no')),
                DatePicker::make('date')
                    ->native(false)
                    ->label(__('messages.postal.date').':'),
                Forms\Components\TextInput::make('from_title')
                    ->label(__('messages.postal.from_title').':')
                    ->placeholder(__('messages.postal.from_title')),
                SpatieMediaLibraryFileUpload::make('attachment')
                    ->label(__('messages.document.attachment').':')
                    ->disk(config('app.media_disk'))
                    ->collection('document_url'),
                Textarea::make('address')
                    ->label(__('messages.postal.address').':')
                    ->placeholder(__('messages.postal.address'))
                    ->rows(4)
                    ->columnSpan('full'),
                Forms\Components\Hidden::make('type')
                    ->label(__('messages.account.type'))
                    ->default(2)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Postal Dispatch')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('type', 2)->whereTenantId(getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                BadgeColumn::make('reference_no')
                    ->label(__('messages.postal.reference_no'))
                    ->getStateUsing(fn ($record) => $record->reference_no ?? __('messages.common.n/a'))
                    ->color(function ($state) {
                        return $state == __('messages.common.n/a') ? 'black' : 'info';
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('from_title')
                    ->label(__('messages.postal.from_title'))
                    ->getStateUsing(fn ($record) => $record->from_title ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('to_title')
                    ->label(__('messages.postal.to_title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('messages.call_log.received_on'))
                    ->sortable()
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->date ? Carbon::parse($record->date)->translatedFormat('jS M, Y') : __('messages.common.n/a')),
                Tables\Columns\TextColumn::make('attachment')
                    ->label(__('messages.document.attachment'))
                    ->getStateUsing(function ($record) {
                        if ($record->document_url) {
                            return '<a href="'.$record->document_url.'" style="margin-left: -17px; color: #4F46E5;" download>Download</a>';
                        }

                        return __('messages.common.n/a');
                    })
                    ->html(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('3xl')->successNotificationTitle(__('messages.flash.postal_dispatch_update'))->modalHeading(__('messages.postal.edit_dispatch')),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.postal_dispatch_deleted')),
            ])
            ->recordAction(null)
            ->actionsColumnLabel(__('messages.common.action'))
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
            'index' => Pages\ListPostalDispatches::route('/'),
            // 'create' => Pages\CreatePostalDispatch::route('/create'),
            // 'edit' => Pages\EditPostalDispatch::route('/{record}/edit'),
        ];
    }
}
