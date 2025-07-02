<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Listar todas las auditorías, ordenadas por fecha_evento descendente
        $auditorias = Auditoria::orderBy('fecha_evento', 'desc')->paginate(20);
        return view('productos.eliminado', compact('auditorias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // No se suele crear auditorías manualmente desde un formulario
        return response()->json(['message' => 'No implementado'], 405);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar los datos requeridos
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'accion' => 'required|string|max:255',
            'tabla_afectada' => 'required|string|max:255',
            'registro_id' => 'nullable|integer',
            'cambios' => 'nullable|array',
            'fecha_evento' => 'nullable|date',
        ]);

        // Si 'cambios' es array, convertir a json
        if (isset($validated['cambios']) && is_array($validated['cambios'])) {
            $validated['cambios'] = json_encode($validated['cambios']);
        }

        $auditoria = Auditoria::create($validated);

        return response()->json($auditoria, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Auditoria $auditoria)
    {
        return response()->json($auditoria);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Auditoria $auditoria)
    {
        // No se edita una auditoría
        return response()->json(['message' => 'No implementado'], 405);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Auditoria $auditoria)
    {
        // No se actualizan auditorías
        return response()->json(['message' => 'No implementado'], 405);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Auditoria $auditoria)
    {
        // No se recomienda eliminar auditorías, pero si es necesario:
        $auditoria->delete();
        return response()->json(['message' => 'Auditoría eliminada']);
    }
}
