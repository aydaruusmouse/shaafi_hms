<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Pages;

use App\Filament\HospitalAdmin\Clusters\BedManagement;
use App\Models\BedType;
use App\Models\PatientAdmission;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;

class BedStatus extends Page
{
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.hospital-admin.clusters.bed-management.pages.bed-status';

    protected static ?string $cluster = BedManagement::class;

    public $bedTypes;

    public $patientAdmissions;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Doctor', 'Nurse']) && ! getModuleAccess('Bed Assigns')) {
            return false;
        }

        return true;
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(url()->previous()),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.bed_status.bed_status');
    }

    public function getTitle(): string
    {
        return __('messages.bed_status.bed_status');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse'])) {
            return true;
        }

        return false;
    }

    public function mount()
    {
        $this->bedTypes = BedType::with(['beds.bedAssigns.patient.user'])->where('tenant_id', getLoggedInUser()->tenant_id)->get();
        $this->patientAdmissions = PatientAdmission::whereHas('bed')->with('bed.bedType')->where('tenant_id', getLoggedInUser()->tenant_id)->get();
    }
}
