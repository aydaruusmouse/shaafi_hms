<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ipd_medication_administrations')) {
            return;
        }

        Schema::create('ipd_medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ipd_patient_department_id');
            $table->unsignedBigInteger('ipd_prescription_item_id')->nullable();
            $table->unsignedBigInteger('medicine_id')->nullable();
            $table->string('medicine_name')->nullable();
            $table->string('dosage')->nullable();
            $table->dateTime('given_at')->nullable();
            $table->unsignedBigInteger('nurse_id')->nullable();
            $table->string('status')->default('given');
            $table->text('notes')->nullable();
            $table->string('tenant_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_medication_administrations');
    }
};
