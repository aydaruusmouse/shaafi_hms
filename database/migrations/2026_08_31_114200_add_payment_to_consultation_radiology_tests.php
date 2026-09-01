<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('consultation_radiology_tests')) {
            Schema::table('consultation_radiology_tests', function (Blueprint $table) {
                if (! Schema::hasColumn('consultation_radiology_tests', 'radiology_test_id')) {
                    $table->unsignedBigInteger('radiology_test_id')->nullable()->after('notes');
                }
                if (! Schema::hasColumn('consultation_radiology_tests', 'processed_at')) {
                    $table->timestamp('processed_at')->nullable()->after('radiology_test_id');
                }
                if (! Schema::hasColumn('consultation_radiology_tests', 'payment_status')) {
                    $table->tinyInteger('payment_status')->default(0)->after('processed_at');
                }
                if (! Schema::hasColumn('consultation_radiology_tests', 'payment_mode')) {
                    $table->tinyInteger('payment_mode')->nullable()->after('payment_status');
                }
                if (! Schema::hasColumn('consultation_radiology_tests', 'paid_amount')) {
                    $table->decimal('paid_amount', 10, 2)->nullable()->after('payment_mode');
                }
                if (! Schema::hasColumn('consultation_radiology_tests', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable()->after('paid_amount');
                }
                if (! Schema::hasColumn('consultation_radiology_tests', 'payment_note')) {
                    $table->text('payment_note')->nullable()->after('paid_at');
                }
                if (! Schema::hasColumn('consultation_radiology_tests', 'payment_reference')) {
                    $table->string('payment_reference')->nullable()->after('payment_note');
                }
            });
        }

        if (Schema::hasTable('radiology_tests')) {
            Schema::table('radiology_tests', function (Blueprint $table) {
                if (! Schema::hasColumn('radiology_tests', 'payment_status')) {
                    $table->tinyInteger('payment_status')->default(0)->after('status');
                }
                if (! Schema::hasColumn('radiology_tests', 'payment_mode')) {
                    $table->tinyInteger('payment_mode')->nullable()->after('payment_status');
                }
                if (! Schema::hasColumn('radiology_tests', 'paid_amount')) {
                    $table->decimal('paid_amount', 10, 2)->nullable()->after('payment_mode');
                }
                if (! Schema::hasColumn('radiology_tests', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable()->after('paid_amount');
                }
                if (! Schema::hasColumn('radiology_tests', 'payment_note')) {
                    $table->text('payment_note')->nullable()->after('paid_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('consultation_radiology_tests')) {
            Schema::table('consultation_radiology_tests', function (Blueprint $table) {
                foreach (['payment_reference', 'payment_note', 'paid_at', 'paid_amount', 'payment_mode', 'payment_status', 'processed_at', 'radiology_test_id'] as $column) {
                    if (Schema::hasColumn('consultation_radiology_tests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('radiology_tests')) {
            Schema::table('radiology_tests', function (Blueprint $table) {
                foreach (['payment_note', 'paid_at', 'paid_amount', 'payment_mode', 'payment_status'] as $column) {
                    if (Schema::hasColumn('radiology_tests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
