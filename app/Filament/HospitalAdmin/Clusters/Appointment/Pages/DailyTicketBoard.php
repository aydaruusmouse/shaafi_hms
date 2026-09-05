<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment;
use App\Models\Doctor;
use App\Support\AppointmentTickets;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Support\Collection;

class DailyTicketBoard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Appointment::class;

    protected static string $view = 'filament.hospital-admin.clusters.appointment.pages.daily-ticket-board';

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 6;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'doctor_id' => null,
            'ticket_date' => Carbon::today()->format('Y-m-d'),
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.appointment.ticket_board');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()?->hasRole(['Admin']) && ! getModuleAccess('Appointments')) {
            return false;
        }

        return (bool) auth()->user()?->hasRole(['Admin', 'Doctor', 'Receptionist']);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('doctor_id')
                    ->label(__('messages.case.doctor').':')
                    ->placeholder(__('messages.web_appointment.select_doctor'))
                    ->options(function () {
                        return Doctor::query()
                            ->withWhereHas('doctorUser', fn ($query) => $query->where('status', true))
                            ->get()
                            ->pluck('doctorUser.full_name', 'id');
                    })
                    ->searchable()
                    ->native(false)
                    ->live(),
                DatePicker::make('ticket_date')
                    ->label(__('messages.appointment.date').':')
                    ->native(false)
                    ->live()
                    ->default(Carbon::today()),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function getTickets(): Collection
    {
        $doctorId = (int) ($this->data['doctor_id'] ?? 0);
        $ticketDate = $this->data['ticket_date'] ?? null;

        if (! $doctorId || ! $ticketDate) {
            return collect();
        }

        return AppointmentTickets::ticketsForDate($doctorId, $ticketDate);
    }
}
