<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AffectationController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PlanningController;

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Import
Route::get('/import', [ImportController::class, 'showForm'])->name('import.form');
Route::post('/import/students', [ImportController::class, 'importStudents'])->name('import.students');
Route::post('/import/professors', [ImportController::class, 'importProfessors'])->name('import.professors');
Route::post('/import/sujets', [ImportController::class, 'importSujets'])->name('import.sujets');
Route::post('/import/salles', [ImportController::class, 'saveSalles'])->name('import.salles');
Route::post('/import/salle/add', [ImportController::class, 'addSalle'])->name('import.salle.add');

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

Route::get('/planning/generate', [PlanningController::class, 'generatePlanning'])->name('planning.generate');
Route::post('/planning/dates', [PlanningController::class, 'saveDates'])->name('planning.dates');

// Export Planning
Route::get('/export', [ExportController::class, 'index'])->name('export.index');
Route::get('/export/pdf', [ExportController::class, 'exportPDF'])->name('export.pdf');
Route::get('/export/word', [ExportController::class, 'exportWord'])->name('export.word');

// Export Affectation encadrants
Route::get('/export/affectation/pdf', [ExportController::class, 'exportAffectationPDF'])->name('export.affectation.pdf');
Route::get('/export/affectation/word', [ExportController::class, 'exportAffectationWord'])->name('export.affectation.word');

Route::get('/export/pv/filiere/{filiere}/{format}', [ExportController::class, 'exportPVFiliere'])->name('export.pv.filiere');

// Export PV individuel
Route::get('/export/pv/{id}/{format}', [ExportController::class, 'exportPV'])->name('export.pv');

// Gener tout :
Route::get('/generer-tout', [AffectationController::class, 'genererTout']);