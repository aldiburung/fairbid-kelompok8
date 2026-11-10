<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('deposit_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->enum('jenis', ['UJL', 'PELUNASAN', 'REFUND_UJL', 'POTONG_UJL', 'PENCAIRAN']);
            $table->decimal('amount', 15, 2);
            $table->string('gateway_ref', 100)->unique();
            $table->string('idempotency_key', 100)->unique();
            $table->enum('status', ['PENDING', 'BERHASIL', 'GAGAL'])->default('PENDING');
            $table->timestamp('webhook_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user');
            $table->foreign('deposit_id')
                ->references('deposit_id')
                ->on('auction_deposit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
