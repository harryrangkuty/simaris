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
        // ASSET PROFILES
        Schema::create('asset_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('item_code', 50);
            $table->string('description');
            $table->unsignedInteger('procurement_id');
            $table->unsignedInteger('transaction_type_id');
            $table->unsignedInteger('registered_by');
            $table->dateTime('book_date');
            $table->unsignedBigInteger('asset_number'); //NUP
            $table->tinyInteger('condition');

            // Nilai keuangan
            $table->double('acquisition_cost')->default(0);
            $table->double('accumulated_depreciation')->default(0);
            $table->double('book_value')->default(0);

            // Distribusi Aset
            $table->string('room_type', 3)->nullable(); // DBR DBL
            $table->unsignedInteger('room_id')->nullable();
            $table->unsignedBigInteger('utilized_by')->nullable(); // user id
            $table->string('utilization_note')->nullable();        // keterangan lokasi/pemakai
            
            // Penghapusan aset
            $table->unsignedInteger('disposal_id')->nullable();
            $table->unsignedInteger('disposal_type_id')->nullable();
            $table->dateTime('disposal_date')->nullable();

            $table->boolean('is_deactivated')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        // ASSET TRANSACTIONS
        Schema::create('asset_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('profile_id');
            $table->unsignedInteger('created_by');
            $table->string('transaction_type_code');
            $table->string('transaction_type_name');
            $table->string('category_id', 10);
            $table->string('category_name');
            $table->unsignedBigInteger('nup');
            $table->tinyInteger('condition')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->double('value');
            $table->string('room_type', 3)->nullable();
            $table->string('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->unsignedInteger('reference_id');
            $table->string('reference_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_transactions');
        Schema::dropIfExists('asset_profiles');
    }
};
