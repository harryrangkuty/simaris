<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('identifier');
            $table->string('name');
            $table->string('email')->nullable()->unique();

            // RS Bunda Thamrin Based
            $table->string('division')->nullable();   // e.g., "IT"
            $table->string('department')->nullable(); // e.g., "Administrasi dan Umum"
            $table->string('position')->nullable();   // e.g., "Ka.Sub.Bid"

            $table->string('photo')->nullable();
            $table->string('password')->nullable();
            $table->unsignedInteger('active_role_id')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
