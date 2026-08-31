<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Livewire\PatientAdmissionRelationTable;
use App\Livewire\PatientAdvancePaymentRelationTable;
use App\Livewire\PatientAppointmentRelationTable;
use App\Livewire\PatientBillRelationTable;
use App\Livewire\PatientCasesRelationTable;
use App\Livewire\PatientDocumentRelationTable;
use App\Livewire\PatientInvoiceRelationTable;
use App\Livewire\PatientVaccinationRelationTable;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(url()->previous()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make()->schema([
                    SpatieMediaLibraryImageEntry::make('user.profile')->collection(User::COLLECTION_PROFILE_PICTURES)->label('')->columnSpan(2)->width(100)->height(100)->defaultImageUrl(function ($record) {
                        if (! $record->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->user->full_name);
                        }
                    })->circular()->columnSpan(1),
                    InfolistGroup::make([
                        TextEntry::make('user.status')
                            ->label('')
                            ->formatStateUsing(fn ($state) => $state ? __('messages.common.active') : __('messages.common.deactive'))
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->columnSpan(1),
                        TextEntry::make('user.full_name')
                            ->label('')
                            ->extraAttributes(['class' => 'font-black'])
                            ->color('primary')
                            ->columnSpan(1),
                        TextEntry::make('user.email')
                            ->label('')
                            ->icon('fas-envelope')
                            ->formatStateUsing(function ($state, $record) {
                                if (auth()->user()->hasRole('Patient')) {
                                    return $state;
                                }

                                return "<a href='mailto:{$state}'>{$state}</a>";
                            })
                            ->html()
                            ->columnSpan(1),
                    ]),
                    TextEntry::make('user.address')
                        ->label('')
                        ->icon('fas-location-dot')
                        ->getStateUsing(function ($record) {
                            if (! empty($record->address->address1) && ! empty($record->address->address2)) {
                                return $record->address->address1.', '.$record->address->address2;
                            } elseif (! empty($record->address->address1)) {
                                return $record->address->address1;
                            } elseif (! empty($record->address->address2)) {
                                return $record->address->address2;
                            } else {
                                return __('messages.common.n/a');
                            }
                        })
                        ->html()->columnSpan(2),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".(isset($record->cases) && $record->cases ? $record->cases->count() : '0').'</span> <br> '.__('messages.patient.total_cases'))
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])
                        ->columnSpan(2),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".(isset($record->admissions) && $record->admissions ? $record->admissions->count() : '0').'</span> <br> '.__('messages.patient.total_admissions'))
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])->columnSpan(2),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".(isset($record->appointments) && $record->appointments ? $record->appointments->count() : '0').'</span> <br> '.'<span>'.__('messages.patient.total_appointments').'</span>')
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])
                        ->columnSpan(2),
                ])->columns(10),
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make(__('messages.overview'))->schema([
                            PhoneEntry::make('user.phone')
                                ->label(__('messages.user.phone').':')
                                ->default(__('messages.common.n/a'))
                                ->formatStateUsing(function ($state, $record) {
                                    if (str_starts_with($state, '+') && strlen($state) > 4) {
                                        return $state;
                                    }
                                    if (empty($record->user->phone)) {
                                        return __('messages.common.n/a');
                                    }

                                    return $record->user->region_code.$record->user->phone;
                                }),
                            TextEntry::make('user.gender')
                                ->label(__('messages.user.gender').':')
                                ->getStateUsing(fn ($record) => $record->user->gender == 0 ? __('messages.user.male') : __('messages.user.female')),
                            TextEntry::make('user.blood_group')
                                ->label(__('messages.user.blood_group').':')
                                ->getStateUsing(fn ($record) => $record->user->blood_group ?? __('messages.common.n/a')),
                            TextEntry::make('user.dob')
                                ->label(__('messages.user.dob').':')
                                ->getStateUsing(fn ($record) => $record->user->dob ? Carbon::parse($record->user->dob)->translatedFormat('jS M, Y') : __('messages.common.n/a')),
                            TextEntry::make('created_at')
                                ->label(__('messages.common.created_at').':')
                                ->getStateUsing(fn ($record) => $record->user->created_at->diffForHumans()),
                            TextEntry::make('updated_at')
                                ->label(__('messages.common.last_updated').':')
                                ->getStateUsing(fn ($record) => $record->user->updated_at->diffForHumans()),
                        ])->columns(2),
                        Tabs\Tab::make(__('messages.cases'))->schema([
                            Livewire::make(PatientCasesRelationTable::class),
                        ]),
                        Tabs\Tab::make(__('messages.patient_admissions'))->schema([
                            Livewire::make(PatientAdmissionRelationTable::class),
                        ]),
                        Tabs\Tab::make(__('messages.appointments'))->schema([
                            Livewire::make(PatientAppointmentRelationTable::class),
                        ]),
                        Tabs\Tab::make(__('messages.bills'))->schema([
                            Livewire::make(PatientBillRelationTable::class),
                        ]),
                        Tabs\Tab::make(__('messages.invoices'))->schema([
                            Livewire::make(PatientInvoiceRelationTable::class),
                        ]),
                        Tabs\Tab::make(__('messages.advanced_payments'))->schema([
                            Livewire::make(PatientAdvancePaymentRelationTable::class),
                        ]),
                        Tabs\Tab::make(__('messages.documents'))->schema([
                            Livewire::make(PatientDocumentRelationTable::class),
                        ]),
                        Tabs\Tab::make(__('messages.vaccinations'))->schema([
                            Livewire::make(PatientVaccinationRelationTable::class),
                        ]),
                    ])->columnSpanFull(),
            ]);
    }
}
