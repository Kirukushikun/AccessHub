<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only used when a person's scope is "selected".
        Schema::create('person_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            $table->unique(['person_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_project');
    }
};
