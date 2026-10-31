<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_profile', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('nama_lengkap', 150);
            $table->string('nik', 16)->unique();
            $table->date('tgl_lahir');
            $table->text('alamat');

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_profile');
    }
};
