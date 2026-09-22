<?php

use App\Modules\Tesis\Http\Controllers\TesisController;
use App\Support\Roles;

// Flujo del estudiante en el Módulo de Tesis de Grado.
Route::middleware(['auth:sanctum', 'role:' . Roles::ESTUDIANTE])->group(function () {
    // Presenta la solicitud con los 3 documentos obligatorios (multipart).
    Route::post('/tesis/solicitud', [TesisController::class, 'store']);

    // Reenvía el perfil corregido tras su rechazo por el Consejo Universitario.
    Route::post('/tesis/{id}/reenviar', [TesisController::class, 'reenviar']);

    // Reenvía el documento final corregido tras su calificación como insuficiente.
    Route::post('/tesis/{id}/reenviar-documento', [TesisController::class, 'reenviarDocumento']);

    // Solicita una fecha para su defensa (queda pendiente de programación por Kardex).
    Route::post('/tesis/{id}/solicitar-fecha-defensa', [TesisController::class, 'solicitarFechaDefensa']);
});

// Configuración pública del módulo (plazos y catálogo de documentos).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tesis/config', [TesisController::class, 'config']);
});