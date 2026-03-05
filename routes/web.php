<?php

use App\Http\Controllers\Admin\TypeNoteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/index', function () {
    return view('index');
});

// ==================== ROUTES ADMIN ====================
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    // Dashboard admin
    Route::get('/dashboard', function () {
        return view('index');
    })->name('dashboard');

    // Parents
    Route::get('parents/import', [Admin\ParentController::class, 'showForm'])->name('parents.import.phpoffice');
    Route::post('parents/import', [Admin\ParentController::class, 'import'])->name('parents.import.phpoffice.post');
    Route::resource('parents', Admin\ParentController::class)->only(['index']);

    // Niveaux, classes, années, matières
    Route::resource('niveaux', Admin\NiveauController::class)->except(['show']);
    Route::resource('classes', Admin\ClasseController::class)->except(['show']);
    Route::resource('annees-scolaires', Admin\AnneeScolaireController::class)->except(['show']);
    Route::resource('classe-annees', Admin\ClasseAnneeController::class)->except(['show']);
    Route::resource('matieres', Admin\MatiereController::class)->except(['show']);

    // Gestion des coefficients pour une classe-année spécifique
    Route::prefix('classe-annees/{classeAnnee}/matieres')->name('classe-matieres.')->group(function () {
        Route::get('/', [Admin\ClasseMatiereController::class, 'index'])->name('index');
        Route::post('/', [Admin\ClasseMatiereController::class, 'store'])->name('store');
        Route::put('{matiere}', [Admin\ClasseMatiereController::class, 'update'])->name('update');
        Route::delete('{matiere}', [Admin\ClasseMatiereController::class, 'destroy'])->name('destroy');
    });

    // Scolarités
    Route::resource('scolarites', App\Http\Controllers\Admin\ScolariteController::class)->except(['show']);

    // Tranches imbriquées dans une scolarité
    Route::prefix('scolarites/{scolarite}/tranches')->name('tranches.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\TrancheController::class, 'index'])->name('index');
        Route::get('create', [App\Http\Controllers\Admin\TrancheController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\TrancheController::class, 'store'])->name('store');
        Route::get('{tranche}/edit', [App\Http\Controllers\Admin\TrancheController::class, 'edit'])->name('edit');
        Route::put('{tranche}', [App\Http\Controllers\Admin\TrancheController::class, 'update'])->name('update');
        Route::delete('{tranche}', [App\Http\Controllers\Admin\TrancheController::class, 'destroy'])->name('destroy');
    });

    // Élèves
    Route::resource('eleves', Admin\EleveController::class)->only(['index']);
    Route::get('eleves/import', [Admin\ImportEleveController::class, 'showForm'])->name('eleves.import');
    Route::post('eleves/import', [Admin\ImportEleveController::class, 'import'])->name('eleves.import.post');

    Route::prefix('eleves/import')->name('eleves.import.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ImportEleveController::class, 'create'])->name('create');
        Route::post('/preview', [App\Http\Controllers\Admin\ImportEleveController::class, 'preview'])->name('preview');
        Route::post('/store', [App\Http\Controllers\Admin\ImportEleveController::class, 'store'])->name('store');
    });

    // Notes
    Route::get('/preview', [Admin\NoteController::class, 'preview'])->name('preview.get');
    Route::resource('type-notes', TypeNoteController::class)->except(['show']);

    // Périodes
    Route::resource('periodes', App\Http\Controllers\Admin\PeriodeController::class)->except(['show']);

    Route::prefix('notes')->name('notes.')->group(function () {
        Route::get('/', [Admin\NoteController::class, 'index'])->name('index');
        Route::get('/create', [Admin\NoteController::class, 'create'])->name('create');
        Route::match(['get', 'post'], '/preview', [Admin\NoteController::class, 'preview'])->name('preview');
        Route::post('/store', [Admin\NoteController::class, 'store'])->name('store');
        Route::get('/export-template', [Admin\NoteController::class, 'exportTemplate'])->name('export-template');
        Route::post('/import-preview', [Admin\NoteController::class, 'importPreview'])->name('import-preview');
    });
});

// routes/web.php
// Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('communiques', \App\Http\Controllers\Admin\CommuniqueController::class);
    Route::patch('communiques/{communique}/toggle', [\App\Http\Controllers\Admin\CommuniqueController::class, 'toggle'])->name('communiques.toggle');
});

// ==================== ROUTES ENSEIGNANT ====================
Route::prefix('enseignant')->name('enseignant.')->middleware(['auth', 'role:enseignant'])->group(function () {
    Route::get('/dashboard', function () {
        return view('enseignant.dashboard');
    })->name('dashboard');

    // Ajoutez ici les autres routes pour les enseignants
});

// ==================== ROUTES PARENT ====================
Route::prefix('parent')->name('parent.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboardparent');
    })->name('dashboard');

    // Ajoutez ici les autres routes pour les parents
});

// ==================== ROUTES PAR DÉFAUT ====================
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ==================== PROFIL ====================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ==================== AUTH ====================
require __DIR__ . '/auth.php';