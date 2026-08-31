<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vital_signs')) {
            Schema::create('vital_signs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('appointment_id')->nullable();
                $table->unsignedBigInteger('case_id')->nullable();
                $table->unsignedBigInteger('patient_id')->nullable();
                $table->string('ipd_opd_number')->nullable();
                $table->string('type')->nullable();
                $table->string('blood_pressure')->nullable();
                $table->integer('pulse_rate')->nullable();
                $table->integer('respiratory_rate')->nullable();
                $table->integer('oxygen_saturation')->nullable();
                $table->decimal('temperature', 5, 2)->nullable();
                $table->integer('random_blood_sugar')->nullable();
                $table->integer('fasting_blood_sugar')->nullable();
                $table->text('drug_allergies')->nullable();
                $table->string('tenant_id')->nullable();
                $table->timestamps();
            });
        }

        $parents = DB::table('modules')->where('name', 'Appointments')->get();
        foreach ($parents as $parent) {
            $exists = DB::table('modules')
                ->where('name', 'Vital Signs')
                ->where('tenant_id', $parent->tenant_id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('modules')->insert([
                'name' => 'Vital Signs',
                'is_active' => 1,
                'is_hidden' => 0,
                'route' => 'admin.vital_signs.index',
                'parent_id' => $parent->id,
                'tenant_id' => $parent->tenant_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vital_signs');
        DB::table('modules')->where('name', 'Vital Signs')->delete();
    }
};
