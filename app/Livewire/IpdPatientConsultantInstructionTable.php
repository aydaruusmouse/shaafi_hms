<?php

namespace App\Livewire;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\IpdConsultantRegister;
use App\Models\User;
use Exception;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class IpdPatientConsultantInstructionTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public $id;

    public $processedData = [];

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
    }

    public function GetRecord()
    {
        $IpdConsultantRegister = IpdConsultantRegister::where('ipd_patient_department_id', $this->id)->orderBy('id', 'desc');

        return $IpdConsultantRegister;
    }

    public function EditFormFields(): array
    {
        return [
            Hidden::make('ipd_patient_department_id')->default($this->id),
            DateTimePicker::make('applied_date')->required(),
            Select::make('doctor_id')
                ->options(function () {
                    return Doctor::with('user')->get()
                        ->where('user.status', User::ACTIVE)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->pluck('user.full_name', 'id');
                })
                ->label(__('messages.death_report.doctor_name'))
                ->native(false)
                ->searchable()
                ->required()
                ->validationMessages([
                    'required' => __('messages.fields.the').' '.__('messages.death_report.doctor_name').' '.__('messages.fields.required'),
                ]),
            DateTimePicker::make('instruction_date')->native(false)->required(),
            Textarea::make('instruction')->rows(1)->maxLength(255)->required(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50])
            ->headerActions([
                Actions\CreateAction::make()
                    ->modalWidth('md')
                    ->createAnother(false)
                    ->visible(fn () => hasModulePermission('IPD Consultant Instruction', 'create'))
                    ->form([
                        Hidden::make('ipd_patient_department_id')->default($this->id),
                        Repeater::make('diagnosis')
                            ->addActionLabel(__('messages.common.add'))
                            ->schema([
                                DateTimePicker::make('applied_date')
                                    ->required(),
                                Select::make('doctor_id')
                                    ->options(function () {
                                        return Doctor::with('user')->get()->where('user.status', User::ACTIVE)->where('tenant_id', auth()->user()->tenant_id)->pluck('user.full_name', 'id');
                                    })
                                    ->label(__('messages.death_report.doctor_name'))
                                    ->native(false)
                                    ->searchable()
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('messages.fields.the').' '.__('messages.death_report.doctor_name').' '.__('messages.fields.required'),
                                    ]),
                                DateTimePicker::make('instruction_date')
                                    ->native(false)
                                    ->required(),
                                Textarea::make('instruction')
                                    ->rows(1)
                                    ->maxLength(255)
                                    ->required(),
                            ])->columns(4)->deletable(function ($state) {
                                if (count($state) === 1) {
                                    return false;
                                }

                                return true;
                            }),
                    ])
                    ->using(function (array $data, string $model): Model {
                        $applied_date = [];
                        $doctor_id = [];
                        $instruction_date = [];
                        $instruction = [];

                        foreach ($data['diagnosis'] as $item) {
                            $applied_date[] = $item['applied_date'];
                            $doctor_id[] = $item['doctor_id'];
                            $instruction_date[] = $item['instruction_date'];
                            $instruction[] = $item['instruction'];
                        }

                        $input = [
                            ...$data,
                            'applied_date' => $applied_date,
                            'doctor_id' => $doctor_id,
                            'instruction_date' => $instruction_date,
                            'instruction' => $instruction,
                        ];

                        $data = Arr::except($input, ['diagnosis']);

                        try {
                            $lastCreatedRecord = null;

                            for ($i = 0; $i < count($input['applied_date']); $i++) {
                                if (empty($input['applied_date'][$i])) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('messages.ipd_patient.please_select_applied_date'))
                                        ->send();

                                    continue;
                                } elseif ($input['doctor_id'][$i] == 0) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('messages.appointment.please_select_doctor'))
                                        ->send();

                                    continue;
                                } elseif (empty($input['instruction_date'][$i])) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('messages.ipd_patient.please_select_instruction_date'))
                                        ->send();

                                    continue;
                                } elseif (empty($input['instruction'][$i])) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('messages.ipd_patient.please_enter_instruction'))
                                        ->send();

                                    continue;
                                }

                                $ipdConsultantInstruction = [
                                    'ipd_patient_department_id' => $data['ipd_patient_department_id'],
                                    'applied_date' => $data['applied_date'][$i],
                                    'doctor_id' => $data['doctor_id'][$i],
                                    'instruction_date' => $data['instruction_date'][$i],
                                    'instruction' => $data['instruction'][$i],
                                ];

                                $lastCreatedRecord = $model::create($ipdConsultantInstruction);
                            }

                            // Return the last created record
                            return $lastCreatedRecord;
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                        }
                    })

                    ->modalWidth('6xl')
                    ->successNotificationTitle(__('messages.flash.IPD_consultant_saved'))
                    ->modalHeading(__('messages.ipd_patient_consultant_register.new_consultant_register'))
                    ->label(__('messages.ipd_patient_consultant_register.new_consultant_register')),
            ])
            ->query(self::GetRecord())
            ->columns([
                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.patient_admission.doctor'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->doctor->user->full_name);
                        }
                    })
                    ->url(fn ($record) => DoctorResource::getUrl('view', ['record' => $record->doctor->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('doctor.doctorUser.full_name')
                    ->label('')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'" class="hoverLink">'.$record->doctor->user->full_name.'</a>')
                    ->html()
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('applied_date')
                    ->label(__('messages.ipd_patient_consultant_register.applied_date'))
                    ->formatStateUsing(fn ($record) => '<div class="text-center">'.\Carbon\Carbon::parse($record->applied_date)->format('h:i A').'<br>'.\Carbon\Carbon::parse($record->applied_date)->translatedFormat('jS M, Y').'</div>')
                    ->html()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('instruction_date')
                    ->label(__('messages.ipd_patient_consultant_register.instruction_date'))
                    ->formatStateUsing(fn ($record) => \Carbon\Carbon::parse($record->instruction_date)->translatedFormat('jS M, Y'))
                    ->sortable()
                    ->searchable(),
            ])
            // Actions
            ->actions([
                Actions\EditAction::make()
                    ->modalWidth('md')
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Consultant Instruction', 'edit'))
                    ->form($this->EditFormFields())->successNotificationTitle(__('messages.flash.IPD_consultant_updated')),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Consultant Instruction', 'delete'))
                    ->successNotificationTitle(__('messages.flash.IPD_consultant_deleted')),
            ])
            ->filters([
                //
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'))
            ->emptyStateDescription('');
    }

    public function render()
    {
        return view('livewire.ipd-patient-consultant-instruction-table');
    }
}
