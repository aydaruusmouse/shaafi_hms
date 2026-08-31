<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consultation_pathology_tests')) {
            return;
        }

        Schema::table('consultation_pathology_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('consultation_pathology_tests', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_note');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('consultation_pathology_tests')) {
            return;
        }

        Schema::table('consultation_pathology_tests', function (Blueprint $table) {
            if (Schema::hasColumn('consultation_pathology_tests', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }
        });
    }
};
