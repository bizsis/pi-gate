<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_photos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            // A szerveren tárolt fájl relatív elérési útja
            $table->string('path');

            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();

            // Bájtban
            $table->unsignedBigInteger('file_size')->nullable();

            $table->string('sha256', 64)->nullable();

            $table->timestamp('uploaded_at')->nullable();

            $table->timestamps();

            $table->index('event_id');
            $table->index('sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_photos');
    }
};
