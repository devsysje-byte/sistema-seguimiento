<?php

namespace App\Modules\Estudiantes\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del alta administrativa del perfil aislado de un estudiante.
 *
 * El administrador registra los datos personales e institucionales del
 * estudiante (CI, nombres, apellidos, registro universitario y fecha de
 * nacimiento); el alta de credenciales de acceso es un paso posterior e
 * independiente (CREAR USUARIO). Autorización resuelta aquí: solo admin.
 */
class StoreEstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ADMIN;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'ci' => ['required', 'string', 'max:20',
                Rule::unique('estudiantes', 'ci'),
            ],
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'registro_universitario' => ['required', 'string', 'max:50',
                Rule::unique('estudiantes', 'registro_universitario'),
            ],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'authorize' => 'Solo el administrador puede crear estudiantes',
        ];
    }
}
