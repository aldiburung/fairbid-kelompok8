<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->unsignedBigInteger('auction_id')->unique();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('buyer_id');
            $table->decimal('harga_akhir', 15, 2);
            $table->decimal('fee_persen', 5, 2)->default(3.00);
            $table->decimal('fee_amount', 15, 2);
            $table->decimal('net_seller', 15, 2);
            $table->enum('status', [
                'MENUNGGU_PELUNASAN',
                'ESCROW',
                'SELESAI',
                'BATAL',
                'SENGKETA',
                'DIKEMBALIKAN',
            ])->default('MENUNGGU_PELUNASAN');
            $table->timestamp('batas_pelunasan');
            $table->timestamp('batas_konfirmasi')->nullable();
            $table->timestamp('buyer_confirmed_at')->nullable();
            $table->timestamp('seller_confirmed_at')->nullable();
            $table->timestamp('released_at')->nullable();

            $table->foreign('auction_id')->references('auction_id')->on('auction');
            $table->foreign('seller_id')->references('user_id')->on('user');
            $table->foreign('buyer_id')->references('user_id')->on('user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction');
    }
};
