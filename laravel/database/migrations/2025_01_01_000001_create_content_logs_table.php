<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_logs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url');
            $table->string('keyword')->nullable();
            $table->string('language')->default('en');
            $table->string('status', 20)->default('published');
            $table->integer('views')->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->timestamps();

            $table->index('created_at');
            $table->index('status');
            $table->index('language');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_logs');
    }
};
