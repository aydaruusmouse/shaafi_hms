<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineCategoryResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineCategoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageMedicineCategories extends ManageRecords
{
    protected static string $resource = MedicineCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('messages.medicine.new_medicine_category'))->modalWidth('xl')->createAnother(false)->successNotificationTitle(__('messages.flash.medicine_category_saved'))->modalHeading(__('messages.medicine.new_medicine_category'))
                ->action(function (array $data) {
                    $foundCategory = Category::where('name', $data['name'])->whereTenantId(getLoggedInUser()->tenant_id)->first();

                    if ($foundCategory) {
                        Notification::make()
                            ->danger()
                            ->title(__('validation.unique', ['attribute' => __('messages.medicine.category')]))
                            ->send();
                        $this->halt();

                        return;

                    } else {
                        Category::create($data);
                    }
                }),
        ];
    }
}
