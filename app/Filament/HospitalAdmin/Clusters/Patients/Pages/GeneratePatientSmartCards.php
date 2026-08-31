<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Patient;
use App\Models\SmartPatientCard;
use App\Models\User;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\CreateAction;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

// use Filament\Tables\Concerns\InteractsWithTable;

class GeneratePatientSmartCards extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.hospital-admin.clusters.patients.pages.generate-patient-smart-cards';

    protected static ?string $cluster = Patients::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Case Manager'])) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Patients')) {
            return false;
        }

        return true;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['Admin', 'Receptionist']);
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('messages.lunch_break.generate_smart_patient_card'))
                ->modalWidth('md')
                ->modalHeading(__('messages.lunch_break.generate_smart_patient_card'))
                ->modalFooterActionsAlignment('end')
                ->createAnother(false)
                ->form(function () {
                    $options = SmartPatientCard::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('template_name', 'id')->filter(function ($label) {
                        return ! empty($label);
                    });

                    return [
                        Select::make('template_id')
                            ->options($options)
                            ->native(false)
                            ->searchable()
                            ->label(__('messages.lunch_break.template_name'))
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.lunch_break.template_name').' '.__('messages.fields.required'),
                            ]),
                        Radio::make('type')
                            ->label(__('messages.common.select_type'))
                            ->required()
                            ->validationAttribute(__('messages.common.select_type'))
                            ->inline()
                            ->live()
                            ->options([
                                'all' => __('messages.lunch_break.for_all_patient'),
                                'remaining' => __('messages.lunch_break.remaining_patient'),
                                'one' => __('messages.lunch_break.only_one_patient'),
                            ]),
                        Select::make('patient_id')
                            ->hidden(fn (Get $get) => $get('type') != 'one')
                            ->label(__('messages.document.select_patient'))
                            ->placeholder(__('messages.document.select_patient'))
                            ->required()
                            ->options(Patient::where('tenant_id', getLoggedInUser()->tenant_id)
                                ->whereNull('template_id')
                                ->with('user')
                                ->get()
                                ->pluck('user.full_name', 'id')
                                ->filter(function ($label) {
                                    return ! empty($label);
                                }))
                            ->native(false)
                            ->searchable()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.document.select_patient').' '.__('messages.fields.required'),
                            ]),
                    ];
                })
                ->action(function (array $data) {
                    if ($data['type'] == 'one') {
                        $patient = Patient::find($data['patient_id']);
                        $patient->template_id = $data['template_id'];
                        $patient->save();

                        return Notification::make()
                            ->title(__('messages.lunch_break.smart_card_saved'))
                            ->success()
                            ->send();
                    }
                    if ($data['type'] == 'remaining') {
                        $patient = Patient::where('tenant_id', getLoggedInUser()->tenant_id)->whereNull('template_id');
                        foreach ($patient as $key => $value) {
                            $value->template_id = $data['template_id'];
                            $value->save();
                        }

                        return Notification::make()
                            ->title(__('messages.lunch_break.smart_card_saved'))
                            ->success()
                            ->send();
                    }
                    if ($data['type'] == 'all') {
                        $patient = Patient::where('tenant_id', getLoggedInUser()->tenant_id);
                        foreach ($patient as $key => $value) {
                            $value->template_id = $data['template_id'];
                            $value->save();
                        }

                        return Notification::make()
                            ->title(__('messages.lunch_break.smart_card_saved'))
                            ->success()
                            ->send();
                    }
                }),

        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Patient::query()->with('admissions.insurance')->whereNotNull('template_id')->where('tenant_id', getLoggedInUser()->tenant_id))
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('user.profile')
                    ->label(__('messages.invoice.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('user.full_name')
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->label('')
                    ->html()
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->id]).'"class="hoverLink">'.$record->user->full_name.'</a>')
                    ->description(function ($record) {
                        return $record->user->email;
                    }),
                TextColumn::make('patient_unique_id')
                    ->label(__('messages.lunch_break.patient_unique_id'))
                    ->searchable()
                    ->badge()
                    ->sortable(),

                TextColumn::make(name: 'SmartCardTemplate.template_name')
                    ->label(__('messages.lunch_break.template_name'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                // Add any filters if needed
            ])
            ->actions([
                Action::make('download')
                    ->iconButton()
                    ->tooltip(__('messages.document.download'))
                    ->icon('fas-download')
                    ->color('primary')
                    ->url(fn (Patient $record) => route('smart-patient-cards.download', $record->id), shouldOpenInNewTab: true),

                ViewAction::make()->iconButton()
                    ->modalHeading(' ')
                    ->modalCancelAction(false)
                    ->infolist(function (Patient $record) {
                        return Infolist::make()
                            ->schema([
                                ViewEntry::make('')
                                    ->view('infolists.components.smart-card')
                                    ->viewData([
                                        'record' => $record,
                                    ]),
                            ]);
                    }),
                DeleteAction::make()->iconButton()
                    ->hidden(getLoggedinPatient())
                    ->action(function (Patient $record) {
                        $patient = Patient::find($record->id);
                        $patient->template_id = null;
                        $patient->save();

                        return Notification::make()
                            ->title(__('messages.flash.patient_smart_card_deleted'))
                            ->success()
                            ->send();
                    }),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                // Add any bulk actions if needed
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }
}
