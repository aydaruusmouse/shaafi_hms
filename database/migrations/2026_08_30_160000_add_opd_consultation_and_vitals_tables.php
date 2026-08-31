<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vital_signs', function (Blueprint $table) {
            if (! Schema::hasColumn('vital_signs', 'height')) {
                $table->decimal('height', 8, 2)->nullable()->after('temperature');
            }
            if (! Schema::hasColumn('vital_signs', 'weight')) {
                $table->decimal('weight', 8, 2)->nullable()->after('height');
            }
        });

        if (! Schema::hasTable('consultation_medical_informations')) {
            Schema::create('consultation_medical_informations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_id');
                $table->string('caseable_type');
                $table->unsignedBigInteger('caseable_id');
                $table->string('tenant_id')->nullable();
                $table->text('chief_complain')->nullable();
                $table->text('past_medical_surgical_history')->nullable();
                $table->text('family_social_history')->nullable();
                $table->text('drug_history_allergy')->nullable();
                $table->text('chronic_diseases_history')->nullable();
                $table->text('obstetric_gynecology_history')->nullable();
                $table->text('physical_examination')->nullable();
                $table->text('differential_diagnosis')->nullable();
                $table->text('professional_diagnosis')->nullable();
                $table->timestamps();

                $table->index(['caseable_type', 'caseable_id'], 'cmi_caseable_index');
                $table->index(['patient_id'], 'cmi_patient_index');
            });
        }

        if (! Schema::hasTable('consultation_pathology_tests')) {
            Schema::create('consultation_pathology_tests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_id');
                $table->unsignedBigInteger('pathology_category_id')->nullable();
                $table->unsignedBigInteger('pathology_parameter_id')->nullable();
                $table->unsignedBigInteger('pathology_test_id')->nullable();
                $table->string('caseable_type');
                $table->unsignedBigInteger('caseable_id');
                $table->string('tenant_id')->nullable();
                $table->text('test_name')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['patient_id']);
                $table->index(['caseable_id', 'caseable_type'], 'cpt_caseable_index');
            });
        }

        if (! Schema::hasTable('consultation_radiology_tests')) {
            Schema::create('consultation_radiology_tests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_id');
                $table->unsignedBigInteger('radiology_category_id')->nullable();
                $table->string('caseable_type');
                $table->unsignedBigInteger('caseable_id');
                $table->string('tenant_id')->nullable();
                $table->text('test_name')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['patient_id']);
                $table->index(['caseable_id', 'caseable_type'], 'crt_caseable_index');
            });
        }

        Schema::table('pathology_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('pathology_tests', 'consultation_pathology_test_id')) {
                $table->unsignedBigInteger('consultation_pathology_test_id')->nullable()->after('patient_id');
            }
        });

        Schema::table('radiology_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('radiology_tests', 'consultation_radiology_test_id')) {
                $table->unsignedBigInteger('consultation_radiology_test_id')->nullable()->after('patient_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_radiology_tests');
        Schema::dropIfExists('consultation_pathology_tests');
        Schema::dropIfExists('consultation_medical_informations');
    }
};
