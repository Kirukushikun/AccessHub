<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per project per environment. Enrollment lands here.
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('environment'); // local | staging | production
            $table->string('client_id')->unique();
            $table->string('client_secret_hash'); // hashed, never retrievable after issue
            $table->string('domain')->nullable();  // optional expected-domain bind
            $table->timestamp('last_seen_at')->nullable(); // stamped on every successful sync
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'environment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
