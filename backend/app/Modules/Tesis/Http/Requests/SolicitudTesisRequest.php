<?php

namespace App\Modules\Tesis\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la fase de solicitud del Módulo de Tesis de Grado.
 *
 * Exige que el estudiante proponga el título de su tesis (es el que KARDEX
 * verificará antes de aprobar el perfil). Los 3 documentos oficiales (Nota de
 * Solicitud, Certificado de Notas y Perfil de Tesis de Grado) son OPCIONALES:
 * si se adjuntan, cada archivo debe ser un PDF válido.
 */
class SolicitudTesisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ESTUDIANTE;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $tipos = array_keys(config('tesis.tipos_documento', []));

        return [
            'titulo' => ['required', 'string', 'min:10', 'max:255'],
            'documentos' => ['nullable', 'array'],
            'documentos.*.archivo' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'documentos.*.tipo' => ['required', 'string', 'in:'.implode(',', $tipos)],
        ];
    }

    /**
     * Si se adjuntan documentos, verifica que los tipos sean válidos y no
     * estén repetidos (el PDF es opcional, pero lo que se envía debe ser
     * correcto).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $documentos = collect($this->input('documentos', []));

            $tiposSubidos = $documentos->pluck('tipo')->filter()->values();

            $duplicados = $tiposSubidos->duplicates()->values();

            if ($duplicados->isNotEmpty()) {
                $validator->errors()->add(
                    'documentos',
                    'Documento duplicado: '.implode(', ', $duplicados).'.'
                );
            }
        });
    }
}
