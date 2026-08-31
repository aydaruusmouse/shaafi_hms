<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('module_role')) {
            return;
        }

        Schema::create('module_role', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('role_id');
            $table->boolean('can_access')->default(true);
            $table->tinyInteger('can_create')->default(0);
            $table->tinyInteger('can_edit')->default(0);
            $table->tinyInteger('can_delete')->default(0);
            $table->timestamps();

            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('departments')->onDelete('cascade');
            $table->unique(['module_id', 'role_id']);
            $table->index(['role_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_role');
    }
};
