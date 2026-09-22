<?php

use App\Modules\Estudiantes\Http\Controllers\EstudianteController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/estudiante/perfil', [EstudianteController::class, 'me']);
    Route::post('/estudiante/perfil', [EstudianteController::class, 'store']);
});