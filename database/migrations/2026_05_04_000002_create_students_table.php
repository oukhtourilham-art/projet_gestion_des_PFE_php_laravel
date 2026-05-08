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
<<<<<<< HEAD
            $table->string('cne')->unique();
=======
            $table->string('CNE')->unique();
>>>>>>> ce7d28093b8d038d14b76effc7e815a3c6f07925
            $table->string('nom');
            $table->string('prenom');
            $table->string('email_perso');
            $table->string('email_etu');
<<<<<<< HEAD
            $table->foreignId('encadrant_id')
                  ->nullable()
                  ->constrained('professors')
                  ->nullOnDelete();
=======
            $table->string('filiere')->nullable();
            $table->foreignId('encadrant_id')->nullable()->constrained('professors')->nullOnDelete();
>>>>>>> ce7d28093b8d038d14b76effc7e815a3c6f07925
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};