<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version_code');
            $table->string('version_name', 50);
            $table->string('apk_path');
            $table->string('sha256', 64);
            $table->unsignedBigInteger('file_size');
            $table->boolean('mandatory')->default(true);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['active', 'version_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_updates');
    }
};
