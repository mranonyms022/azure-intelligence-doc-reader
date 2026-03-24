<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('needs_review')->default(false)->after('processed_at');
            $table->decimal('min_confidence_score', 5, 2)->nullable()->after('needs_review');
            // min_confidence_score = us invoice ka sabse low confidence field ka score
            // useful for sorting/filtering review queue
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['needs_review', 'min_confidence_score']);
        });
    }
};
