<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AffectationController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Import
Route::get('/import', [ImportController::class, 'showForm'])->name('import.form');
Route::post('/import/students', [ImportController::class, 'importStudents'])->name('import.students');
Route::post('/import/professors', [ImportController::class, 'importProfessors'])->name('import.professors');

// Affectation
Route::get('/affecter-encadrants', [AffectationController::class, 'affecterEncadrants']);
Route::get('/test-affectation', [AffectationController::class, 'generer']);

// Vérification
Route::get('/verifier-planning', [VerificationController::class, 'verifier']);

// Planning
Route::get('/planning', function () {
    $soutenances = \App\Models\Soutenance::with(['student', 'juries.professor'])->get();
    return view('planning', compact('soutenances'));
})->name('planning.index');

// Export
Route::get('/export', [ExportController::class, 'index'])->name('export.index');
Route::get('/export/pdf', [ExportController::class, 'exportPDF'])->name('export.pdf');
Route::get('/export/word', [ExportController::class, 'exportWord'])->name('export.word');
Route::get('/export/pv/{id}', [ExportController::class, 'exportPV'])->name('export.pv');