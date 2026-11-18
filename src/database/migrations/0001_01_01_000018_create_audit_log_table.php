<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedBigInteger('actor_id');
            $table->string('aksi', 100);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->json('nilai_lama')->nullable();
            $table->json('nilai_baru')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('actor_id')
                ->references('user_id')
                ->on('user');
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
