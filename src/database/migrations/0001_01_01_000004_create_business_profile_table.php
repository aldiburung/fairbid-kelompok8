<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profile', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('nama_usaha', 150);
            $table->string('nib', 30)->unique();
            $table->string('npwp', 30)->unique();
            $table->text('alamat')->nullable();
            $table->string('nama_penanggung_jawab', 150);

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profile');
    }
};
