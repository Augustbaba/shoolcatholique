<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EleveController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\ParentController;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json([
        'message' => 'API fonctionne'
    ]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {
    // Parents
    Route::get('/parents/{id}/enfants', [ParentController::class, 'enfants']);

    // Élèves
    Route::get('/eleves/{id}/dernieres-notes', [EleveController::class, 'dernieresNotes']);
    Route::get('/eleves/{id}/toutes-notes', [EleveController::class, 'toutesNotes']);
    Route::get('/eleves/{id}/statistiques', [EleveController::class, 'statistiques']);

    Route::post('/save-fcm-token', [NotificationController::class, 'saveFCMToken']);
    Route::post('/send-notification', [NotificationController::class, 'sendNotification']);

    // Scolarité
    Route::get('/eleves/{eleve}/scolarite', [PaiementController::class, 'getScolariteEnfant']);
    Route::get('/parents/{parent}/paiements-recents', [PaiementController::class, 'getPaiementsRecents']);

    // FedaPay
    Route::post('/paiements/create-order', [PaiementController::class, 'createOrder']);
    Route::post('/paiements/capture-order', [PaiementController::class, 'captureOrder']);

    // Reçu PDF
    Route::get('/paiements/{paiement}/recu', [PaiementController::class, 'downloadRecu']);
});

// Callbacks FedaPay
Route::get('/fedapay/success', [PaiementController::class, 'successRedirect']);
Route::get('/fedapay/cancel', [PaiementController::class, 'cancelRedirect']);
