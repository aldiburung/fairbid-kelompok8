<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_moderation', function (Blueprint $table) {
            $table->id('moderation_id');
            $table->unsignedBigInteger('auction_id');
            $table->unsignedBigInteger('admin_id');
            $table->enum('aksi', ['TOLAK', 'TANGGUHKAN', 'TURUNKAN', 'SETUJUI_BATAL']);
            $table->text('alasan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('auction_id')
                ->references('auction_id')
                ->on('auction')
                ->cascadeOnDelete();
            $table->foreign('admin_id')
                ->references('user_id')
                ->on('user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_moderation');
    }
};
