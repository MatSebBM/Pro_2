<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validate = $request->validate([
            'perPage' => 'integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
        ]);

        $perPage = $request->query('perPage', 10);
        $search = $request->query('search');

        $query = User::select(
            'id',
            'name',
            'email',
            'created_at',
            'updated_at'
        );

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $users = $query->paginate($perPage)->appends($request->query());

        $data = [
            'users' => $users,
            'perPage' => $perPage,
            'total' => $users->total(),
            'search' => $search,
        ];

        return view('users.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->only(['name', 'email']);
            $data['password'] = bcrypt($request->password);

            $user = User::create($data);

            // Auditoría: creación de usuario
            $authUser = Auth::user();
            Auditoria::create([
                'user_id' => $authUser ? $authUser->id : null,
                'accion' => 'crear',
                'tabla_afectada' => 'users',
                'registro_id' => $user->id,
                'cambios' => [
                    'nuevo' => $user->toArray()
                ],
                'fecha_evento' => now(),
            ]);

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', 'Usuario creado exitosamente.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Error al crear el usuario: ' . $th->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $data = $request->only(['name', 'email']);
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }
        $old = $user->getOriginal();

        $user->update($data);

        // Auditoría: actualización de usuario
        $authUser = Auth::user();
        Auditoria::create([
            'user_id' => $authUser ? $authUser->id : null,
            'accion' => 'actualizar',
            'tabla_afectada' => 'users',
            'registro_id' => $user->id,
            'cambios' => [
                'antes' => $old,
                'despues' => $user->toArray()
            ],
            'fecha_evento' => now(),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $old = $user->toArray();
        $user->delete();

        // Auditoría: eliminación lógica de usuario
        $authUser = Auth::user();
        Auditoria::create([
            'user_id' => $authUser ? $authUser->id : null,
            'accion' => 'eliminar',
            'tabla_afectada' => 'users',
            'registro_id' => $user->id,
            'cambios' => [
                'eliminado' => $old
            ],
            'fecha_evento' => now(),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }

    public function restoreView()
    {
        $usuariosEliminados = User::onlyTrashed()->paginate(5);
        return view('users.restaure', compact('usuariosEliminados'));
    }

    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $old = $user->toArray();
        $user->restore();

        // Auditoría: restauración de usuario
        $authUser = Auth::user();
        Auditoria::create([
            'user_id' => $authUser ? $authUser->id : null,
            'accion' => 'restaurar',
            'tabla_afectada' => 'users',
            'registro_id' => $user->id,
            'cambios' => [
                'restaurado' => $old
            ],
            'fecha_evento' => now(),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Usuario restaurado exitosamente.');
    }

    public function forceDeleteView()
    {
        $usuariosEliminados = User::onlyTrashed()->paginate(5);
        return view('users.forceDelete', compact('usuariosEliminados'));
    }

    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $old = $user->toArray();
        $user->forceDelete();

        // Auditoría: eliminación permanente de usuario
        $authUser = Auth::user();
        Auditoria::create([
            'user_id' => $authUser ? $authUser->id : null,
            'accion' => 'eliminar_permanente',
            'tabla_afectada' => 'users',
            'registro_id' => $user->id,
            'cambios' => [
                'eliminado_permanente' => $old
            ],
            'fecha_evento' => now(),
        ]);

        return redirect()->route('users.restaure')
            ->with('success', 'Usuario eliminado permanentemente.');
    }

    public function eliminadoView()
    {
        $usuariosEliminados = User::onlyTrashed()->paginate(5);

        // Auditorías de usuarios eliminados permanentemente
        $auditorias = Auditoria::where('tabla_afectada', 'users')
            ->where('accion', 'eliminar_permanente')
            ->orderBy('fecha_evento', 'desc')
            ->paginate(10);

        return view('users.eliminado', compact('usuariosEliminados', 'auditorias'));
    }
}
