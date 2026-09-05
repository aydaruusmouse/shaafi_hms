<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $appointments = DB::table('appointments')
            ->whereNull('tenant_id')
            ->get(['id', 'doctor_id', 'patient_id']);

        foreach ($appointments as $appointment) {
            $tenantId = DB::table('doctors')
                ->where('id', $appointment->doctor_id)
                ->value('tenant_id');

            if (! $tenantId) {
                $tenantId = DB::table('patients')
                    ->where('id', $appointment->patient_id)
                    ->value('tenant_id');
            }

            if ($tenantId) {
                DB::table('appointments')
                    ->where('id', $appointment->id)
                    ->update(['tenant_id' => $tenantId]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
