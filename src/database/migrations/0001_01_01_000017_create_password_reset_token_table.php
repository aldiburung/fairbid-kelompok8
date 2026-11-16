<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_token', function (Blueprint $table) {
            $table->bigIncrements('token_id');
            $table->unsignedBigInteger('user_id');
            $table->string('token_hash', 255)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_token');
    }
};
