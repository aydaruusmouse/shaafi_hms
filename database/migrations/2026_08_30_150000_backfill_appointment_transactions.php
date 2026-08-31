<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $appointments = DB::table('appointments')
            ->whereNotIn('id', function ($query) {
                $query->select('appointment_id')->from('appointment_transactions');
            })
            ->get();

        $now = now();

        foreach ($appointments as $appointment) {
            $type = (int) ($appointment->payment_type ?: 4);
            $prefix = match ($type) {
                6 => 'CHQ',
                4 => 'CASH',
                default => 'APT',
            };

            DB::table('appointment_transactions')->insert([
                'appointment_id' => $appointment->id,
                'transaction_type' => $type,
                'transaction_id' => $prefix.'-'.$appointment->id.'-'.strtoupper(Str::random(8)),
                'tenant_id' => $appointment->tenant_id,
                'created_at' => $appointment->created_at ?? $now,
                'updated_at' => $appointment->updated_at ?? $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('appointment_transactions')
            ->where('transaction_id', 'like', 'CASH-%')
            ->orWhere('transaction_id', 'like', 'CHQ-%')
            ->orWhere('transaction_id', 'like', 'APT-%')
            ->delete();
    }
};
