<?php

namespace App\Filament\HospitalAdmin\Clusters\Document\Resources;

use App\Filament\HospitalAdmin\Clusters\Document;
use App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentTypeResource\Pages;
use App\Models\Document as ModelsDocument;
use App\Models\DocumentType;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;

    protected static ?string $cluster = Document::class;

    protected static ?int $navigationSort = 1;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Document Types')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Document Types')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.document.document_type');
    }

    public static function getLabel(): string
    {
        return __('messages.document.document_type');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Document Types')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Document Types')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Document Types')) {
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
                    ->required()
                    ->placeholder(__('messages.document.document_type'))
                    ->columnSpanFull(),
                // ->url(fn($record) => DocumentTypeResource::getUrl('edit', ['record' => $record])),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Document Types')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.document.document_type'))
                    ->color('primary')
                    // ->url(fn($record): string => route('filament.hospitalAdmin.document.resources.document-types.view', $record))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.document_type_updated'))
                    ->action(function ($record, $data) {
                        $foundType = DocumentType::where('name', $data['name'])->where('id', '!=', $record->id)->whereTenantId(getLoggedInUser()->tenant_id)->first();
                        if ($foundType) {
                            return Notification::make()
                                ->danger()
                                ->title(__('validation.unique', ['attribute' => __('messages.document.document_type')]))
                                ->send();
                        } else {
                            $record->update($data);

                            return Notification::make()
                                ->success()
                                ->title(__('messages.flash.document_type_updated'))
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (DocumentType $record) {
                        if (! canAccessRecord(DocumentType::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.document_type_not_found'))
                                ->send();
                        }

                        $documentTypeModel = [
                            ModelsDocument::class,
                        ];
                        $result = canDelete($documentTypeModel, 'document_type_id', $record->id);
                        if ($result) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.document_type_cant_deleted'))
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.document_type_deleted'))
                            ->send();
                    }),
            ])
            ->recordAction(null)
            ->actionsColumnLabel(__('messages.common.action'))
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
            'index' => Pages\ListDocumentTypes::route('/'),
            'view' => Pages\ViewDocumentType::route('/{record}'),
        ];
    }
}
