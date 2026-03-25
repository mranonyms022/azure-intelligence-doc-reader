<?php
// database/migrations/2024_01_01_000003_create_user_store_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_store', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('store_id')
                  ->constrained('stores')
                  ->onDelete('cascade');
            $table->timestamps();

            // One user can be assigned to one store only once
            $table->unique(['user_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_store');
    }
};
