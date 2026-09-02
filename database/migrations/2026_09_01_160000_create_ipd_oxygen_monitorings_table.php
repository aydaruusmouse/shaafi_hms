<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ipd_oxygen_monitorings')) {
            return;
        }

        Schema::create('ipd_oxygen_monitorings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ipd_patient_department_id');
            $table->dateTime('recorded_at')->nullable();
            $table->unsignedTinyInteger('spo2')->nullable();
            $table->string('delivery_device')->nullable();
            $table->decimal('flow_rate', 5, 2)->nullable();
            $table->unsignedTinyInteger('fio2')->nullable();
            $table->unsignedInteger('respiratory_rate')->nullable();
            $table->string('target_spo2')->nullable();
            $table->unsignedBigInteger('nurse_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('tenant_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_oxygen_monitorings');
    }
};
