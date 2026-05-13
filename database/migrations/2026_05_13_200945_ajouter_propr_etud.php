<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {

            $table->string('sujet')->nullable();
            $table->string('langue')->nullable();
            $table->foreignId('binome_id')->nullable()->constrained('students')->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {

            $table->dropConstrainedForeignId('binome_id');
            $table->dropColumn(['sujet','langue']);

        });
    }
};