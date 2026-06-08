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
        Schema::table('soutenances', function (Blueprint $table) {
            $table->unsignedBigInteger('binome_student_id')->nullable()->after('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('soutenances', function (Blueprint $table) {
            $table->dropColumn('binome_student_id');
        });
    }
};
