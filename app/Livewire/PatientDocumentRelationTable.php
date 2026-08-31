<?php

namespace App\Livewire;

use App\Models\Document;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class PatientDocumentRelationTable extends Component implements HasForms, HasTable
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
            ->query(Document::where('patient_id', $this->id)->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc'))
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('documentType.name')
                    ->label(__('messages.document.document_type'))
                    ->default(__('messages.common.n/a'))
                    ->sortable()->searchable(),
                TextColumn::make('title')
                    ->label(__('messages.document.title'))
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->extraAttributes(['class' => 'text-center'])
                    ->sortable(),
                TextColumn::make('document_url')
                    ->label(__('messages.document.attachment'))
                    ->html()
                    ->color('primary')
                    ->alignEnd()
                    ->visible(function () {
                        if (auth()->user()->hasRole('Patient')) {
                            return false;
                        }

                        return true;
                    })
                    ->getStateUsing(function ($record) {
                        if ($record->document_url) {

                            return '<a href="'.$record->document_url.'"class="hoverLink" download>'.__('messages.document.download').'</a>';
                        }

                        return __('messages.common.n/a');
                    }),
            ])
            ->actionsColumnLabel(function () {
                if (auth()->user()->hasRole('Patient')) {
                    return null;
                }

                return __('messages.common.actions');
            })
            ->actions([
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
        return view('livewire.patient-document-relation-table');
    }
}
