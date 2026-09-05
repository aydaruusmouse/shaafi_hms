<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doctor_daily_tickets')) {
            return;
        }

        Schema::create('doctor_daily_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->date('ticket_date');
            $table->unsignedInteger('ticket_number');
            $table->string('status')->default('available');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->dateTime('reserved_until')->nullable();
            $table->string('tenant_id')->nullable();
            $table->timestamps();

            $table->unique(['doctor_id', 'ticket_date', 'ticket_number', 'tenant_id'], 'doctor_daily_tickets_unique');
            $table->index(['doctor_id', 'ticket_date', 'status']);
        });

        $this->backfillFromAppointments();
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_daily_tickets');
    }

    private function backfillFromAppointments(): void
    {
        if (! Schema::hasTable('appointments') || ! Schema::hasColumn('appointments', 'ticket_number')) {
            return;
        }

        $appointments = DB::table('appointments')
            ->whereNotNull('ticket_number')
            ->whereNotNull('doctor_id')
            ->whereNotNull('opd_date')
            ->get();

        foreach ($appointments as $appointment) {
            $date = date('Y-m-d', strtotime($appointment->opd_date));
            $doctor = DB::table('doctors')->where('id', $appointment->doctor_id)->first();
            $capacity = max((int) ($doctor->ticket_count ?? 20), (int) $appointment->ticket_number);

            for ($ticket = 1; $ticket <= $capacity; $ticket++) {
                $exists = DB::table('doctor_daily_tickets')
                    ->where('doctor_id', $appointment->doctor_id)
                    ->where('ticket_date', $date)
                    ->where('ticket_number', $ticket)
                    ->where('tenant_id', $appointment->tenant_id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $status = 'available';
                $patientId = null;
                $appointmentId = null;

                if ((int) $ticket === (int) $appointment->ticket_number) {
                    $status = (int) $appointment->is_completed === 3 ? 'cancelled' : 'taken';
                    $patientId = $appointment->patient_id;
                    $appointmentId = $appointment->id;
                }

                DB::table('doctor_daily_tickets')->insert([
                    'doctor_id' => $appointment->doctor_id,
                    'ticket_date' => $date,
                    'ticket_number' => $ticket,
                    'status' => $status,
                    'patient_id' => $patientId,
                    'appointment_id' => $appointmentId,
                    'tenant_id' => $appointment->tenant_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
