<?php

namespace App\Filament\HospitalAdmin\Clusters\Document\Resources;

use App\Filament\HospitalAdmin\Clusters\Document;
use App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Document as DocumentModel;
use App\Models\DocumentType;
use App\Models\Patient;
use App\Models\User;
use App\Repositories\DocumentRepository;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DocumentResource extends Resource
{
    protected static ?string $model = DocumentModel::class;

    protected static ?string $cluster = Document::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Documents')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Documents')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.documents');
    }

    public static function getLabel(): string
    {
        return __('messages.documents');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient']) && getModuleAccess('Documents')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient']) && getModuleAccess('Documents')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient']) && getModuleAccess('Documents')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {

        $fields =
            [
                TextInput::make('title')
                    ->required()
                    ->validationAttribute(__('messages.document.title'))
                    ->label(__('messages.document.title').':')
                    ->placeholder(__('messages.document.title')),
                Select::make('document_type_id')
                    ->required()
                    ->native(false)
                    ->relationship('documentType', 'name')
                    ->label(__('messages.document.document_type').':')
                    ->options(DocumentType::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                    ->placeholder(__('messages.document.document_type'))
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.document.document_type').' '.__('messages.fields.required'),
                    ]),
                Select::make('patient_id')
                    ->label(__('messages.document.patient').':')
                    ->placeholder(__('messages.document.select_patient'))
                    ->options(function () {
                        $patientRepo = app(DocumentRepository::class);

                        return $patientRepo->getSyncList()['patients'];
                    })
                    ->hidden(auth()->user()->hasRole('Patient'))
                    ->native(false)
                    ->required()
                    ->validationAttribute(__('messages.document.patient')),
                SpatieMediaLibraryFileUpload::make('attachment')
                    ->label(__('messages.document.attachment').':')
                    ->disk(config('app.media_disk'))
                    ->collection('document_url')
                    ->required()
                    ->validationAttribute(__('messages.document.attachment')),
                Textarea::make('notes')
                    ->label(__('messages.document.notes').':')
                    ->placeholder(__('messages.document.notes'))
                    ->rows(4)
                    ->columnSpan('full'),
                Hidden::make('uploaded_by')
                    ->default(getLoggedInUserId())
                    ->required(),
            ];

        return $form->schema($fields);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Patient']) && ! getModuleAccess('Documents')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            if (! getLoggedinPatient()) {
                $query = DocumentModel::whereHas('patient.patientUser')->with('documentType', 'patient.patientUser', 'media')->select('documents.*');
            } else {
                $patientId = Patient::where('user_id', getLoggedInUserId())->first();
                $query = DocumentModel::whereHas('patient.patientUser')->with(
                    'documentType',
                    'patient.patientUser',
                    'media'
                )->select('documents.*')->where('patient_id', $patientId->id);
            }

            return $query->whereTenantId(auth()->user()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('attachment')
                    ->label(__('messages.file_name'))
                    ->icon(function ($record) {
                        // if image then image logo and if other fil then file logo
                        $imageExtensions = ['jpg', 'jpeg', 'gif', 'png', 'bmp', 'svg', 'svgz', 'cgm', 'djv', 'djvu', 'ico', 'ief', 'jpe', 'pbm', 'pgm', 'pnm', 'ppm', 'ras', 'rgb', 'tif', 'tiff', 'wbmp', 'xbm', 'xpm', 'xwd'];

                        $explodeImage = explode('.', $record->document_url);
                        $extension = end($explodeImage);

                        if (in_array($extension, $imageExtensions)) {
                            return 'fas-image';
                        } else {
                            return 'fas-file';
                        }
                    })
                    ->getStateUsing(function ($record) {
                        return $record->media[0]->file_name ?? 'No File';
                    }),
                TextColumn::make('documentType.name')->label(__('messages.document.document_type'))->sortable()->searchable(),
                SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                    ->hidden(auth()->user()->hasRole('Patient'))
                    ->label(__('messages.document.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! auth()->user()->hasRole('Patient')) {
                            if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                                return getUserImageInitial($record->id, $record->patient->user->full_name);
                            }
                        }
                    })
                    ->url(fn ($record) => ! auth()->user()->hasRole('Patient') ? PatientResource::getUrl('view', ['record' => $record->patient->id]) : '')
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.patientUser.full_name')
                    ->hidden(auth()->user()->hasRole('Patient'))
                    ->label('')
                    ->description(fn ($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->disabled(auth()->user()->hasRole('Patient'))
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'" class="hoverLink">'.$record->patient->patientUser->full_name.'</a>')
                    ->html()
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->sortable(['users.first_name', 'users.last_name']),
                TextColumn::make('attachment_download')
                    ->label(__('messages.document.attachment'))
                    ->getStateUsing(function ($record) {
                        if (isset($record->document_url) && ! empty($record->document_url)) {
                            return '<a href="'.$record->document_url.'" style="margin-left: -17px;" class="hoverLink" target="_blank">'.__('messages.document.download').'</a>';
                        } else {
                            return __('messages.common.n/a');
                        }
                    })
                    ->color('primary')
                    ->html(),
            ])
            ->filters([
                //
            ])
            ->actions([

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->modalWidth('3xl')
                    ->successNotificationTitle((__('messages.flash.document_updated'))),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (DocumentModel $record) {
                        if (! canAccessRecord(DocumentModel::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.document_not_found'))
                                ->send();
                        }

                        if (getLoggedInUser()->hasRole('Patient')) {
                            if (getLoggedInUser()->owner_id != $record->patient_id) {
                                return Notification::make()
                                    ->danger()
                                    ->title(__('messages.flash.document_not_found'))
                                    ->send();
                            }
                        }

                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.document_deleted'))
                            ->send();
                    }),
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
            'index' => Pages\ListDocuments::route('/'),
        ];
    }
}
