<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Auditoria;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\ValidarStoreProducto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// use App\Http\Requests\UpdateProductoRequest;

class ProductoController extends Controller
{
    public function index()
    {
        $search = request()->input('search');
        $perPage = request()->input('per_page', 5);

        $query = Producto::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', $search . '%')
                  ->orWhere('codigo', 'like', $search . '%')
                  ->orWhere('ID', 'like', $search . '%')
                  ->orWhere('cantidad', '>=', is_numeric($search) ? $search : 0)
                  ->orWhere('precio', 'like', $search . '%');
            });
        }

        $productos = $query->latest()->paginate($perPage)->appends([
            'search' => $search,
            'per_page' => $perPage,
        ]);

        $i = ($productos->currentPage() - 1) * $productos->perPage();

        return view('productos.index', compact('productos', 'i', 'search', 'perPage'));
    }


    public function create()
    {
        return view('productos.create');
    }

    public function store(ValidarStoreProducto $request)
    {
        $data = $request->only(['nombre', 'codigo', 'precio', 'cantidad']);
        DB::beginTransaction();
        try{
            //CREAR PRODUCTO
            $produ = Producto::create($data);

            $produ->nombre=$request->nombre;
            $produ->save();

            //ACTUALIZAR EL USUARIO AUTENTICAOD 
            $user = Auth::user();
            $user->name="ESPE";
            $user->save();

            // Auditoría: creación de producto
            Auditoria::create([
                'user_id' => $user ? $user->id : null,
                'accion' => 'crear',
                'tabla_afectada' => 'productos',
                'registro_id' => $produ->id,
                'cambios' => [
                    'nuevo' => $produ->toArray()
                ],
                'fecha_evento' => now(),
            ]);

            DB::commit();

            return redirect()->route('productos.index')
                             ->with('success', 'Producto creado exitosamente.');
        }catch(\Throwable $th){
            DB::rollBack();
            return redirect()->back()
                             ->withErrors(['error' => 'Error al crear el producto: ' . $th->getMessage()])
                             ->withInput();
        }
    }

    public function show(Producto $producto)
    {
        return view('productos.show', compact('producto'));
    }

    public function edit(Producto $producto)
    {
        return view('productos.edit', compact('producto'));
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'nombre' => 'required|unique:productos,nombre,' . $producto->id,
            'codigo' => 'required',
            'precio' => 'required|numeric',
            'cantidad' => 'required|integer',
        ]);

        $data = $request->only(['nombre', 'codigo', 'precio', 'cantidad']);
        $old = $producto->getOriginal();

        $producto->update($data);

        // Auditoría: actualización de producto
        $user = Auth::user();
        Auditoria::create([
            'user_id' => $user ? $user->id : null,
            'accion' => 'actualizar',
            'tabla_afectada' => 'productos',
            'registro_id' => $producto->id,
            'cambios' => [
                'antes' => $old,
                'despues' => $producto->toArray()
            ],
            'fecha_evento' => now(),
        ]);

        return redirect()->route('productos.index')
                         ->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Producto $producto)
    {
        $old = $producto->toArray();
        $producto->delete();

        // Auditoría: eliminación lógica de producto
        $user = Auth::user();
        Auditoria::create([
            'user_id' => $user ? $user->id : null,
            'accion' => 'eliminar',
            'tabla_afectada' => 'productos',
            'registro_id' => $producto->id,
            'cambios' => [
                'eliminado' => $old
            ],
            'fecha_evento' => now(),
        ]);

        return redirect()->route('productos.index')
                         ->with('success', 'Producto eliminado exitosamente.');
    }

    public function restoreView()
    {
        $productosEliminados = Producto::onlyTrashed()->paginate(5);
        return view('productos.restaure', compact('productosEliminados'));
    }

    public function restore($id)
    {
        $producto = Producto::onlyTrashed()->findOrFail($id);
        $old = $producto->toArray();
        $producto->restore();

        // Auditoría: restauración de producto
        $user = Auth::user();
        Auditoria::create([
            'user_id' => $user ? $user->id : null,
            'accion' => 'restaurar',
            'tabla_afectada' => 'productos',
            'registro_id' => $producto->id,
            'cambios' => [
                'restaurado' => $old
            ],
            'fecha_evento' => now(),
        ]);

        return redirect()->route('productos.index')
                        ->with('success', 'Producto restaurado exitosamente.');
    }

    public function forceDeleteView()
    {
        $productosEliminados = Producto::onlyTrashed()->paginate(5);
        return view('productos.forceDelete', compact('productosEliminados'));
    }

    public function forceDelete($id)
    {
        $producto = Producto::onlyTrashed()->findOrFail($id);
        $old = $producto->toArray();
        $producto->forceDelete();

        // Auditoría: eliminación permanente de producto
        $user = Auth::user();
        Auditoria::create([
            'user_id' => $user ? $user->id : null,
            'accion' => 'eliminar_permanente',
            'tabla_afectada' => 'productos',
            'registro_id' => $producto->id,
            'cambios' => [
                'eliminado_permanente' => $old
            ],
            'fecha_evento' => now(),
        ]);

        return redirect()->route('productos.restaure')
                         ->with('success', 'Producto eliminado permanentemente.');
    }

    public function eliminadoView()
    {
        $productosEliminados = Producto::onlyTrashed()->paginate(5);

        // Auditorías de productos eliminados permanentemente
        $auditorias = Auditoria::where('tabla_afectada', 'productos')
            ->where('accion', 'eliminar_permanente')
            ->orderBy('fecha_evento', 'desc')
            ->paginate(10);

        return view('productos.eliminado', compact('productosEliminados', 'auditorias'));
    }
}
