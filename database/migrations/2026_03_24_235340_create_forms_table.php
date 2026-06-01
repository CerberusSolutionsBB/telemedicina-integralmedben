<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->ulid('code')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->string('title', 255)->nullable();
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('response_limit')->nullable();
            $table->boolean('is_public')->default(true);
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('responses_count')->default(0);
            $table->enum('status', ['rascunho', 'ativo', 'pausado', 'encerrado'])
                ->default('rascunho')
                ->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
