<?php

namespace App\Modules\Estudiantes\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la auto-actualización del perfil académico del estudiante.
 *
 * Con el rediseño, el estudiante SOLO mantiene sus campos académicos
 * (`plan_estudios`, `fecha_conclusion_plan`, `promedio_global`). Los datos
 * personales e institucionales los registra el administrador al crear el
 * perfil aislado (ver StoreEstudianteRequest). Autorización resuelta aquí:
 * solamente el rol `estudiante`.
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
        return [
            'plan_estudios' => ['required', 'string'],
            'fecha_conclusion_plan' => ['required', 'date'],
            'promedio_global' => ['required', 'numeric', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'authorize' => 'Solo estudiantes pueden actualizar su perfil académico',
        ];
    }
}
