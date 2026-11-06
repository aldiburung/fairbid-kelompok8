<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction', function (Blueprint $table) {
            $table->id('auction_id');

            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('category_id');
            $table->string('judul', 200);
            $table->text('deskripsi');
            $table->enum('model_transaksi', ['C2C', 'C2B', 'B2B', 'B2C'])->default('C2C');
            $table->decimal('harga_awal', 15, 2);
            $table->decimal('harga_limit', 15, 2)->nullable();
            $table->decimal('kelipatan_bid', 15, 2);
            $table->decimal('nilai_ujl', 15, 2);
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->integer('anti_sniping_window_menit')->default(2);
            $table->integer('anti_sniping_extend_menit')->default(2);
            $table->enum('status', [
                'DRAFT',
                'TERJADWAL',
                'AKTIF',
                'SELESAI',
                'TANPA_PEMENANG',
                'DIBATALKAN',
                'DITURUNKAN',
            ])->default('DRAFT');
            // Foreign key menyusul pada migration tabel bid.
            $table->unsignedBigInteger('winning_bid_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('seller_id')
                ->references('user_id')
                ->on('user');
            $table->foreign('category_id')
                ->references('category_id')
                ->on('category');
            $table->index('status');
            $table->index('end_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction');
    }
};
