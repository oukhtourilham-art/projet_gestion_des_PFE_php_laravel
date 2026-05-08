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
            $table->string('cne')->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email_perso');
            $table->string('email_etu');
            $table->foreignId('encadrant_id')
                  ->nullable()
                  ->constrained('professors')
                  ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};