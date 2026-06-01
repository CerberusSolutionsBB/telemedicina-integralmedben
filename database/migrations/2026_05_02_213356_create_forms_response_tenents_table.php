<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms_response_tenents', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('tenant_id')->nullable()->constrained('tenants');
            $table->foreignId('form_id')->nullable()->constrained('forms');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms_response_tenents');
    }
};
