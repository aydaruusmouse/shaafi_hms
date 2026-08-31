<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource;
use App\Models\Patient;
use App\Repositories\BillRepository;
use Carbon\Carbon;
use Carbon\Exceptions\Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.bill_saved');
    }

    protected function beforeCreate(): void
    {
        $patientId = Patient::with('patientUser')->whereId($this->data['patient_id'])->first();
        $birthDate = $patientId->patientUser->dob;
        $billDate = Carbon::parse($this->data['bill_date'])->toDateString();
        if (! empty($birthDate) && $billDate < $birthDate) {
            Notification::make()
                ->danger()
                ->title(__('messages.flash.bill_date_smaller'))
                ->send();
            $this->halt;
        }
    }

    protected function handleRecordCreation(array $input): Model
    {
        $billRepository = app(BillRepository::class);
        try {
            DB::beginTransaction();
            $patientId = Patient::with('patientUser')->whereId($input['patient_id'])->first();
            $birthDate = $patientId->patientUser->dob;
            $billDate = Carbon::parse($input['bill_date'])->toDateString();
            if (! empty($birthDate) && $billDate < $birthDate) {
                return $this->sendError(__('messages.flash.bill_date_smaller'));
            }
            $bill = $billRepository->saveBill($input);
            $billRepository->saveNotification($input);
            DB::commit();

            return $bill;
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e->getMessage());
        }
    }
}
