<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification', function (Blueprint $table) {
            $table->bigIncrements('verification_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('jenis_dokumen', ['KTP', 'NIB', 'NPWP']);
            $table->string('file_url', 255);
            $table->enum('status', ['MENUNGGU', 'DISETUJUI', 'DITOLAK'])
                ->default('MENUNGGU');
            $table->text('alasan_penolakan')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user');
            $table->foreign('reviewed_by')
                ->references('user_id')
                ->on('user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification');
    }
};
