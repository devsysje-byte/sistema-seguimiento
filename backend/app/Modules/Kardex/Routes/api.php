<?php

use App\Modules\Kardex\Http\Controllers\KardexController;
use App\Support\Roles;

// Dashboard de KARDEX (roles de gestión académica).
// Registro de postulantes (búsqueda por CI/RU, asignación de modalidad y
// generación automática de credenciales) y consulta del flujo de trámites.
Route::middleware(['auth:sanctum', 'role:' . implode(',', Roles::GESTION)])->group(function () {
    // Busca un postulante por CI o registro universitario.
    Route::post('/kardex/postulantes/buscar', [KardexController::class, 'buscar']);

    // Asigna la modalidad de titulación al postulante y genera (si aún no
    // existen) sus credenciales de acceso. Devuelve las credenciales una única vez.
    Route::post('/kardex/postulantes/asignar-modalidad', [KardexController::class, 'asignarModalidad']);

    // Consulta el estado actual del flujo del postulante (trámite más reciente).
    Route::get('/kardex/postulantes/consultar', [KardexController::class, 'consultar']);

    // Resumen estadístico del dashboard (modalidades, estudiantes y tutores).
    Route::get('/kardex/resumen', [KardexController::class, 'resumen']);
});