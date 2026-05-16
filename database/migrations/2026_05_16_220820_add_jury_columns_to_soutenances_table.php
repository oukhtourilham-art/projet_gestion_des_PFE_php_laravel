<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soutenances', function (Blueprint $table) {
            $table->foreignId('encadrant_id')->nullable()->constrained('professors')->onDelete('set null');
            $table->foreignId('jury_id1')->nullable()->constrained('professors')->onDelete('set null');
            $table->foreignId('jury_id2')->nullable()->constrained('professors')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('soutenances', function (Blueprint $table) {
            $table->dropForeign(['encadrant_id']);
            $table->dropForeign(['jury_id1']);
            $table->dropForeign(['jury_id2']);
            $table->dropColumn(['encadrant_id', 'jury_id1', 'jury_id2']);
        });
    }
};