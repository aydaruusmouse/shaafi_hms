<?php

namespace App\Filament\Clusters\LandingCMS\Resources;

use App\Filament\Clusters\LandingCMS;
use App\Filament\Clusters\LandingCMS\Resources\FaqsResource\Pages;
use App\Models\Faqs;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaqsResource extends Resource
{
    protected static ?string $model = Faqs::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 7;

    protected static ?string $cluster = LandingCMS::class;

    public static function getNavigationLabel(): string
    {
        return __('messages.faq');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.faqs.faqs');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('question')
                    ->label(__('messages.faqs.question').':')
                    ->placeholder(__('messages.faqs.question'))
                    ->validationAttribute(__('messages.faqs.question'))
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\Textarea::make('answer')
                    ->label(__('messages.faqs.question').':')
                    ->validationAttribute(__('messages.faqs.question'))
                    ->placeholder(__('messages.faqs.question'))
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('question')
                    ->label(__('messages.faqs.question').':'),
                TextEntry::make('answer')
                    ->label(__('messages.faqs.question').':'),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->label(__('messages.faqs.question'))
                    ->sortable()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('answer')
                    ->label(__('messages.faqs.answer'))
                    ->sortable()
                    ->wrap()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip(__('messages.common.view'))
                    ->modalHeading(__('messages.faqs.show'))
                    ->iconButton()
                    ->color('info')
                    ->modalWidth('md'),
                Tables\Actions\EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton()
                    ->successNotificationTitle(__('messages.flash.FAQs_updated'))
                    ->modalHeading(__('messages.faqs.edit_faqs'))
                    ->modalWidth('md'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.common.delete').' '.__('messages.faq'))
                    ->iconButton()
                    ->successNotificationTitle(__('messages.flash.FAQs_deleted'))
                    ->modalWidth('md'),
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
            'index' => Pages\ManageFaqs::route('/'),
        ];
    }
}
