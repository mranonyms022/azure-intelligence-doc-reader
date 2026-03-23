<?php
// database/migrations/2024_01_01_000001_create_stores_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();        // S101, S102 ...
            $table->string('name', 100);
            $table->string('folder_path', 500)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('stores'); }
};
