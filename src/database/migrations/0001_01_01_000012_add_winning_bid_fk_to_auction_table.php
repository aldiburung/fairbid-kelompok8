<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction', function (Blueprint $table) {
            $table->foreign('winning_bid_id')
                ->references('bid_id')
                ->on('bid')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auction', function (Blueprint $table) {
            $table->dropForeign(['winning_bid_id']);
        });
    }
};
