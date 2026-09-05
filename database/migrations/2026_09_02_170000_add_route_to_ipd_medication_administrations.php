<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ipd_medication_administrations')) {
            return;
        }

        if (! Schema::hasColumn('ipd_medication_administrations', 'route')) {
            Schema::table('ipd_medication_administrations', function (Blueprint $table) {
                $table->string('route')->nullable()->after('dosage');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ipd_medication_administrations') && Schema::hasColumn('ipd_medication_administrations', 'route')) {
            Schema::table('ipd_medication_administrations', function (Blueprint $table) {
                $table->dropColumn('route');
            });
        }
    }
};
