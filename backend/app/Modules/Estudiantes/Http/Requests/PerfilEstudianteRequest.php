<?php

namespace App\Modules\Estudiantes\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del registro/actualización del perfil de estudiante.
 *
 * La autorización se resuelve aquí: solo el rol `estudiante` puede registrar
 * su propio perfil.
 */
class PerfilEstudianteRequest extends FormRequest
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
        $usuario = $this->user();

        return [
            'codigo_universitario' => [
                'required',
                'string',
                Rule::unique('estudiantes', 'codigo_universitario')
                    ->ignore($usuario?->estudiante?->id_estudiante, 'id_estudiante'),
            ],
            'plan_estudios' => ['required', 'string'],
            'fecha_conclusion_plan' => ['required', 'date'],
            'promedio_global' => ['required', 'numeric', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'authorize' => 'Solo estudiantes pueden registrar este perfil',
        ];
    }
}