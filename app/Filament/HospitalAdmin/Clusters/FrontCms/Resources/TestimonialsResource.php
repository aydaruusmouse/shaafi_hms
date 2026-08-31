<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontCms\Resources;

use App\Filament\HospitalAdmin\Clusters\FrontCms;
use App\Filament\HospitalAdmin\Clusters\FrontCms\Resources\TestimonialsResource\Pages;
use App\Models\Testimonial;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TestimonialsResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static ?string $cluster = FrontCms::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Testimonial')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Testimonial')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.web_home.testimonials');
    }

    public static function getLabel(): string
    {
        return __('messages.web_home.testimonials');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Testimonial')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Testimonial')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Testimonial')) {
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
                TextInput::make('name')
                    ->label(__('messages.testimonial.name').':')
                    ->required()
                    ->validationAttribute(__('messages.testimonial.name'))
                    ->placeholder(__('messages.testimonial.name'))
                    ->columnSpan(12),

                Textarea::make('description')
                    ->label(__('messages.testimonial.description').':')
                    ->required()
                    ->validationAttribute(__('messages.testimonial.description'))
                    ->rows(6)
                    ->placeholder(__('messages.testimonial.description'))
                    ->extraAttributes(['class' => 'testimonialDescription'])
                    ->columnSpan(12),

                SpatieMediaLibraryFileUpload::make('profile')
                    ->label(__('messages.common.profile').':')
                    ->collection(Testimonial::PATH)
                    ->avatar()
                    ->imageCropAspectRatio(null)
                    ->disk(config('app.media_disk'))
                    ->columnSpan(12),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Testimonial')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('tenant_id', auth()->user()->tenant_id)->where('id', '!=', auth()->user()->id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->columns([
                SpatieMediaLibraryImageColumn::make('icon')
                    ->collection(Testimonial::PATH)
                    ->disk(config('app.media_disc'))
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.common.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('messages.testimonial.description'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.testimonial_update'))->modalHeading(__('messages.testimonial.edit_testimonial')),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.testimonial_delete')),
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
            'index' => Pages\ManageTestimonials::route('/'),
        ];
    }
}
