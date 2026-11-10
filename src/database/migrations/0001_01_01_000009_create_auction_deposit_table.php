<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_deposit', function (Blueprint $table) {
            $table->id('deposit_id');
            $table->unsignedBigInteger('auction_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('nominal_ujl', 15, 2);
            $table->enum('status', ['MENUNGGU', 'DITAHAN', 'DIKEMBALIKAN', 'DIPOTONG', 'GAGAL'])
                ->default('MENUNGGU');
            $table->timestamp('terms_accepted_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('settled_at')->nullable();

            $table->foreign('auction_id')
                ->references('auction_id')
                ->on('auction')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('user_id')
                ->on('user');
            $table->unique(['auction_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_deposit');
    }
};
