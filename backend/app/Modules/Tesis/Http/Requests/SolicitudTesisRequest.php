<?php

namespace App\Modules\Tesis\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la fase de solicitud del Módulo de Tesis de Grado.
 *
 * Exige que el estudiante adjunte OBLIGATORIAMENTE los 3 documentos oficiales:
 * Nota de Solicitud, Certificado de Notas y Perfil de Tesis de Grado.
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
            'documentos' => ['required', 'array', 'min:3'],
            'documentos.*.archivo' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'documentos.*.tipo' => ['required', 'string', 'in:' . implode(',', $tipos)],
        ];
    }

    /**
     * Verifica que estén presentes exactamente los 3 documentos obligatorios.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tiposSubidos = collect($this->input('documentos', []))
                ->pluck('tipo')
                ->filter()
                ->all();

            $tiposRequeridos = array_keys(config('tesis.tipos_documento', []));
            $faltantes = array_values(array_diff($tiposRequeridos, $tiposSubidos));

            if (! empty($faltantes)) {
                $validator->errors()->add(
                    'documentos',
                    'Son obligatorios los 3 documentos: ' . implode(', ', $faltantes) . '.'
                );
            }
        });
    }
}