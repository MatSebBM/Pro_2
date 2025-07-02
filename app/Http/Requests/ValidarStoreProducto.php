<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;

class ValidarStoreProducto extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'cantidad' => 'required|integer|min:0',
        ];
    }

    protected function prepareForValidation()
    {
        $codigoBase = strtolower(trim($this->codigo ?? ''));

        if (!empty($codigoBase)) {
            $conteo = Producto::where('codigo', 'LIKE', $codigoBase . '%')->count();
            $sufijo = sprintf('%02d', $conteo + 1);
            $codigoFinal = $codigoBase . $sufijo;

            $this->merge([
                'codigo' => $codigoFinal
            ]);
        }
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'Nombre del producto',
            'descripcion' => 'Descripción',
            'precio' => 'Precio',
            'cantidad' => 'Stock disponible',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'precio.required' => 'El precio del producto es obligatorio.',
            'cantidad.required' => 'El campo de la cantidad del producto es obligatorio.',
            'precio.numeric' => 'El precio debe ser un número válido.',
            'cantidad.integer' => 'El stock debe ser un número entero.',
            'nombre.unique' => 'Este nombre de producto ya existe.',
        ];
    }
}
