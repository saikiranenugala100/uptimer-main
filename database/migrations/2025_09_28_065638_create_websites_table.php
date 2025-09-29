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
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_up')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_downtime_at')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'is_active']);
            $table->index('last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
