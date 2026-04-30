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
        Schema::create('application_menus', function (Blueprint $table) {
            $table->id();

            // TREE STRUCTURE
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('application_menus')
                ->cascadeOnDelete();

            $table->enum('type', ['header', 'menu'])->default('menu');

            // MENU IDENTIFIER
            $table->string('key')->nullable()->unique();
            $table->string('title');
            $table->string('icon')->nullable();
            $table->string('url')->nullable();

            // SPATIE PERMISSION (NAME)
            $table->string('permission')->nullable()
                ->comment('spatie permission name');

            // UI & STATUS
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_menus');
    }
};
