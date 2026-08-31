<?php

use Database\Seeders\PathologyPermissionModuleSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pathology_tests')) {
            Schema::table('pathology_tests', function (Blueprint $table) {
                if (! Schema::hasColumn('pathology_tests', 'payment_status')) {
                    $table->tinyInteger('payment_status')->default(0)->after('status');
                }
                if (! Schema::hasColumn('pathology_tests', 'payment_mode')) {
                    $table->tinyInteger('payment_mode')->nullable()->after('payment_status');
                }
                if (! Schema::hasColumn('pathology_tests', 'paid_amount')) {
                    $table->decimal('paid_amount', 10, 2)->nullable()->after('payment_mode');
                }
                if (! Schema::hasColumn('pathology_tests', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable()->after('paid_amount');
                }
                if (! Schema::hasColumn('pathology_tests', 'payment_note')) {
                    $table->text('payment_note')->nullable()->after('paid_at');
                }
            });
        }

        (new PathologyPermissionModuleSeeder)->run();
    }

    public function down(): void
    {
        if (Schema::hasTable('pathology_tests')) {
            Schema::table('pathology_tests', function (Blueprint $table) {
                foreach (['payment_note', 'paid_at', 'paid_amount', 'payment_mode', 'payment_status'] as $column) {
                    if (Schema::hasColumn('pathology_tests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
