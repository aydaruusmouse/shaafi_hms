<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('doctor_daily_tickets')
            ->where('status', 'reserved')
            ->update([
                'status' => 'available',
                'patient_id' => null,
                'appointment_id' => null,
                'reserved_until' => null,
            ]);
    }

    public function down(): void
    {
        //
    }
};
