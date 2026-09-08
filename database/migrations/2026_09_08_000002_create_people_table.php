<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique(); // numeric id from the org directory — the join key every project uses
            $table->string('name');
            $table->string('email');
            $table->string('farm')->nullable();       // display only — never affects access
            $table->string('department')->nullable(); // display only
            $table->string('position')->nullable();   // job title, display only
            $table->json('roles');                    // one or more of: requestor | division_head | vp | user
            $table->string('scope')->default('all');  // all | selected
            $table->boolean('active')->default(true); // false on departure, keeps history
            $table->timestamps();

            $table->index(['farm', 'department']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
