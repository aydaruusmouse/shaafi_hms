<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources;

use App\Filament\HospitalAdmin\Clusters\FrontOffice;
use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\VisitorResource\Pages;
use App\Models\Visitor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class VisitorResource extends Resource
{
    protected static ?string $model = Visitor::class;

    protected static ?string $cluster = FrontOffice::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Visitors')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Visitors')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.visitors');
    }

    public static function getLabel(): string
    {
        return __('messages.visitors');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Visitors')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Visitors')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Visitors')) {
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
                Section::make()
                    ->schema([
                        Select::make('purpose')
                            ->label(__('messages.visitor.purpose').':')
                            ->placeholder(__('messages.visitor.select_purpose'))
                            ->live()
                            ->options(Visitor::PURPOSE)
                            ->native(false)
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.visitor.purpose').' '.__('messages.fields.required'),
                            ]),
                        TextInput::make('name')
                            ->label(__('messages.user.name').':')
                            ->placeholder(__('messages.user.name'))
                            ->validationAttribute(__('messages.user.name'))
                            ->required(),
                        PhoneInput::make('phone')
                            ->label(__('messages.user.phone').':')
                            ->validationAttribute(__('messages.user.phone'))
                            ->rules(function (Get $get) {
                                return [
                                    'phone:AUTO,'.strtoupper($get('prefix_code')),
                                ];
                            })
                            ->validationMessages([
                                'phone' => __('messages.common.invalid_number'),
                            ])
                            ->defaultCountry('IN')
                            ->showSelectedDialCode(true),
                        TextInput::make('id_card')
                            ->placeholder(__('messages.visitor.id_card'))
                            ->label(__('messages.visitor.id_card').':'),
                        TextInput::make('no_of_person')
                            ->label(__('messages.visitor.number_of_person').':')
                            ->placeholder(__('messages.visitor.number_of_person'))
                            ->numeric()
                            ->minValue(1),
                        DatePicker::make('date')
                            ->native(false)
                            ->label(__('messages.visitor.date').':'),
                        TimePicker::make('in_time')
                            ->label(__('messages.visitor.in_time').':'),
                        TimePicker::make('out_time')
                            ->label(__('messages.visitor.out_time').':'),
                        Textarea::make('note')
                            ->label(__('messages.visitor.note').':')
                            ->placeholder(__('messages.visitor.note'))
                            ->rows(4),
                        SpatieMediaLibraryFileUpload::make('attachment')
                            ->label(__('messages.document.attachment').':')
                            ->disk(config('app.media_disk'))
                            ->collection(Visitor::PATH),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Visitors')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            $query->whereTenantId(getLoggedInUser()->tenant_id);

            return $query;
        });

        return $table
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('purpose')
                    ->getStateUsing(function ($record) {
                        if ($record->purpose == 1) {
                            return __('Visit');
                        } elseif ($record->purpose == 2) {
                            return __('Enquiry');
                        } else {
                            return __('Seminar');
                        }
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->getStateUsing(fn ($record) => $record->phone ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_card')
                    ->getStateUsing(fn ($record) => $record->id_card ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_of_person')
                    ->getStateUsing(fn ($record) => $record->no_of_person ?? __('messages.common.n/a'))
                    ->label(__('messages.visitor.number_of_person'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('messages.visitor.date'))
                    ->sortable()
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->date ? Carbon::parse($record->date)->translatedFormat('jS M, Y') : __('messages.common.n/a')),
                Tables\Columns\TextColumn::make('in_time')
                    ->getStateUsing(function ($record) {
                        return $record->in_time ? Carbon::parse($record->in_time)->format('h:i:s') : __('messages.common.n/a');
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('out_time')
                    ->getStateUsing(function ($record) {
                        return $record->out_time ? Carbon::parse($record->out_time)->format('h:i:s') : __('messages.common.n/a');
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('attachment')
                    ->getStateUsing(function ($record) {
                        if ($record->document_url) {
                            return '<a href="'.$record->document_url.'" style="margin-left: -17px; color: #4F46E5;" download>Download</a>';
                        }

                        return __('messages.common.n/a');
                    })
                    ->html(),
            ])
            ->filters([
                SelectFilter::make('purpose')
                    ->options(Visitor::PURPOSE)
                    ->label(__('messages.common.status').':')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.visitor_updated')),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.visitor_deleted')),
            ])
            ->actionsColumnLabel(__('Action'))
            ->bulkActions([
                //
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
            'index' => Pages\ListVisitors::route('/'),
            'create' => Pages\CreateVisitor::route('/create'),
            'edit' => Pages\EditVisitor::route('/{record}/edit'),
        ];
    }
}
