<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyTestResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyTestResource;
use App\Models\PathologyParameter;
use App\Models\PathologyParameterItem;
use App\Models\PathologyUnit;
use App\Repositories\PathologyTestRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditPathologyTest extends EditRecord
{
    protected static string $resource = PathologyTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $id = $data['id'];

        $parameterItems = PathologyParameterItem::where('pathology_id', $id)->get();

        $formattedData = [];

        foreach ($parameterItems as $item) {
            $reference_range = PathologyParameter::where('id', ($item->parameter_id))->value('reference_range');
            $unit_id = PathologyParameter::where('id', ($item->parameter_id))->value('unit_id');
            $unit = PathologyUnit::where('id', $unit_id)->value('name');
            $formattedData['parameter'][] = [
                'pathology_id' => $item->pathology_id,
                'patient_result' => $item->patient_result,
                'parameter_id' => $item->parameter_id,
                'reference_range' => $reference_range,
                'unit_id' => $unit,
            ];
        }

        $data = Arr::except($data, ['unit_id']);

        $data = $data + $formattedData;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $input): Model
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

        app(PathologyTestRepository::class)->update($input, $record);
        $record = new ($this->getModel())($input);

        return $record;
    }

    public function prepareInputForInvoiceItem(array $input): array
    {
        $items = [];
        foreach ($input as $key => $data) {
            foreach ($data as $index => $value) {
                $items[$index][$key] = $value;
                if (! (isset($items[$index]['price']) && $key == 'price')) {
                    continue;
                }
                $items[$index]['price'] = removeCommaFromNumbers($items[$index]['price']);
            }
        }

        return $items;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.pathology_test_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
