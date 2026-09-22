<?php

namespace App\Modules\Usuarios\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de actualización de usuarios (incluye el perfil de estudiante
 * anidado que el panel de administración envía junto al usuario).
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $usuarioId = $this->route('id');
        $roles = implode(',', Roles::TODOS);

        return [
            'ci' => ['required', 'string', "unique:users,ci,{$usuarioId},id_usuario"],
            'nombres' => ['required', 'string'],
            'apellidos' => ['required', 'string'],
            'email' => ['required', 'email', "unique:users,email,{$usuarioId},id_usuario"],
            'telefono' => ['nullable', 'string'],
            'rol' => ['required', "in:{$roles}"],
            'password' => ['nullable', 'string', 'min:6'],

            'estudiante.codigo_universitario' => ['nullable', 'string'],
            'estudiante.plan_estudios' => ['nullable', 'string'],
            'estudiante.fecha_conclusion_plan' => ['nullable', 'date'],
            'estudiante.promedio_global' => ['nullable', 'numeric', 'between:0,100'],
            'estudiante.estado' => ['nullable', 'in:activo,inactivo'],
        ];
    }
}