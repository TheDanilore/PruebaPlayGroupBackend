<?php

namespace App\Http\Controllers;

use App\Models\EstadoRol;
use App\Models\Rol;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

//Exportar excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//Exportar en pdf
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
//Exportar en Word
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class RolController extends Controller
{

    public function index(Request $request)
    {
        // Establecer la cantidad de productos por página, por defecto 15
        $perPage = $request->get('per_page', 5);

        // Obtener los productos paginados
        $roles = Role::with('permissions')->paginate($perPage);

        // Retornar los productos paginados en formato JSON
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        try {
            //mensajes personalizados
            $messages = [
                'name.required' => 'El nombre es obligatorio.',
                'permissions.required' => 'Elegir un permiso al menos es obligatorio.',
                'name.unique' => 'El nombre ya esta en uso.',
            ];

            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'required|unique:roles,name',
                'permissions' => 'required|array',
            ], $messages);


            $role = Role::create([
                'name' => $request->input('name'),
                'guard_name' => $request->input('guard_name'),
                'estado' => $request->input('estado', 'ACTIVO'), // Asigna 'Activo' si no se proporciona estado
            ]);

            // Asigna los permisos al rol
            $role->syncPermissions($request->input('permissions'));

            return response()->json(['message' => 'Rol creado exitosamente.', 'rol' => $role], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el rol.', 'error' => $e->getMessage()], 500);
        }
    }


    public function show($id)
    {
        try {
            $rol = Rol::with('estadorol', 'audit.usercreated', 'audit.userupdated')->find($id);
            return response()->json($rol);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el rol: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            ///mensajes personalizados
            $messages = [
                'name.required' => 'El nombre es obligatorio.',
                'permissions.required' => 'Elegir un permiso al menos es obligatorio.',
                'estado.required' => 'Elegir estado es obligatorio.',
            ];
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'required',
                'permissions' => 'required|array', // Cambiado de 'permission' a 'permissions'
                'estado' => 'required',
            ], $messages);

            $role = Role::findOrFail($id);

            $role->name = $request->input('name');
            $role->guard_name = $request->input('guard_name');
            $role->estado = $request->input('estado');
            $role->save();

            // Actualizado para usar 'permissions' en lugar de 'permission'
            $role->syncPermissions($request->input('permissions'));

            return response()->json($role);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al actualizar el rol: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            DB::table('roles')->where('id', $id)->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al eliminar el rol: ' . $e->getMessage()], 500);
        }
    }

}
