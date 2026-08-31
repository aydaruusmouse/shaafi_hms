<?php

namespace App\Livewire;

use App\Models\OpdTimeline;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class OpdPatientTimeLineTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public $id;

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
    }

    public function GetRecord()
    {
        $OpdTimeline = OpdTimeline::whereOpdPatientDepartmentId($this->id)->orderBy('id', 'desc');

        return $OpdTimeline;
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Actions\CreateAction::make()
                    ->visible(fn () => hasModulePermission('OPD Timelines', 'create'))
                    ->modalWidth('md')
                    ->createAnother(false)
                    ->form([
                        Group::make([
                            Hidden::make('opd_patient_department_id')->default($this->id),
                            TextInput::make('title')
                                ->label(__('messages.ipd_patient_timeline.title'))
                                ->required()
                                ->maxLength(255),
                            DatePicker::make('date')
                                ->native(false)
                                ->label(__('messages.ipd_patient_timeline.date'))
                                ->required(),
                            Textarea::make('description')
                                ->label(__('messages.ipd_patient_timeline.description'))
                                ->maxLength(255),
                            Toggle::make('visible_to_person')
                                ->default(true)
                                ->live(),
                            SpatieMediaLibraryFileUpload::make('attachment')
                                ->label(__('messages.ipd_patient_timeline.document'))
                                ->collection(OpdTimeline::OPD_TIMELINE_PATH)
                                ->disk(config('app.media_disk')),
                        ])->columns(2),
                    ])
                    ->modalWidth('xl')
                    ->successNotificationTitle(__('messages.flash._timeline_saved'))
                    ->modalHeading(__('messages.ipd_patient_timeline.new_ipd_timeline'))
                    ->label(__('messages.ipd_patient_timeline.new_ipd_timeline')),
            ])
            ->query($this->GetRecord())
            ->columns([
                TextColumn::make('date')
                    ->label(__('messages.ipd_patient_charges.date'))
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('jS M, Y')),
                TextColumn::make('title')
                    ->label(__('messages.ipd_patient_timeline.title'))
                    ->extraAttributes(['class' => 'font-black'])
                    ->default(__('messages.common.n/a')),
                TextColumn::make('opd_timeline_document_url')
                    ->label(__('messages.document.attachment'))
                    ->html()
                    ->alignEnd()
                    ->color('primary')
                    ->formatStateUsing(function ($record) {
                        return '<a href="'.$record->opd_timeline_document_url.'" class ="hoverLink" download>'.__('messages.document.download').'</a>';
                    }),
            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->paginated(false)
            ->actions([
                Actions\EditAction::make()
                    ->modalWidth('xl')
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('OPD Timelines', 'edit'))
                    ->form([
                        Group::make([
                            Hidden::make('ipd_patient_department_id')->default($this->id),
                            TextInput::make('title')
                                ->label(__('messages.ipd_patient_timeline.title'))
                                ->required()
                                ->maxLength(255),
                            DatePicker::make('date')
                                ->native(false)
                                ->label(__('messages.ipd_patient_timeline.date'))
                                ->required(),
                            Textarea::make('description')
                                ->label(__('messages.ipd_patient_timeline.description'))
                                ->maxLength(255),
                            Toggle::make('visible_to_person')
                                ->default(true)
                                ->live(),
                            SpatieMediaLibraryFileUpload::make('attachment')
                                ->label(__('messages.ipd_patient_timeline.document'))
                                ->collection(OpdTimeline::OPD_TIMELINE_PATH)
                                ->disk(config('app.media_disk')),
                        ])->columns(2),
                    ])
                    ->successNotificationTitle(__('messages.flash.OPD_timeline_updated')),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('OPD Timelines', 'delete'))
                    ->successNotificationTitle(__('messages.flash.OPD_timeline_deleted')),
            ])
            ->filters([
                //
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'))
            ->emptyStateDescription('');
    }

    public function render()
    {
        return view('livewire.opd-patient-time-line-table');
    }
}
