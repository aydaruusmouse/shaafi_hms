<?php

namespace App\Repositories;

use App\Models\IpdConsultantRegister;
use Exception;
use Filament\Notifications\Notification;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class IpdConsultantRegisterRepository
 *
 * @version September 9, 2020, 6:56 am UTC
 */
class IpdConsultantRegisterRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'ipd_patient_department_id',
        'applied_date',
        'doctor_id',
        'instruction',
        'instruction_date',
    ];

    /**
     * Return searchable fields
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return IpdConsultantRegister::class;
    }

    public function store(array $input)
    {
        try {
            for ($i = 0; $i < count($input['applied_date']); $i++) {
                if (empty($input['applied_date'][$i])) {
                    Notification::make()
                        ->danger()
                        ->title(__('messages.ipd_patient.please_select_applied_date'))
                        ->send();
                } elseif ($input['doctor_id'][$i] == 0) {
                    Notification::make()
                        ->danger()
                        ->title(__('messages.appointment.please_select_doctor'))
                        ->send();
                } elseif (empty($input['instruction_date'][$i])) {
                    Notification::make()
                        ->danger()
                        ->title(__('messages.ipd_patient.please_select_instruction_date'))
                        ->send();
                } elseif (empty($input['instruction'][$i])) {
                    Notification::make()
                        ->danger()
                        ->title(__('messages.ipd_patient.please_enter_instruction'))
                        ->send();
                }

                $ipdConsultantInstruction = [
                    'ipd_patient_department_id' => $input['ipd_patient_department_id'],
                    'applied_date' => $input['applied_date'][$i],
                    'doctor_id' => $input['doctor_id'][$i],
                    'instruction_date' => $input['instruction_date'][$i],
                    'instruction' => $input['instruction'][$i],
                ];
                $model = $this->model->create($ipdConsultantInstruction);
            }
        } catch (Exception $e) {
            // throw new UnprocessableEntityHttpException($e->getMessage());
            Notification::make()
                ->danger()
                ->title($e->getMessage())
                ->send();
        }

        return $model;
    }
}
