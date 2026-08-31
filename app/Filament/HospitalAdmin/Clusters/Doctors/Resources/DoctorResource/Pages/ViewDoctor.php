<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Livewire\DoctorAppointmentRelationTable;
use App\Livewire\DoctorCaseRelationTable;
use App\Livewire\DoctorPatientRelationTable;
use App\Livewire\DoctorPayrollRelationTable;
use App\Livewire\DoctorScheduleRelationTable;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;

class ViewDoctor extends ViewRecord
{
    protected static string $resource = DoctorResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(url()->previous()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {

        return $infolist
            ->schema([
                Section::make()->schema([
                    SpatieMediaLibraryImageEntry::make('user.profile')->collection(User::COLLECTION_PROFILE_PICTURES)->label('')->columnSpan(2)->width(100)->height(100)
                        ->defaultImageUrl(function ($record) {
                            if (! $record->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                                return getUserImageInitial($record->id, $record->user->first_name);
                            }
                        })->circular()->columnSpan(1),
                    Group::make([
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
                            // ->extraAttributes(['style' => 'margin: -20px;'])
                            ->formatStateUsing(fn ($state) => "<a href='mailto:{$state}'>{$state}</a>")
                            ->html()
                            ->columnSpan(1),
                    ])->extraAttributes(['class' => 'display-block']),
                    Group::make([]),
                    Group::make([]),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".(isset($record->cases) && $record->cases ? $record->cases->count() : '0').'</span> <br> '.__('messages.patient.total_cases'))
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])
                        ->columnSpan(2),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".(isset($record->patients) && $record->patients ? $record->patients->count() : '0').'</span> <br> '.__('messages.patients'))
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])->columnSpan(2),
                    TextEntry::make('id')
                        ->label('')
                        ->formatStateUsing(fn ($record) => "<span class='text-2xl font-bold text-primary-600'>".(isset($record->appointments) && $record->appointments ? $record->appointments->count() : '0').'</span> <br> '.'<span>'.__('messages.patient.total_appointments').'</span>')
                        ->html()->extraAttributes(['class' => 'border p-6 rounded-xl'])
                        ->columnSpan(2),
                ])->columns(10),
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make(__('messages.overview'))
                            ->schema([
                                TextEntry::make('user.designation')
                                    ->label(__('messages.user.designation').':')
                                    ->getStateUsing(fn ($record) => $record->user->designation ?? __('messages.common.n/a')),
                                PhoneEntry::make('user.phone')
                                    ->default(__('messages.common.n/a'))
                                    ->formatStateUsing(function ($state, $record) {
                                        if (str_starts_with($state, '+') && strlen($state) > 4) {
                                            return $state;
                                        }
                                        if (empty($record->user->phone)) {
                                            return __('messages.common.n/a');
                                        }

                                        return $record->user->region_code.$record->user->phone;
                                    })
                                    ->label(__('messages.user.phone').':'),
                                TextEntry::make('user.doctor_department_id')
                                    ->label(__('messages.appointment.doctor_department').':')
                                    ->getStateUsing(function ($record) {
                                        $doctorData = self::getDoctorAssociatedData($record->id);

                                        return getDoctorDepartment($doctorData['doctorData']->doctor_department_id) ?? __('messages.common.n/a');
                                    }),
                                TextEntry::make('user.qualification')
                                    ->label(__('messages.user.qualification').':')
                                    ->getStateUsing(fn ($record) => $record->user->qualification ?? __('messages.common.n/a')),
                                TextEntry::make('user.blood_group')
                                    ->label(__('messages.user.blood_group').':')
                                    ->getStateUsing(fn ($record) => $record->user->blood_group ?? __('messages.common.n/a')),
                                TextEntry::make('user.dob')
                                    ->label(__('messages.user.dob').':')
                                    ->getStateUsing(fn ($record) => $record->user->dob ? Carbon::parse($record->user->dob)->translatedFormat('jS M, Y') : __('messages.common.n/a')),
                                TextEntry::make('user.specialist')
                                    ->label(__('messages.doctor.specialist').':')
                                    ->getStateUsing(function ($record) {
                                        $doctorData = self::getDoctorAssociatedData($record->id);

                                        return $doctorData['doctorData']->specialist ?? __('messages.common.n/a');
                                    }),
                                TextEntry::make('user.gender')
                                    ->label(__('messages.user.gender').':')
                                    ->getStateUsing(fn ($record) => $record->user->gender == 0 ? __('messages.user.male') : __('messages.user.female')),
                                TextEntry::make('created_at')
                                    ->label(__('messages.common.created_at').':')
                                    ->getStateUsing(fn ($record) => $record->user->created_at->diffForHumans()),
                                TextEntry::make('updated_at')
                                    ->label(__('messages.common.last_updated').':')
                                    ->getStateUsing(fn ($record) => $record->user->updated_at->diffForHumans()),
                            ])->columns(2),
                        Tabs\Tab::make(__('messages.cases'))
                            ->schema([
                                Livewire::make(DoctorCaseRelationTable::class),
                            ]),
                        Tabs\Tab::make(__('messages.patients'))
                            ->schema([
                                Livewire::make(DoctorPatientRelationTable::class),
                            ]),
                        Tabs\Tab::make(__('messages.appointments'))
                            ->schema([
                                Livewire::make(DoctorAppointmentRelationTable::class),
                            ]),
                        Tabs\Tab::make(__('messages.schedules'))
                            ->schema([
                                Livewire::make(DoctorScheduleRelationTable::class),
                            ]),
                        Tabs\Tab::make(__('messages.my_payrolls'))
                            ->schema([
                                Livewire::make(DoctorPayrollRelationTable::class),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function getDoctorAssociatedData(int $doctorId)
    {
        $data['doctorData'] = Doctor::with([
            'cases.patient.patientUser',
            'patients.patientUser',
            'schedules',
            'payrolls',
            'doctorUser',
            'address',
            'appointments.doctor.doctorUser',
            'appointments.patient.patientUser',
            'appointments.department',
        ])->findOrFail($doctorId);
        $data['appointments'] = $data['doctorData']->appointments;

        return $data;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
}
