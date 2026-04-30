<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iis_alkes_list', function (Blueprint $table) {
            $table->dropForeign(['distributor_id']);
            $table->dropColumn('distributor_id');
            $table->unsignedBigInteger('supplier_id')->nullable()->after('warehouse_id');
            $table->foreign('supplier_id')
                  ->references('id')
                  ->on('suppliers')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('iis_alkes_list', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
            $table->unsignedBigInteger('distributor_id')->nullable()->after('warehouse_id');
            $table->foreign('distributor_id')
                  ->references('id')
                  ->on('distributors')
                  ->nullOnDelete();
        });
    }
};