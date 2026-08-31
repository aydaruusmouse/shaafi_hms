<?php

namespace App\Filament\Clusters\LandingCMS\Resources;

use App\Filament\Clusters\LandingCMS;
use App\Filament\Clusters\LandingCMS\Resources\AdminTestimonialResource\Pages;
use App\Models\AdminTestimonial;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;

class AdminTestimonialResource extends Resource
{
    protected static ?string $model = AdminTestimonial::class;

    protected static ?string $cluster = LandingCMS::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('messages.testimonials');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.testimonials');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('messages.testimonial.name').':')
                    ->placeholder(__('messages.testimonial.name'))
                    ->validationAttribute(__('messages.testimonial.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('position')
                    ->label(__('messages.testimonial.position').':')
                    ->placeholder(__('messages.testimonial.position'))
                    ->validationAttribute(__('messages.testimonial.position'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('messages.testimonial.description').':')
                    ->validationAttribute(__('messages.testimonial.description'))
                    ->placeholder(__('messages.testimonial.description'))
                    ->required()
                    ->rows(5),
                SpatieMediaLibraryFileUpload::make('testimonials')
                    ->collection(AdminTestimonial::PATH)
                    ->label(__('messages.common.profile').':')
                    ->validationAttribute(__('messages.common.profile'))
                    ->required()
                    ->disk(config('app.media_disk'))
                    ->avatar()
                    ->imageCropAspectRatio(null),
            ])->columns(1);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('name')
                    ->label(__('messages.testimonial.name').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('position')
                    ->label(__('messages.testimonial.position').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('description')
                    ->label(__('messages.account.description').':')
                    ->default(__('messages.common.n/a')),
                SpatieMediaLibraryImageEntry::make('profile')
                    ->collection(AdminTestimonial::PATH)
                    ->label(__('messages.common.profile').':'),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                SpatieMediaLibraryImageColumn::make('testimonials')
                    ->collection(AdminTestimonial::PATH)->rounded()
                    ->label(__('messages.common.profile')),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.testimonial.name'))
                    ->sortable()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('position')
                    ->label(__('messages.testimonial.position'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('messages.account.description'))
                    ->sortable()
                    ->wrap()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip(__('messages.common.view'))
                    ->modalHeading(__('messages.testimonial.show_testimonial'))
                    ->color('info')
                    ->iconButton()
                    ->modalWidth('md'),
                Tables\Actions\EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading(__('messages.testimonial.edit_testimonial'))
                    ->successNotificationTitle(__('messages.flash.testimonial_update')),
                Tables\Actions\DeleteAction::make()
                    ->tooltip(__('messages.common.delete'))
                    ->iconButton()
                    ->modalHeading(__('messages.common.delete').' '.__('messages.delete.testimonial'))
                    ->successNotificationTitle(__('messages.delete.testimonial').' '.__('messages.common.deleted_successfully')),
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
            'index' => Pages\ManageAdminTestimonials::route('/'),
        ];
    }
}
