<?php

namespace App\Filament\Clusters\LandingCMS\Resources;

use App\Filament\Clusters\LandingCMS;
use App\Filament\Clusters\LandingCMS\Resources\ServiceSliderResource\Pages;
use App\Models\ServiceSlider;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;

class ServiceSliderResource extends Resource
{
    protected static ?string $model = ServiceSlider::class;

    protected static ?string $cluster = LandingCMS::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('messages.services');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.services');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                SpatieMediaLibraryFileUpload::make('image')
                    ->collection(ServiceSlider::SERVICE_SLIDER)
                    ->label(__('messages.service_slider.service_slider_image').':')
                    ->validationAttribute(__('messages.service_slider.service_slider_image'))
                    ->columnSpanFull()
                    ->required()
                    ->disk(config('app.media_disk'))
                    ->avatar()
                    ->imageCropAspectRatio(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection(ServiceSlider::SERVICE_SLIDER)
                    ->rounded()
                    ->label(__('messages.landing_cms.image')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->modalWidth('md')
                    ->modalHeading(__('messages.service_slider.edit_service_slider'))
                    ->successNotificationTitle(__('messages.new_change.service_slider_update'))
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.common.delete').' '.__('messages.delete.service'))
                    ->successNotificationTitle(__('messages.service_slider.service_slider').' '.__('messages.common.deleted_successfully')),
            ])->actionsColumnLabel(__('messages.common.action'))
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
            'index' => Pages\ManageServiceSliders::route('/'),
        ];
    }
}
