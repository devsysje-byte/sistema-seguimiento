<?php

/**
 * Configuración del Módulo de Tesis de Grado.
 *
 * Parámetros temporales del flujo oficial del estudiante:
 *
 *   - `plazo_presentacion_meses`: cuántos meses tiene el estudiante para
 *     presentar el documento final desde la aprobación del perfil. Configurable
 *     con `TESIS_PLAZO_PRESENTACION_MESES` (se acota siempre entre 3 y 12).
 *   - `dias_correccion`: días para corregir el documento cuando la revisión es
 *     insuficiente o la defensa no fue aprobada. Configurable con
 *     `TESIS_DIAS_CORRECCION` (por defecto 90, según el reglamento).
 *   - `tipos_documento`: catálogo de los documentos obligatorios de la fase de
 *     solicitud (Nota de Solicitud, Certificado de Notas, Perfil de Tesis).
 */
return [
    'plazo_presentacion_meses' => max(3, min(12, (int) env('TESIS_PLAZO_PRESENTACION_MESES', 12))),

    'dias_correccion' => max(1, (int) env('TESIS_DIAS_CORRECCION', 90)),

    'tipos_documento' => [
        'nota_solicitud' => 'Nota de Solicitud',
        'certificado_notas' => 'Certificado de Notas',
        'perfil_tesis' => 'Perfil de Tesis de Grado',
    ],
];