<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontCms\Resources;

use App\Filament\HospitalAdmin\Clusters\FrontCms;
use App\Filament\HospitalAdmin\Clusters\FrontCms\Resources\FrontCmsServicesResource\Pages;
use App\Models\FrontService;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FrontCmsServicesResource extends Resource
{
    protected static ?string $model = FrontService::class;

    protected static ?string $cluster = FrontCms::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('messages.front_cms_services');
    }

    public static function getLabel(): string
    {
        return __('messages.front_cms_services');
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
                TextInput::make('name')
                    ->label(__('messages.common.name').':')
                    ->placeholder(__('messages.common.name'))
                    ->required()
                    ->validationAttribute(__('messages.common.name'))
                    ->columnSpanFull(),

                Textarea::make('short_description')
                    ->label(__('messages.short_description'))
                    ->placeholder(__('messages.common.name'))
                    ->validationAttribute(__('messages.short_description'))
                    ->required()
                    ->columnSpanFull(),

                SpatieMediaLibraryFileUpload::make('icon')
                    ->label(__('messages.icon').':')
                    ->collection(FrontService::PATH)
                    ->required()
                    ->disk(config('app.media_disk'))
                    ->validationAttribute(__('messages.icon'))
                    ->avatar()
                    ->imageCropAspectRatio(null)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(auth()->user()->tenant_id);

                return $query;
            })
                ->paginated([10, 25, 50])
                ->columns([
                    SpatieMediaLibraryImageColumn::make('icon')
                        ->collection(FrontService::PATH)
                        ->defaultImageUrl(asset('web_front/images/services/medicine.png'))
                        ->disk(config('app.media_disc'))
                        ->circular(),

                    Tables\Columns\TextColumn::make('name')
                        ->label(__('messages.common.name'))
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('short_description')
                        ->label(__('messages.post.description'))
                        ->searchable()
                        ->sortable(),
                ])
                ->filters([
                    //
                ])
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->modalHeading(__('messages.front_services.edit_service'))->successNotificationTitle(__('messages.flash.frontService_updated')),
                    Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.frontService_deleted')),
                ])
                ->defaultSort('id', 'desc')
                ->actionsColumnLabel(__('messages.common.action'))
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
            'index' => Pages\ManageFrontCmsServices::route('/'),
        ];
    }
}
