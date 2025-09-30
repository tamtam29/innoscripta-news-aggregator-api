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
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('source_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->string('category')->nullable();
            $table->string('language', 2)->default('en');
            $table->string('country', 2)->nullable();
            $table->string('provider')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['provider', 'is_active']);
            $table->index('source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};