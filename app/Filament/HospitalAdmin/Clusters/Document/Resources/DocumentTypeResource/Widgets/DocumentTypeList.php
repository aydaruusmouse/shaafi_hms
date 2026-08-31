<?php

namespace App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentTypeResource\Widgets;

use App\Models\Document;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DocumentTypeList extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public $record;

    public function mount($record)
    {
        $this->record = $record->id;
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50])
            ->query(
                Document::where('document_type_id', $this->record)
            )
            ->columns([
                TextColumn::make('attachment_download')
                    ->label(__('messages.document.attachment'))
                    ->getStateUsing(function ($record) {

                        if ($record->document_url) {

                            return '<a href="'.$record->document_url.'" style="margin-left: -17px; color: #4F46E5;" download>'.__('messages.document.download').'</a>';
                        }

                        return __('messages.common.n/a');
                    })
                    ->extraAttributes(['class' => 'text-center'])
                    ->html(),
                TextColumn::make('title')
                    ->label(__('messages.document.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('patient.patientUser.full_name')
                    ->label(__('messages.document.patient'))
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->sortable(),
                TextColumn::make('user.full_name')
                    ->label(__('messages.document.uploaded_by'))
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->sortable(),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }
}
