<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opd_patient_departments', function (Blueprint $table) {
            if (! Schema::hasColumn('opd_patient_departments', 'is_discharge')) {
                $table->boolean('is_discharge')->default(false)->after('is_old_patient');
            }
            if (! Schema::hasColumn('opd_patient_departments', 'discharge_date')) {
                $table->dateTime('discharge_date')->nullable()->after('is_discharge');
            }
            if (! Schema::hasColumn('opd_patient_departments', 'discharge_summary')) {
                $table->text('discharge_summary')->nullable()->after('discharge_date');
            }
        });

        Schema::table('ipd_patient_departments', function (Blueprint $table) {
            if (! Schema::hasColumn('ipd_patient_departments', 'admission_id')) {
                $table->string('admission_id')->nullable()->after('ipd_number');
            }
            if (! Schema::hasColumn('ipd_patient_departments', 'discharge_date')) {
                $table->dateTime('discharge_date')->nullable()->after('is_discharge');
            }
            if (! Schema::hasColumn('ipd_patient_departments', 'discharge_summary')) {
                $table->text('discharge_summary')->nullable()->after('discharge_date');
            }
        });

        if (Schema::hasColumn('ipd_patient_departments', 'admission_id')) {
            $ipds = DB::table('ipd_patient_departments')->whereNull('admission_id')->orWhere('admission_id', '')->pluck('id');
            foreach ($ipds as $id) {
                do {
                    $admissionId = 'ADM'.strtoupper(substr(str_replace('-', '', (string) \Illuminate\Support\Str::uuid()), 0, 8));
                } while (DB::table('ipd_patient_departments')->where('admission_id', $admissionId)->exists());

                DB::table('ipd_patient_departments')->where('id', $id)->update(['admission_id' => $admissionId]);
            }
        }

        Schema::table('radiology_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('radiology_tests', 'doctor_id')) {
                $table->unsignedBigInteger('doctor_id')->nullable()->after('patient_id');
            }
            if (! Schema::hasColumn('radiology_tests', 'status')) {
                $table->tinyInteger('status')->default(0)->after('doctor_id');
            }
            if (! Schema::hasColumn('radiology_tests', 'uploaded_at')) {
                $table->timestamp('uploaded_at')->nullable()->after('status');
            }
        });

        Schema::table('pathology_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('pathology_tests', 'doctor_id')) {
                $table->unsignedBigInteger('doctor_id')->nullable()->after('patient_id');
            }
            if (! Schema::hasColumn('pathology_tests', 'status')) {
                $table->tinyInteger('status')->default(0)->after('doctor_id');
            }
        });

        if (! Schema::hasTable('charge_types')) {
            Schema::create('charge_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('tenant_id')->nullable();
                $table->timestamps();
            });

            $now = now();
            $defaults = [
                1 => 'Investigations',
                2 => 'Operation Theatre',
                3 => 'Others',
                4 => 'Procedures',
                5 => 'Supplier',
            ];
            foreach ($defaults as $id => $name) {
                DB::table('charge_types')->insert([
                    'id' => $id,
                    'name' => $name,
                    'tenant_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! Schema::hasTable('ipd_operations')) {
            Schema::create('ipd_operations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ipd_patient_department_id');
                $table->string('operation_name')->nullable();
                $table->dateTime('operation_date')->nullable();
                $table->unsignedBigInteger('surgeon_id')->nullable();
                $table->unsignedBigInteger('assistant_id')->nullable();
                $table->unsignedBigInteger('anesthetist_id')->nullable();
                $table->text('notes')->nullable();
                $table->string('tenant_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ipd_nurse_notes')) {
            Schema::create('ipd_nurse_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ipd_patient_department_id');
                $table->unsignedBigInteger('nurse_id')->nullable();
                $table->dateTime('note_date')->nullable();
                $table->text('note')->nullable();
                $table->string('tenant_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_nurse_notes');
        Schema::dropIfExists('ipd_operations');
        Schema::dropIfExists('charge_types');
    }
};
