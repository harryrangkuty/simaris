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
        // REQUESTS PROCUREMENTS
        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->date('request_date');
            $table->string('code', 20)->unique();
            $table->string('title');
            // siapa yang menerima request (atasan langsung)
            $table->unsignedInteger('supervisor_id');
            $table->string('supervisor_position');
            $table->enum('status', ['draft', 'submitted', 'verified', 'approved', 'rejected'])
                ->default('draft');
            // verified
            $table->unsignedInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            // approved
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            // rejected
            $table->unsignedInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejected_note')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });


        // PROCUREMENTS
        Schema::create('procurements', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->unsignedInteger('supplier_id')->nullable();
            $table->unsignedInteger('procurement_request_id')->nullable();
            $table->string('transaction_type_code');
            $table->string('funding_source')->nullable();
            $table->date('letter_date')->nullable();
            $table->string('letter_number')->nullable();

            $table->enum('status', [
                'draft',       // dibuat oleh user
                'submitted',   // diajukan untuk persetujuan
                'approved',    // disahkan oleh keuangan
                'po_created',  // dibuat PO jika jenis transaksi pembelian barang
                'received',    // barang diterima gudang
                'registered',  // diregistrasi oleh bagian hutang
                'cancelled',   // dibatalkan
                'rejected'     // ditolak oleh keuangan
            ])->default('draft');

            $table->unsignedInteger('purchasing_officer_id');
            $table->text('notes')->nullable();

            $table->date('estimated_arrival_date')->nullable();     // estimasi barang sampai
            $table->date('book_date')->nullable();                  // tanggal buku akuntansi
            $table->date('po_date')->nullable();                    // tanggal PO
            $table->timestamp('approved_at')->nullable();           // tanggal admin keuangan menyetujui
            $table->timestamp('received_at')->nullable();           // tanggal barang sampai
            $table->date('registered_at')->nullable();
            $table->json('etc')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('delete_message')->nullable();
        });

        Schema::create('procurement_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('procurement_id');
            $table->string('description');
            $table->string('item_code', 10);
            $table->tinyInteger('item_type')
                ->default(1)
                ->comment('1 = Baru, 2 = Bekas, 3 = Stock Opname');
            $table->tinyInteger('physical_condition')
                ->default(1)
                ->comment('1 = Baik, 2 = Rusak Ringan, 3 = Rusak Berat');
            $table->unsignedInteger('quantity');
            $table->double('unit_price');                   // harga per unit
            $table->double('discount_value')->default(0);   // nilai potongan per unit
            $table->double('commission_value')->default(0); // komisi per unit (kalau ada)
            $table->double('shipping_value')->default(0);   // ongkir per item (kalau dibagi rata)
            $table->double('ppn_value')->default(0);        // pajak per unit (persentase)
            $table->double('sub_total')->default(0);         // total semuanya
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_details');
        Schema::dropIfExists('procurements');
        Schema::dropIfExists('procurement_requests');
    }
};
