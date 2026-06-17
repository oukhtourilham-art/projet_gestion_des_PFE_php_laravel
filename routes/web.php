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
Route::post('/import/unified', [ImportController::class, 'importUnified'])->name('import.unified');
Route::post('/import/salles', [ImportController::class, 'saveSalles'])->name('import.salles');
Route::post('/import/salle/add', [ImportController::class, 'addSalle'])->name('import.salle.add');

// Affectation
Route::get('/affecter-encadrants', [AffectationController::class, 'affecterEncadrants']);
Route::get('/test-affectation', [AffectationController::class, 'generer']);

// Vérification
Route::get('/verifier-planning', [VerificationController::class, 'verifier']);

// Planning
Route::get('/planning', function () {
    $soutenances = \App\Models\Soutenance::with([
        'student.encadrant',
        'juries.professor'
    ])->get();
    return view('planning', compact('soutenances'));
})->name('planning.index');

Route::get('/planning/generate', [PlanningController::class, 'generatePlanningWeb'])->name('planning.generate');
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
Route::get('/export/pv/directory', [ExportController::class, 'pvDirectory'])->name('export.pv.directory');
Route::get('/export/pv/zip/{professorId}', [ExportController::class, 'exportPVZip'])->name('export.pv.zip');

// Gener tout :
Route::get('/generer-tout', [AffectationController::class, 'genererTout']);