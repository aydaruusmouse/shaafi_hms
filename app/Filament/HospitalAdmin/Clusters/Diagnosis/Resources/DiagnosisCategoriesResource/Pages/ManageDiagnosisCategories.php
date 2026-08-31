<?php

namespace App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisCategoriesResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisCategoriesResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDiagnosisCategories extends ManageRecords
{
    protected static string $resource = DiagnosisCategoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->modalWidth('md')->createAnother(false)->successNotificationTitle(__('messages.flash.diagnosis_category_saved'))->modalHeading(__('messages.diagnosis_category.new_diagnosis_category')),
        ];
    }
}
