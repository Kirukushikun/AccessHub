<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every hub login attempt, success or fail.
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->boolean('successful');
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
