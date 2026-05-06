<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;

Route::get('/import', [ImportController::class, 'showForm']);

Route::post('/import/students', [ImportController::class, 'importStudents'])->name('import.students');

Route::post('/import/professors', [ImportController::class, 'importProfessors'])->name('import.professors');