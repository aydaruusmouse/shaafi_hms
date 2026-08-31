<?php

namespace App\Filament\HospitalAdmin\Clusters\Reports\Resources\BirthReportResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Reports\Resources\BirthReportResource;
use App\Models\PatientCase;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBirthReportCase extends ViewRecord
{
    protected static string $resource = BirthReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(fn () => BirthReportResource::getUrl('index')),
        ];
    }

    public function mount(int|string $record): void
    {
        $this->record = PatientCase::with('patient', 'doctor')->where('id', $record)->first();

        $this->authorizeAccess();

        if (! $this->hasInfolist()) {
            $this->fillForm();
        }
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('')
                    ->schema([
                        TextEntry::make('case_id')
                            ->label(__('messages.operation_report.case_id').':')
                            ->prefix('#')
                            ->badge(),
                        TextEntry::make('patient.patientUser.full_name')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.case.patient').':'),
                        TextEntry::make('phone')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.case.phone').':'),
                        TextEntry::make('doctor.doctorUser.full_name')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.case.doctor').':'),
                        TextEntry::make('date')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.case.case_date').':')
                            ->getStateUsing(fn ($record) => $record->created_at->translatedFormat('jS M,Y g:i A') ?? __('messages.common.n/a')),
                        TextEntry::make('fee')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.case.fee').':'),
                        TextEntry::make('created_at')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.common.created_at').':')
                            ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a')),
                        TextEntry::make('updated_at')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.common.last_updated').':')
                            ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a')),
                        TextEntry::make('status')
                            ->label(__('messages.common.status').':')
                            ->getStateUsing(function ($record) {
                                return $record->status == 1 ? __('messages.common.active') : __('messages.common.de_active');
                            })
                            ->color(function ($record) {
                                return $record->status == 1 ? 'success' : 'danger';
                            })
                            ->badge(),
                        TextEntry::make('description')
                            ->default(__('messages.common.n/a'))
                            ->label(__('messages.common.description').':'),
                    ])->columns(2),
            ]);
    }
}
