<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyTestResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyTestResource;
use App\Repositories\PathologyTestRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreatePathologyTest extends CreateRecord
{
    protected static string $resource = PathologyTestResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function handleRecordCreation(array $input): Model
    {

        $parameter_id = [];
        $patient_result = [];
        $reference_range = [];
        $unit_id = [];

        foreach ($input['parameter'] as $item) {
            $parameter_id[] = $item['parameter_id'];
            $patient_result[] = $item['patient_result'];
            $reference_range[] = $item['reference_range'];
            $unit_id[] = $item['unit_id'];
        }

        $input = [
            ...$input,
            'parameter_id' => $parameter_id,
            'patient_result' => $patient_result,
            'reference_range' => $reference_range,
            'unit_id' => $unit_id,
        ];
        $input = Arr::except($input, ['parameter']);
        // dd($input);
        $input['standard_charge'] = removeCommaFromNumbers($input['standard_charge']);
        $input['unit'] = ! empty($input['unit']) ? $input['unit'] : null;
        $input['subcategory'] = ! empty($input['subcategory']) ? $input['subcategory'] : null;
        $input['method'] = ! empty($input['method']) ? $input['method'] : null;
        $input['report_days'] = ! empty($input['report_days']) ? $input['report_days'] : null;

        if ($input['parameter_id']) {
            foreach ($input['parameter_id'] as $key => $value) {
                if ($input['parameter_id'][$key] == null) {
                    // Flash::error(__('messages.new_change.parameter_name_required'));
                    // return redirect()->back();
                    Notification::make()
                        ->title(__('messages.new_change.parameter_name_required'))
                        ->danger()
                        ->send();
                }
                if ($input['patient_result'][$key] == null) {
                    // Flash::error(__('messages.new_change.patient_result_required'));
                    // return redirect()->back();

                    Notification::make()
                        ->title(__('messages.new_change.patient_result_required'))
                        ->danger()
                        ->send();
                }
            }
        }
        app(PathologyTestRepository::class)->store($input);

        $record = new ($this->getModel())($input);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.pathology_test_saved');
    }
}
