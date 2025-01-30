<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

//Exportar excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//Exportar en pdf
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
//Exportar en Word
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class UsuarioController extends Controller
{

    public function index(Request $request)
    {
        // Establecer la cantidad --- por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener paginados
        $usuarios = User::with('roles')->paginate($perPage);

        // Retornar paginados en formato JSON
        return response()->json($usuarios->items());
    }



    public function store(Request $request)
    {
        try {
            Log::info('Datos recibidos:', $request->all());
            //mensajes personalizados
            $messages = [
                'name.required' => 'El apodo es obligatorio.',
                'name.unique' => 'El apodo ya está en uso.',
                'email.required' => 'El correo es obligatorio.',
                'email.unique' => 'El correo ya está en uso.',
                'password.required' => 'La contraseña es obligatoria.',
            ];
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|min:2',
                'email' => 'required|email|unique:users,email|min:10',
                'password' => 'required|same:password_confirmation|min:8',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'estado' => 'nullable|in:ACTIVO,INACTIVO',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,name'
            ], $messages);


            $input = $request->except('roles');
            $input['password'] = Hash::make($request->password);
            $input['estado'] = $input['estado'] ?? 'ACTIVO';


            //Para manejar la imagen
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $input['avatar'] = $avatarPath;
            }

            // Crear un nuevo usuario
            $user = User::create($input);

            // Asignar roles si se proporcionaron
            if ($request->has('roles')) {
                $roles = $request->input('roles');
                if (!empty($roles)) {
                    $user->syncRoles($roles);
                }
            }


            return response()->json($user->load('roles'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear un usuario: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $usuario = User::findOrFail($id);
            return response()->json($usuario);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el usuario: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            //mensajes personalizados
            $messages = [
                'name.required' => 'El apodo es obligatorio.',
                'email.required' => 'El correo es obligatorio.',
                'password.required' => 'La contraseña es obligatoria.',
                'roles.required' => 'Elegir el rol es obligatorio.',
            ];
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|min:2',
                'email' => 'required|email|exists:users,email|min:10',
                'password' => 'nullable|same:password_confirmation|min:8',
                'roles' => '',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'estado' => 'required',
            ], $messages);

            $user = User::find($id);
            $input = $validatedData;

            if (!empty($input['password'])) {
                $input['password'] = Hash::make($input['password']);
            } else {
                $input = Arr::except($input, ['password']);
            }

            //Para manejar la imagen
            if ($request->hasFile('avatar')) {
                // Eliminar el avatar anterior si existe
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $input['avatar'] = $avatarPath;
            }

            $user->update($input);

            DB::table('model_has_roles')->where('model_id', $id)->delete();

            // Asignar roles si se proporcionaron
            if ($request->has('roles')) {
                $roles = $request->input('roles');
                if (!empty($roles)) {
                    $user->syncRoles($roles);
                }
            }

            return response()->json($user->load('roles'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al actualizar un usuario: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al eliminar el usuario: ' . $e->getMessage()], 500);
        }
    }

}
