<?php

use App\Modules\Estudiantes\Http\Controllers\EstudianteController;

// Gestión administrativa del perfil aislado de estudiantes.
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/estudiantes', [EstudianteController::class, 'index']);
    Route::post('/estudiantes', [EstudianteController::class, 'store']);
    Route::get('/estudiantes/sin-usuario', [EstudianteController::class, 'sinUsuario']);
});

// Auto-gestión del perfil del estudiante autenticado.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/estudiante/perfil', [EstudianteController::class, 'me']);
    Route::match(['put', 'patch'], '/estudiante/perfil', [EstudianteController::class, 'updatePerfil']);
});
