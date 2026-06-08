<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {

            // Ajouter seulement les colonnes qui n'existent pas encore
            if (!Schema::hasColumn('students', 'sujet')) {
                $table->string('sujet')->nullable();
            }
            if (!Schema::hasColumn('students', 'langue')) {
                $table->string('langue')->default('FR');
            }
            if (!Schema::hasColumn('students', 'binome')) {
                $table->boolean('binome')->default(false);
            }
            if (!Schema::hasColumn('students', 'binome_id')) {
                $table->unsignedBigInteger('binome_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(
                collect(['sujet', 'langue', 'binome', 'binome_id'])
                    ->filter(fn($col) => Schema::hasColumn('students', $col))
                    ->toArray()
            );
        });
    }
};