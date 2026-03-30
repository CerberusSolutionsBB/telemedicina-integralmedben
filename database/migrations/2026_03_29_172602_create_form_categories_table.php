<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('slug', 150)->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('color', 25)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('sort_order');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('form_categories');
    }
};