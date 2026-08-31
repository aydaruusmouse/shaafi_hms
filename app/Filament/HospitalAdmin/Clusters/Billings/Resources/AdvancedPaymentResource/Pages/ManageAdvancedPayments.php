<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\AdvancedPaymentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\AdvancedPaymentResource;
use App\Repositories\AdvancedPaymentRepository;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Schema;

class ManageAdvancedPayments extends ManageRecords
{
    protected static string $resource = AdvancedPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalWidth('md')
                ->createAnother(false)
                ->successNotificationTitle(__('messages.flash.advanced_payment_save'))
                ->using(function ($data) {
                    $data['amount'] = removeCommaFromNumbers($data['amount']);
                    Schema::disableForeignKeyConstraints();
                    $rec = app(AdvancedPaymentRepository::class)->create($data);
                    Schema::enableForeignKeyConstraints();

                    return $rec;
                })
                ->after(function ($record) {
                    try {
                        app(AdvancedPaymentRepository::class)->createNotification($record->toArray());
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title($e->getMessage())
                            ->error();
                    }
                }),
        ];
    }
}
