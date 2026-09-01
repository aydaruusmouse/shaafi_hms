<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('radiology_tests')) {
            return;
        }

        Schema::table('radiology_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('radiology_tests', 'result_status')) {
                $table->string('result_status')->nullable()->after('status');
            }
            if (! Schema::hasColumn('radiology_tests', 'result_document_name')) {
                $table->string('result_document_name')->nullable()->after('uploaded_at');
            }
            if (! Schema::hasColumn('radiology_tests', 'document_path')) {
                $table->string('document_path')->nullable()->after('result_document_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('radiology_tests')) {
            return;
        }

        Schema::table('radiology_tests', function (Blueprint $table) {
            foreach (['document_path', 'result_document_name', 'result_status'] as $column) {
                if (Schema::hasColumn('radiology_tests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
