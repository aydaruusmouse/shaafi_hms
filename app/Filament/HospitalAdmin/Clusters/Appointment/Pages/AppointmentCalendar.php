<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment;
use App\Filament\HospitalAdmin\Clusters\Appointment\Widgets\AppoinmentCalenderWidget;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;

class AppointmentCalendar extends Page
{
    protected static ?string $cluster = Appointment::class;

    protected static string $view = 'filament.hospital-admin.clusters.appointment.pages.appointment-calendar';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 1;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('messages.appointment.google_calendar');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()?->hasRole(['Admin']) && ! getModuleAccess('Appointments')) {
            return false;
        }

        return (bool) auth()->user()?->hasRole(['Admin', 'Doctor', 'Receptionist', 'Patient']);
    }

    public static function getWidgets(): array
    {
        return [
            AppoinmentCalenderWidget::class,
        ];
    }
}
