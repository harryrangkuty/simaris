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
        // DAFTAR ITEM IIS
        Schema::create('iis_items_list', function (Blueprint $table) {
            $table->string('item_no')->primary();
            $table->string('name')->nullable();
            $table->decimal('buying_price', 15, 2)->default(0); // harga beli terakhir
            $table->boolean('is_sell')->default(0);
            $table->boolean('is_buy')->default(0);
            $table->boolean('stock')->nullable();
            $table->integer('onhand')->default(0);
            $table->decimal('hpp', 15, 2)->default(0);          // harga pokok persediaan
            $table->decimal('asset', 15, 2)->default(0);        // nilai aset
        });

        // DAFTAR KATEGORI IIS
        Schema::create('iis_categories_list', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
        });

        // DAFTAR INVENTARIS IIS
        Schema::create('iis_inventories_list', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->comment('Cabang tempat barang berada');
            $table->string('qr_code_no')->unique();
            $table->string('item_code', 11)->nullable();
            $table->string('description')->nullable();
            $table->string('category_name')->nullable();
            $table->unsignedInteger('building_id')->nullable();
            $table->string('floor')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('unit_legacy')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('room_legacy')->nullable();
            $table->string('pj_nik', 30)->nullable();
            $table->string('condition')->nullable();
            $table->unsignedBigInteger('asset_number')->nullable(); // NUP
            $table->string('iis_operator')->nullable();

            // Procurement / Perolehan
            $table->string('po_number')->nullable();
            $table->string('received_number')->nullable();

            // Penghapusan aset
            $table->unsignedInteger('disposal_id')->nullable();
            $table->unsignedInteger('disposal_type_id')->nullable();
            $table->dateTime('disposal_date')->nullable();

            $table->decimal('unit_price', 15, 2)->nullable();
            $table->year('purchase_year')->nullable();

            $table->boolean('is_deactivated')->default(0);
            $table->string('is_deactivated_notes')->nullable();

            $table->boolean('is_handed_over')->default(0);

            $table->string('notes')->nullable();

            $table->unsignedBigInteger('warehouse_id')->nullable()->comment('Gudang tempat barang berada');

            // JSON FIELD
            $table->json('etc')->nullable();

            // PRINT CONTROL
            $table->unsignedTinyInteger('print_count')->default(0);
            $table->timestamp('last_print_at')->nullable();
            $table->unsignedBigInteger('last_print_by')->nullable();

            $table->enum('data_source', ['legacy_iis', 'system'])
                ->default('system');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });

        // DAFTAR ALKES IIS
        Schema::create('iis_alkes_list', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->comment('Cabang tempat barang berada');
            $table->string('qr_code_no')->unique();
            $table->string('item_no', 11)->nullable();
            $table->string('item_no_legacy')->nullable();
            $table->string('description')->nullable();
            $table->string('category_name')->nullable();
            $table->string('position_legacy')->nullable();

            $table->unsignedInteger('building_id')->nullable();
            $table->string('floor')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('pj_nik', 30)->nullable();
            $table->string('condition')->nullable();

            // Procurement / Perolehan
            $table->string('po_number')->nullable();
            $table->string('received_number')->nullable();

            // Penghapusan aset
            $table->unsignedInteger('disposal_id')->nullable();
            $table->unsignedInteger('disposal_type_id')->nullable();
            $table->dateTime('disposal_date')->nullable();

            $table->decimal('unit_price', 15, 2)->nullable();
            $table->year('purchase_year')->nullable();

            $table->boolean('is_deactivated')->default(0);
            $table->string('is_deactivated_notes')->nullable();

            $table->string('notes')->nullable();

            $table->unsignedBigInteger('warehouse_id')->nullable()->comment('Gudang tempat barang berada');

            $table->boolean('is_handed_over')->default(0);

            // JSON FIELD
            $table->json('etc')->nullable();

            // PRINT CONTROL
            $table->unsignedTinyInteger('print_count')->default(0);
            $table->timestamp('last_print_at')->nullable();
            $table->unsignedBigInteger('last_print_by')->nullable();

            $table->enum('data_source', ['legacy_iis', 'system'])
                ->default('system');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });

        // DAFTAR PEMELIHARAAN IIS
        Schema::create('iis_maintenance', function (Blueprint $table) {
            $table->id();
            $table->string('qr_code_no');
            $table->string('object_type'); // inventory | alkes
            $table->unsignedBigInteger('operator_id')->comment('User teknisi / operator yang menangani maintenance');

            // Jenis pemeliharaan
            $table->enum('maintenance_type', ['internal', 'external']);

            // Status pemeliharaan
            $table->enum('status', [
                'draft',       // baru dibuat, belum disubmit
                'submitted',   // sudah disubmit untuk approval/validasi
                'approved',    // maintenance tervalidasi / selesai resmi
                'rejected',    // ditolak / invalid
                'in_progress',  // maintenance sedang berjalan
            ])->default('draft');

            // Informasi servis
            $table->string('service_code')->nullable();
            $table->string('supplier_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('cost', 15, 2)->nullable();

            // Tanggal servis
            $table->dateTime('maintenance_date');            // tanggal masuk servis
            $table->dateTime('estimated_completed_date')->nullable()->comment('Perkiraan tanggal selesai maintenance');
            $table->dateTime('completed_date')->nullable();  // tanggal selesai servis
            // File pendukung (nota / foto / pdf)
            $table->json('attachments')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['qr_code_no', 'object_type']);
        });

        // DAFTAR SERAH TERIMA IIS
        Schema::create('iis_qr_code_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // nomor berita acara
            // PJ BARANG (tetap)
            $table->unsignedBigInteger('pj_id');
            // PETUGAS QRCODE
            $table->unsignedBigInteger('operator_id');
            $table->enum('asset_type', ['inventory', 'alkes']);
            // STATUS VERIFIKASI
            $table->enum('status', [
                'draft',
                'submitted',
                'approved',
                'verified',   // qrcode ulang selesai
                'rejected',
            ])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();

            // rejection
            $table->text('rejection_note')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // DAFTAR ITEM SERAH TERIMA IIS
        Schema::create('iis_qr_code_handover_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qr_code_handover_id');
            $table->unsignedBigInteger('asset_id');
            // snapshot untuk surat
            $table->string('qr_code_no')->nullable();
            $table->string('description')->nullable();
            $table->string('condition')->nullable();
            $table->timestamps();
            $table->unique(
                ['qr_code_handover_id', 'asset_id'],
                'uq_qr_code_handover_asset'
            );
        });

        // DAFTAR APPROVER SERAH TERIMA IIS
        Schema::create('iis_qr_code_handover_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('handover_id')
                ->constrained('iis_qr_code_handovers')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('position'); // kabid / kasub / direktur dll
            $table->integer('approval_order')->default(1);

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('note')->nullable();

            $table->unique(['handover_id', 'user_id']);

            $table->timestamps();
        });

        // DAFTAR PERPINDAHAN BARANG IIS
        Schema::create('iis_movements', function (Blueprint $table) {
            $table->id();

            // IDENTITAS
            $table->string('code')->unique();
            $table->enum('asset_type', ['inventory', 'alkes']);
            $table->enum('movement_type', ['distribution', 'mutation', 'return', 'borrow', 'borrow_return']);

            // PJ Awal
            $table->unsignedBigInteger('from_pj_id')->nullable();
            // PJ Tujuan
            $table->unsignedBigInteger('to_pj_id')->nullable();

            // META TRANSAKSI
            $table->unsignedBigInteger('operator_id');
            $table->dateTime('movement_date');
            $table->text('notes')->nullable();

            /**
             * WORKFLOW STATUS
             * draft     : disiapkan
             * submitted : diajukan
             * approved  : disetujui atasan
             * verified  : diverifikasi penerima (update inventory DI SINI)
             * rejected  : ditolak
             */
            $table->enum('status', [
                'draft',
                'submitted',
                'approved',
                'verified',
                'rejected',
            ])->default('draft');

            // WORKFLOW META
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_note')->nullable();

            $table->timestamps();

            // KHUSUS PEMINJAMAN
            $table->unsignedBigInteger('parent_movement_id')->nullable();
            $table->date('borrowed_at')->nullable();   // tanggal mulai pinjam
            $table->date('borrowed_due_at')->nullable(); // jatuh tempo pengembalian

            // OPTIONAL INDEX
            $table->index(['movement_type', 'status']);
            $table->index(['asset_type']);
        });

        // DAFTAR ITEM BARANG DALAM PERPINDAHAN BARANG IIS
        Schema::create('iis_movement_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('movement_id');
            $table->unsignedBigInteger('asset_id');

            // SNAPSHOT ITEM (opsional tapi bagus buat histori)
            $table->string('qr_code_no')->nullable();
            $table->string('description')->nullable();
            $table->string('condition')->nullable();

            // ASAL
            $table->enum('from_type', ['warehouse', 'unit', 'user']);
            $table->json('from_location'); // SNAPSHOT ASAL

            // TUJUAN
            $table->enum('to_type', ['warehouse', 'unit', 'user'])->nullable();
            $table->json('to_location'); // SNAPSHOT TUJUAN

            $table->timestamps();

            $table->unique(['movement_id', 'asset_id']);

            // Optional FK
            $table->foreign('movement_id')->references('id')->on('iis_movements')->cascadeOnDelete();
        });

        // DAFTAR APPROVER PERPINDAHAN BARANG IIS
        Schema::create('iis_movement_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('movement_id')
                ->constrained('iis_movements')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('position'); // kabid / kasub / direktur dll
            $table->integer('approval_order')->default(1);

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['movement_id', 'user_id']);
        });

        // DAFTAR PEMAKAIAN BARANG IIS
        Schema::create('iis_usages', function (Blueprint $table) {
            $table->id();

            // IDENTITAS
            $table->string('code')->unique();
            $table->enum('asset_type', ['inventory', 'alkes']);

            // JENIS PEMAKAIAN
            $table->enum('usage_type', [
                'operational',   // pemakaian biasa
                'temporary',     // sementara / durasi
                'consumable',     // habis pakai (stok)
            ]);

            // LOKASI
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();

            // DIGUNAKAN OLEH
            $table->enum('used_by_type', ['user', 'unit', 'external'])->nullable();
            $table->unsignedBigInteger('used_by_id')->nullable();

            // PENANGGUNG JAWAB & OPERATOR
            $table->unsignedBigInteger('pj_id')->nullable();
            $table->unsignedBigInteger('operator_id');

            // INFORMASI PEMAKAIAN
            $table->dateTime('usage_date');
            $table->dateTime('usage_end_date')->nullable(); // untuk temporary
            $table->string('activity_name')->nullable();
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();

            // WORKFLOW STATUS
            $table->enum('status', [
                'draft',
                'submitted',
                'approved',
                'completed',
                'cancelled',
            ])->default('draft');

            // WORKFLOW META
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // AUDIT
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // INDEX
            $table->index(['asset_type', 'usage_type']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iis_usages');
        Schema::dropIfExists('iis_movement_approvals');
        Schema::dropIfExists('iis_movement_items');
        Schema::dropIfExists('iis_movements');
        Schema::dropIfExists('iis_qr_code_handover_approvals');
        Schema::dropIfExists('iis_qr_code_handover_items');
        Schema::dropIfExists('iis_qr_code_handovers');
        Schema::dropIfExists('iis_alkes_list');
        Schema::dropIfExists('iis_inventories_list');
        Schema::dropIfExists('iis_categories_list');
        Schema::dropIfExists('iis_items_list');
        Schema::dropIfExists('iis_maintenance');
    }
};
