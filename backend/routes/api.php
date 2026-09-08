<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EstudianteController;
use App\Http\Controllers\Api\TramiteController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('/usuarios', UserController::class)->only(['index', 'store', 'destroy']);

    Route::get('/modalidades', function () {
        return \App\Models\Modalidad::where('activo', true)->orderBy('nombre')->get();
    });

    // Perfil de Estudiante
    Route::get('/estudiante/perfil', [EstudianteController::class, 'me']);
    Route::post('/estudiante/perfil', [EstudianteController::class, 'store']);
    Route::get('/estudiante/tramite-activo', [TramiteController::class, 'tramiteActivo']);

    // Trámites
    Route::post('/tramites', [TramiteController::class, 'store']);
    Route::get('/tramites/pendientes', [TramiteController::class, 'pendientes']);
    Route::post('/tramites/{id}/revisar', [TramiteController::class, 'revisar']);
    Route::get('/tramites/{id}', [TramiteController::class, 'show']);
    Route::post('/tramites/{id}/transicionar', [TramiteController::class, 'transicionar']);
});
