<?php

namespace App\Livewire;

use App\Models\Patient;
use App\Models\VaccinatedPatients;
use App\Models\Vaccination;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class PatientVaccinationRelationTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public $id;

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(VaccinatedPatients::where('patient_id', $this->id)->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc'))
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('vaccination.name')
                    ->label(__('messages.vaccinated_patient.vaccination_name'))
                    ->default(__('messages.common.n/a'))
                    ->sortable()->searchable(),
                TextColumn::make('vaccination_serial_number')
                    ->label(__('messages.document.title'))
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dose_number')
                    ->label(__('messages.vaccinated_patient.does_no'))
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dose_given_date')
                    ->label(__('messages.vaccinated_patient.dose_given_date'))
                    ->default(__('messages.common.n/a'))
                    ->html()
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('g:i A').' <br>   '.\Carbon\Carbon::parse($state)->translatedFormat('jS M, Y'))
                    ->searchable()
                    ->sortable(),
            ])
            ->actionsColumnLabel(function () {
                if (auth()->user()->hasRole('Patient')) {
                    return null;
                }

                return __('messages.common.action');
            })
            ->actions([
                Actions\EditAction::make()
                    ->visible(fn ($record) => ! auth()->user()->hasRole('Patient'))
                    ->form([
                        Group::make([
                            Select::make('patient_id')
                                ->label(__('messages.document.patient').': ')
                                ->placeholder(__('messages.document.select_patient'))
                                ->options(Patient::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id'))
                                ->native(false)
                                ->required()
                                ->validationMessages([
                                    'required' => __('messages.fields.the').' '.__('messages.document.patient').' '.__('messages.fields.required'),
                                ]),
                            Select::make('vaccination_id')
                                ->label(__('messages.vaccinated_patient.vaccine').': ')
                                ->placeholder(__('messages.vaccination.select_vaccination'))
                                ->options(Vaccination::where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('name', 'id'))
                                ->native(false)
                                ->required()
                                ->validationMessages([
                                    'required' => __('messages.fields.the').' '.__('messages.vaccinated_patient.vaccine').' '.__('messages.fields.required'),
                                ]),
                            TextInput::make('vaccination_serial_number')
                                ->label(__('messages.vaccinated_patient.serial_no').': ')
                                ->placeholder(__('messages.vaccinated_patient.serial_no'))
                                ->maxLength(255)
                                ->columnSpanFull(),
                            TextInput::make('dose_number')
                                ->label(__('messages.vaccinated_patient.does_no').': ')
                                ->placeholder(__('messages.vaccinated_patient.does_no'))
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->rules(['numeric', 'lte:50']),
                            DateTimePicker::make('dose_given_date')
                                ->label(__('messages.vaccinated_patient.dose_given_date').': ')
                                ->placeholder(__('messages.vaccinated_patient.dose_given_date'))
                                ->required()
                                ->disabled()
                                ->native(false)
                                ->default(now()),
                            Textarea::make('description')
                                ->label(__('messages.document.notes').': ')
                                ->placeholder(__('messages.document.notes'))
                                ->rows(4)
                                ->columnSpanFull(),
                        ])->columns(2),
                    ])
                    ->successNotificationTitle(__('messages.flash.vaccination_updated'))
                    ->iconButton(),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(function () {
                        if (auth()->user()->hasRole('Patient')) {
                            return false;
                        }

                        return true;
                    })
                    ->successNotificationTitle(__('messages.flash.document_deleted')),
            ])
            ->filters([
                //
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public function render()
    {
        return view('livewire.patient-vaccination-relation-table');
    }
}
