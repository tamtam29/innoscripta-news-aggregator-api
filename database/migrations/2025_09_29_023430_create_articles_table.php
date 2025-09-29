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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('url');
            $table->string('url_sha1', 40)->unique();
            $table->text('image_url')->nullable();
            $table->string('author')->nullable();
            $table->string('publisher')->nullable();
            $table->string('provider')->nullable();
            $table->timestamp('published_at')->index();
            $table->string('category')->nullable();
            $table->timestamps();

            $table->index(['publisher']);
            $table->index(['author']);
            $table->index(['category']);
            $table->index(['published_at', 'id']);
            $table->index(['provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
