<?php

namespace App\Modules\Usuarios\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de creación de usuarios.
 */
class StoreUserRequest extends FormRequest
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
        $roles = implode(',', Roles::TODOS);

        return [
            'ci' => ['required', 'string', 'unique:users,ci'],
            'nombres' => ['required', 'string'],
            'apellidos' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'telefono' => ['nullable', 'string'],
            'rol' => ['required', "in:{$roles}"],
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}