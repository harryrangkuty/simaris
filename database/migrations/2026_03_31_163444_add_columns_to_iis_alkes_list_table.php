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
        Schema::table('iis_alkes_list', function (Blueprint $table) {
            $table->unsignedBigInteger('distributor_id')->nullable()->after('warehouse_id');
            $table->string('alkes_number')->nullable()->after('item_no');

            // kalau mau foreign key
            $table->foreign('distributor_id')
                ->references('id')
                ->on('distributors')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iis_alkes_list', function (Blueprint $table) {
            $table->dropForeign(['distributor_id']);
            $table->dropColumn(['distributor_id', 'alkes_number']);
        });
    }
};
