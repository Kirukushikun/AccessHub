<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connection_codes', function (Blueprint $table) {
            // Which environment this code was minted for — nullable at the DB
            // level only so this migration never fails against old rows; codes
            // live 15 minutes, so any pre-existing row is expired and irrelevant
            // by the time this ships. Every code created from here on sets it.
            $table->string('environment')->nullable()->after('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('connection_codes', function (Blueprint $table) {
            $table->dropColumn('environment');
        });
    }
};
