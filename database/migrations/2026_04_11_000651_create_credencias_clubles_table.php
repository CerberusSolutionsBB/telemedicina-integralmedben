<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credencias_clubles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('grant_type')->nullable();
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->string('scope')->nullable();
            $table->string('token_type')->nullable();
            $table->integer('expires_in')->nullable();
            $table->longText('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('refresh_token')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('client_id');
            $table->index('token_expires_at');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('credencias_clubles');
    }
};
