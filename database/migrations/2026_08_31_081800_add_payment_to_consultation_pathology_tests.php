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
            if (! Schema::hasColumn('consultation_pathology_tests', 'payment_status')) {
                $table->tinyInteger('payment_status')->default(0)->after('processed_at');
            }
            if (! Schema::hasColumn('consultation_pathology_tests', 'payment_mode')) {
                $table->tinyInteger('payment_mode')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('consultation_pathology_tests', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->nullable()->after('payment_mode');
            }
            if (! Schema::hasColumn('consultation_pathology_tests', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('paid_amount');
            }
            if (! Schema::hasColumn('consultation_pathology_tests', 'payment_note')) {
                $table->text('payment_note')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('consultation_pathology_tests')) {
            return;
        }

        Schema::table('consultation_pathology_tests', function (Blueprint $table) {
            foreach (['payment_note', 'paid_at', 'paid_amount', 'payment_mode', 'payment_status'] as $column) {
                if (Schema::hasColumn('consultation_pathology_tests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
