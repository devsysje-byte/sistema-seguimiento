<?php

namespace App\Modules\Tesis\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del reenvío del perfil rechazado por el Consejo Universitario.
 *
 * El estudiante puede reenviar la versión corregida del perfil (PDF opcional:
 * si deja el anterior, basta con un mensaje) y vuelve a quedar en evaluación.
 */
class ReenviarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ESTUDIANTE;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'perfil' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
}