<?php

namespace App\Modules\Tesis\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del reenvío del perfil rechazado por KARDEX.
 *
 * El estudiante debe volver a presentar un título de tesis (el que KARDEX
 * volverá a verificar) y, opcionalmente, la versión corregida del perfil en
 * PDF: si conserva el documento anterior, basta con el título.
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
            'titulo' => ['required', 'string', 'min:10', 'max:255'],
            'perfil' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
}
