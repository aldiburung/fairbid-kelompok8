<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->id('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('auction_id')->nullable();
            $table->enum('tipe', [
                'TERSALIP',
                'HASIL_LELANG',
                'VERIFIKASI',
                'LELANG_MULAI',
                'LELANG_BERAKHIR',
                'PENGINGAT_PELUNASAN',
                'SENGKETA',
            ]);
            $table->enum('kanal', ['IN_APP', 'SMS', 'WA', 'PUSH'])->default('IN_APP');
            $table->text('isi');
            $table->boolean('is_read')->default(false);
            $table->enum('status_kirim', ['ANTRE', 'TERKIRIM', 'GAGAL'])->default('ANTRE');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->cascadeOnDelete();
            $table->foreign('auction_id')
                ->references('auction_id')
                ->on('auction')
                ->nullOnDelete();
            $table->index(['user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
};
