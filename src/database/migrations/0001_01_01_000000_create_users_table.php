<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->string('no_hp', 20);
            $table->enum('tipe_akun', ['PERSONAL', 'BISNIS'])->default('PERSONAL');
            $table->enum('peran', ['USER', 'ADMIN'])->default('USER');
            $table->enum('status_verifikasi', ['BELUM', 'MENUNGGU', 'DISETUJUI', 'DITOLAK'])->default('BELUM');
            $table->enum('status_akun', ['AKTIF', 'DITANGGUHKAN', 'DIBLOKIR'])->default('AKTIF');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
