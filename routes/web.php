<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\NoteController;
use App\Http\Controllers\Admin\TypeNoteController;
use App\Http\Controllers\Admin\CahierNotesController;
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
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // ==================== PARENTS ====================
    Route::get('parents/import', [Admin\ParentController::class, 'showForm'])->name('parents.import.phpoffice');
    Route::post('parents/import', [Admin\ParentController::class, 'import'])->name('parents.import.phpoffice.post');
    Route::resource('parents', Admin\ParentController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::get('parents/{parent}/reset-password', [Admin\ParentController::class, 'resetPasswordForm'])->name('parents.reset-password.form');
    Route::post('parents/{parent}/reset-password', [Admin\ParentController::class, 'resetPassword'])->name('parents.reset-password');

    // ==================== NIVEAUX, CLASSES, ANNÉES, MATIÈRES ====================
    Route::resource('niveaux', Admin\NiveauController::class)->except(['show']);
    Route::resource('classes', Admin\ClasseController::class)->except(['show']);
    Route::resource('annees-scolaires', Admin\AnneeScolaireController::class)->except(['show']);
    Route::resource('classe-annees', Admin\ClasseAnneeController::class)->except(['show']);
    Route::resource('matieres', Admin\MatiereController::class)->except(['show']);

    // ==================== GESTION DES COEFFICIENTS ====================
    Route::prefix('classe-annees/{classeAnnee}/matieres')->name('classe-matieres.')->group(function () {
        Route::get('/', [Admin\ClasseMatiereController::class, 'index'])->name('index');
        Route::post('/', [Admin\ClasseMatiereController::class, 'store'])->name('store');
        Route::put('{matiere}', [Admin\ClasseMatiereController::class, 'update'])->name('update');
        Route::delete('{matiere}', [Admin\ClasseMatiereController::class, 'destroy'])->name('destroy');
    });

    // ==================== SCOLARITÉS ====================
    Route::resource('scolarites', App\Http\Controllers\Admin\ScolariteController::class)->except(['show']);

    // ==================== TRANCHES ====================
    Route::prefix('scolarites/{scolarite}/tranches')->name('tranches.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\TrancheController::class, 'index'])->name('index');
        Route::get('create', [App\Http\Controllers\Admin\TrancheController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\TrancheController::class, 'store'])->name('store');
        Route::get('{tranche}/edit', [App\Http\Controllers\Admin\TrancheController::class, 'edit'])->name('edit');
        Route::put('{tranche}', [App\Http\Controllers\Admin\TrancheController::class, 'update'])->name('update');
        Route::delete('{tranche}', [App\Http\Controllers\Admin\TrancheController::class, 'destroy'])->name('destroy');
    });

    // ==================== ÉLÈVES ====================
    Route::resource('eleves', Admin\EleveController::class)->only(['index']);
    Route::get('eleves/import', [Admin\ImportEleveController::class, 'showForm'])->name('eleves.import');
    Route::post('eleves/import', [Admin\ImportEleveController::class, 'import'])->name('eleves.import.post');

    Route::prefix('eleves/import')->name('eleves.import.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ImportEleveController::class, 'create'])->name('create');
        Route::post('/preview', [App\Http\Controllers\Admin\ImportEleveController::class, 'preview'])->name('preview');
        Route::post('/store', [App\Http\Controllers\Admin\ImportEleveController::class, 'store'])->name('store');
    });

    // ==================== NOTES - ROUTES COMPLÈTES ====================
    // Type de notes
    Route::resource('type-notes', TypeNoteController::class)->except(['show']);

    // Périodes
    Route::resource('periodes', App\Http\Controllers\Admin\PeriodeController::class)->except(['show']);

    // Routes principales des notes
    Route::get('/notes', [NoteController::class, 'index'])->name('notes.index');
    Route::get('/notes/create', [NoteController::class, 'create'])->name('notes.create');
    Route::match(['get', 'post'], '/notes/preview', [NoteController::class, 'preview'])->name('notes.preview');
    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
    
    // Routes d'export
    Route::get('/notes/export', [NoteController::class, 'export'])->name('notes.export');
    Route::get('/notes/export-pdf', [NoteController::class, 'exportPdf'])->name('notes.export-pdf');
    Route::get('/notes/export-template', [NoteController::class, 'exportTemplate'])->name('notes.export-template');
    Route::get('/notes/export-template-excel', [NoteController::class, 'exportTemplateExcel'])->name('notes.export-template-excel');
    
    // Routes d'import
    Route::post('/notes/import-preview', [NoteController::class, 'importPreview'])->name('notes.import-preview');
    Route::post('/notes/import-image', [NoteController::class, 'importImage'])->name('notes.import-image');

    // ==================== CAHIER DE NOTES ====================
    Route::prefix('cahier-notes')->name('cahier-notes.')->group(function () {
        // Page de sélection (choix de la classe et de la période)
        Route::get('/', [CahierNotesController::class, 'index'])->name('index');

        // Cahier de notes complet d'une classe (avec filtres en query string)
        Route::get('/classe/{classeAnnee}', [CahierNotesController::class, 'classe'])->name('classe');

        // Liste des élèves d'une classe (point d'entrée vers les bulletins)
        Route::get('/classe/{classeAnnee}/eleves', [CahierNotesController::class, 'listeEleves'])->name('liste-eleves');

        // Bulletin individuel d'un élève (toutes périodes)
        Route::get('/eleve/{eleve}', [CahierNotesController::class, 'bulletinEleve'])->name('bulletin-eleve');
    });

    // ==================== COMMUNIQUÉS ====================
    Route::resource('communiques', \App\Http\Controllers\Admin\CommuniqueController::class);
    Route::patch('communiques/{communique}/toggle', [\App\Http\Controllers\Admin\CommuniqueController::class, 'toggle'])->name('communiques.toggle');
});

// ==================== ROUTES ENSEIGNANT ====================
Route::prefix('enseignant')->name('enseignant.')->middleware(['auth', 'role:enseignant'])->group(function () {
    Route::get('/dashboard', function () {
        return view('enseignant.dashboard');
    })->name('dashboard');
});

// ==================== ROUTES PARENT ====================
Route::prefix('parent')->name('parent.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboardparent');
    })->name('dashboard');
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