<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append only. Every grant, role change, scope change, project registration,
        // code issue, enrollment, and connection revoke.
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type')->default('admin'); // admin | project | system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_label');                    // email or project key, denormalised for display
            $table->string('action')->index();                // e.g. person.role_changed
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('summary');
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
