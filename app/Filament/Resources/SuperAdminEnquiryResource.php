<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuperAdminEnquiryResource\Pages;
use App\Models\SuperAdminEnquiry;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SuperAdminEnquiryResource extends Resource
{
    protected static ?string $model = SuperAdminEnquiry::class;

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationIcon = 'fab-elementor';

    protected static ?string $modelLabel = 'Enquiries';

    protected static ?string $navigationLabel = 'Enquiries';

    public static function getNavigationLabel(): string
    {
        return __('messages.landing.enquiry');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.landing.enquiry');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('')
                    ->schema([
                        TextEntry::make('full_name')
                            ->label(__('messages.common.name').':'),
                        TextEntry::make('email')
                            ->label(__('messages.user.email').':'),
                        TextEntry::make('phone')
                            ->label(__('messages.user.phone').':')
                            ->default(__('messages.common.n/a')),
                        TextEntry::make('status')
                            ->label(__('messages.common.status').':')
                            ->badge()
                            ->getStateUsing(fn (SuperAdminEnquiry $record) => $record->status == 1 ? __('messages.enquiry.read') : __('messages.enquiry.unread'))
                            ->color(fn (SuperAdminEnquiry $record) => $record->status == 1 ? 'success' : 'danger'),
                        TextEntry::make('created_at')
                            ->label(__('messages.common.created_at').':')
                            ->since(),
                        TextEntry::make('updated_at')
                            ->label(__('messages.common.last_updated').':')
                            ->since(),
                        TextEntry::make('message')
                            ->label(__('messages.enquiry.message').':')
                            ->columnSpan(2),

                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->recordUrl(false)
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('messages.common.name'))
                    ->searchable()
                    ->sortable(['first_name', 'last_name']),
                TextColumn::make('message')
                    ->label(__('messages.enquiry.message'))
                    ->words(10)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('messages.enquiry.read'))
                    ->getStateUsing(fn (SuperAdminEnquiry $record) => $record->status == 1 ? __('messages.enquiry.read') : __('messages.enquiry.unread'))
                    ->badge()
                    ->color(fn (SuperAdminEnquiry $record) => $record->status == 1 ? 'success' : 'danger')
                    ->searchable()
                    ->sortable(),

            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('messages.user.status').':')
                    ->options([
                        '' => __('messages.filter.all'),
                        '1' => __('messages.enquiry.read'),
                        '0' => __('messages.enquiry.unread'),
                    ])
                    ->native(false),
            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip(__('messages.common.view'))
                    ->color('info')
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->tooltip(__('messages.common.delete'))
                    ->iconButton()
                    ->successNotificationTitle(__('messages.flash.enquiry_delete')),
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
            'index' => Pages\ListSuperAdminEnquiries::route('/'),
            // 'create' => Pages\CreateSuperAdminEnquiry::route('/create'),
            'view' => Pages\ViewSuperAdminEnquiry::route('/{record}'),
            // 'edit' => Pages\EditSuperAdminEnquiry::route('/{record}/edit'),
        ];
    }
}
