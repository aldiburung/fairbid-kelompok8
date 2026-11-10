<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bid', function (Blueprint $table) {
            $table->id('bid_id');
            $table->unsignedBigInteger('auction_id');
            $table->unsignedBigInteger('bidder_id');
            $table->unsignedBigInteger('deposit_id');
            $table->decimal('amount', 15, 2);
            $table->integer('sequence_no');
            $table->timestamp('server_timestamp', 6)->default(DB::raw('CURRENT_TIMESTAMP(6)'));
            $table->enum('status', ['VALID', 'DITOLAK'])->default('VALID');
            $table->boolean('is_flagged')->default(false);
            $table->boolean('extended_auction')->default(false);

            $table->foreign('auction_id')
                ->references('auction_id')
                ->on('auction')
                ->cascadeOnDelete();
            $table->foreign('bidder_id')
                ->references('user_id')
                ->on('user');
            $table->foreign('deposit_id')
                ->references('deposit_id')
                ->on('auction_deposit');
            $table->unique(['auction_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bid');
    }
};
