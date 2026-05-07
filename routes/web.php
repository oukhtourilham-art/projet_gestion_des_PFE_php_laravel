<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AffectationController;
use App\Http\Controllers\VerificationController;

Route::get('/test-affectation', [AffectationController::class, 'generer']);

Route::get('/verifier-planning', [VerificationController::class, 'verifier']);

Route::get('/import', [ImportController::class, 'showForm']);

Route::post('/import/students', [ImportController::class, 'importStudents'])->name('import.students');

Route::post('/import/professors', [ImportController::class, 'importProfessors'])->name('import.professors');