<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HospitalTypeResource\Pages;
use App\Models\HospitalType;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HospitalTypeResource extends Resource
{
    protected static ?string $model = HospitalType::class;

    protected static ?string $navigationIcon = 'fas-hospital';

    public static function getNavigationLabel(): string
    {
        return __('messages.hospitals_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.hospitals_type');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('messages.hospital_type').':')
                    ->placeholder(__('messages.hospital_type'))
                    ->unique('hospital_type', 'name', ignoreRecord: true)
                    ->validationMessages([
                        'unique' => __('messages.hospital_type').' '.__('messages.common.is_already_exists'),
                    ])
                    ->required()
                    ->validationAttribute(__('messages.hospital_type'))
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->query(HospitalType::query()->withCount('users'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.user.name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('messages.hospital_count'))
                    ->badge()
                    ->sortable(),

            ])
            ->recordAction(null)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip(__('messages.common.edit'))
                    ->modalWidth('md')
                    ->successNotificationTitle(__('messages.hospitals_type').' '.__('messages.common.updated_successfully')),
                Tables\Actions\DeleteAction::make()
                    ->tooltip(__('messages.common.delete'))
                    ->iconButton()
                    ->action(function ($record) {
                        $models = [
                            User::class,
                        ];
                        $hospitalExist = canDelete($models, 'hospital_type_id', $record['id']);
                        if ($hospitalExist) {
                            return Notification::make()
                                ->danger()
                                ->title((__('messages.new_change.hospital_not_delete')))
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title((__('messages.hospitals_type').' '.__('messages.common.deleted_successfully')))
                            ->send();
                    }),

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
            'index' => Pages\ManageHospitalTypes::route('/'),
        ];
    }
}
