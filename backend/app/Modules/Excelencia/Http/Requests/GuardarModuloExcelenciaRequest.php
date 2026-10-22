<?php

namespace App\Modules\Excelencia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del guardado de un módulo del flujo de Excelencia Académica.
 *
 * Igual que en el flujo de Examen de Grado, este request es un sobre fino:
 * valida la forma del cuerpo y deja las reglas de cada módulo (promedio
 * válido, resolución obligatoria con su fecha) al
 * \App\Modules\Excelencia\Services\ExcelenciaService, que además devuelve el
 * mensaje en el idioma de la tarjeta que lo disparó.
 */
class GuardarModuloExcelenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `datos` puede viajar vacío (`{}`): el módulo de reporte no pide ningún
     * campo. Cada módulo declara sus propios requisitos en `ExcelenciaService`.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'datos' => ['present', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'datos.present' => 'Debe enviar los datos del módulo de Excelencia Académica.',
            'datos.array' => 'Los datos del módulo deben ser un objeto.',
        ];
    }
}
