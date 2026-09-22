<?php

use App\Modules\Tramites\Http\Controllers\ModalidadController;
use App\Modules\Tramites\Http\Controllers\TramiteController;
use App\Support\Roles;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/modalidades', [ModalidadController::class, 'index']);
    Route::get('/estudiante/tramite-activo', [TramiteController::class, 'tramiteActivo']);
    Route::post('/tramites', [TramiteController::class, 'store']);
    Route::get('/tramites/{id}', [TramiteController::class, 'show']);
});

// Paneles de gestión (roles de gestión).
Route::middleware(['auth:sanctum', 'role:' . implode(',', Roles::GESTION)])->group(function () {
    Route::get('/tramites/pendientes', [TramiteController::class, 'pendientes']);
    Route::get('/tramites/estadisticas', [TramiteController::class, 'estadisticas']);
    Route::post('/tramites/{id}/revisar', [TramiteController::class, 'revisar']);
    Route::post('/tramites/{id}/asignar-tutor', [TramiteController::class, 'asignarTutor']);
});

// Transición de estados: también lo pueden ejecutar los roles de gestión + concejo.
Route::middleware(['auth:sanctum', 'role:' . implode(',', Roles::GESTION_CONCEJO)])->group(function () {
    Route::post('/tramites/{id}/transicionar', [TramiteController::class, 'transicionar']);
});

// Panel del docente tutor.
Route::middleware(['auth:sanctum', 'role:' . Roles::DOCENTE])->group(function () {
    Route::get('/tutorias', [TramiteController::class, 'tutorias']);
});