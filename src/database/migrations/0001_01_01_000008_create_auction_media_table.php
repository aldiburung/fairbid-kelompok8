<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_media', function (Blueprint $table) {
            $table->id('media_id');
            $table->unsignedBigInteger('auction_id');
            $table->enum('tipe', ['FOTO', 'VIDEO'])->default('FOTO');
            $table->string('url', 255);
            $table->integer('urutan')->default(1);

            $table->foreign('auction_id')
                ->references('auction_id')
                ->on('auction')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_media');
    }
};
