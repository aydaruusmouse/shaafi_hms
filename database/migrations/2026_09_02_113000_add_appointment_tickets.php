<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doctors') && ! Schema::hasColumn('doctors', 'ticket_count')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->unsignedInteger('ticket_count')->default(20)->after('appointment_charge');
            });
        }

        if (Schema::hasTable('appointments') && ! Schema::hasColumn('appointments', 'ticket_number')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->unsignedInteger('ticket_number')->nullable()->after('opd_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('doctors') && Schema::hasColumn('doctors', 'ticket_count')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->dropColumn('ticket_count');
            });
        }

        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'ticket_number')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn('ticket_number');
            });
        }
    }
};
