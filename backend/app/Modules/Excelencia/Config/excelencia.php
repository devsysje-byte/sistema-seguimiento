<?php

/**
 * Configuración del Módulo de Excelencia Académica.
 *
 * Parámetros del flujo de titulación por promedio sobresaliente:
 *
 *   - `nota_minima`: promedio general mínimo del estudiante para optar por la
 *     modalidad. Con un promedio inferior el trámite se RECHAZA y el flujo
 *     termina. Configurable con `EXCELENCIA_NOTA_MINIMA` (por defecto 80).
 *   - `rango_nota`: rango válido en el que se puede registrar un promedio
 *     (0 a 100). El backend vuelve a validarlo por si el formulario se manipula.
 */
return [
    'nota_minima' => max(1, min(100, (int) env('EXCELENCIA_NOTA_MINIMA', 80))),

    'rango_nota' => [0, 100],
];
