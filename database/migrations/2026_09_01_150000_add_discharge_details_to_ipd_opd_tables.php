<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ipd_patient_departments') && ! Schema::hasColumn('ipd_patient_departments', 'discharge_details')) {
            Schema::table('ipd_patient_departments', function (Blueprint $table) {
                $table->json('discharge_details')->nullable()->after('discharge_summary');
            });
        }

        if (Schema::hasTable('opd_patient_departments') && ! Schema::hasColumn('opd_patient_departments', 'discharge_details')) {
            Schema::table('opd_patient_departments', function (Blueprint $table) {
                $table->json('discharge_details')->nullable()->after('discharge_summary');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ipd_patient_departments') && Schema::hasColumn('ipd_patient_departments', 'discharge_details')) {
            Schema::table('ipd_patient_departments', function (Blueprint $table) {
                $table->dropColumn('discharge_details');
            });
        }

        if (Schema::hasTable('opd_patient_departments') && Schema::hasColumn('opd_patient_departments', 'discharge_details')) {
            Schema::table('opd_patient_departments', function (Blueprint $table) {
                $table->dropColumn('discharge_details');
            });
        }
    }
};
