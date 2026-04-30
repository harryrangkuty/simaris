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
        Schema::create('menu_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('application_menu_id');
            $table->unsignedBigInteger('permission_id');
            $table->primary(['application_menu_id', 'permission_id']);
            $table->foreign('application_menu_id')->references('id')->on('application_menus')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_has_permissions');
    }
};
