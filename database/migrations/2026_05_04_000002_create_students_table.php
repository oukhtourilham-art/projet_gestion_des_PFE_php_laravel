<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
<<<<<<< Updated upstream
            $table->string('CNE')->unique();

=======
            $table->string('cne')->unique();
>>>>>>> Stashed changes
            $table->string('nom');
            $table->string('prenom');
            $table->string('email_perso');
            $table->string('email_etu');
<<<<<<< Updated upstream
            $table->string('filiere')->nullable();
            $table->foreignId('encadrant_id')->nullable()->constrained('professors')->nullOnDelete();
=======
            $table->foreignId('encadrant_id')->nullable() ->constrained('professors') ->nullOnDelete();
>>>>>>> Stashed changes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};