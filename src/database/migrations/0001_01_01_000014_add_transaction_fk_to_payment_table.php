<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment', function (Blueprint $table) {
            $table->foreign('transaction_id')
                ->references('transaction_id')
                ->on('transaction')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
        });
    }
};
