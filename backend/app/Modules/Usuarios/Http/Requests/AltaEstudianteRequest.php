<?php

namespace App\Modules\Usuarios\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del alta automática de la cuenta de acceso de un estudiante
 * (CREAR USUARIO). El administrador solo aporta el identificador (CI o registro
 * universitario) del estudiante ya registrado; el sistema genera las credenciales.
 */
class AltaEstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ADMIN;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'identificador' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'identificador.required' => 'Indique el CI o registro universitario del estudiante.',
        ];
    }
}
