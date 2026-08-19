<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('card_id')
                ->nullable()
                ->constrained('cards')
                ->nullOnDelete();

            // IN = érkezés
            // OUT = távozás
            $table->string('event_type', 10);

            // A PDA-n rögzített tényleges időpont
            $table->timestamp('event_at');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('photo_path')->nullable();

            // Mobiloldali eseményazonosító,
            // hogy ugyanazt az eseményt ne töltsük fel kétszer.
            $table->string('client_event_uuid')->unique();

            $table->timestamp('received_at')->nullable();

            $table->timestamps();

            $table->index([
                'company_id',
                'employee_id',
                'event_at'
            ]);

            $table->index([
                'device_id',
                'event_at'
            ]);

            $table->index([
                'company_id',
                'event_type',
                'event_at'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
