<?php

namespace App\Modules\Tramites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del guardado de un módulo del flujo de titulación (Tesis de Grado).
 *
 * El identificador del módulo llega como parámetro de ruta (`{modulo}`); aquí
 * solo se valida el cuerpo con los datos del formulario del módulo.
 */
class GuardarModuloFlujoRequest extends FormRequest
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
        return [
            'datos' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'datos.required' => 'Debe enviar los datos del módulo del flujo.',
            'datos.array' => 'Los datos del módulo deben ser un objeto.',
        ];
    }
}
