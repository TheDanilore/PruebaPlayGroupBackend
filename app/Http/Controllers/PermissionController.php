<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use Illuminate\Http\Request;

//Exportar excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//Exportar en pdf
use Barryvdh\DomPDF\Facade\Pdf;

//Exportar en Word
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{

    public function index(Request $request)
    {
        // Establecer la cantidad de permisos por página, por defecto 15
        $perPage = $request->get('per_page', 5);

        // Obtener los permisos paginados
        $permisos = Permiso::paginate($perPage);

        // Retornar los permisos paginados en formato JSON
        return response()->json($permisos);
    }


    public function store(Request $request)
    {
        try {
            //mensajes personalizados
            $messages = [
                'name.required' => 'El nombre es obligatorio.',
                'name.unique' => 'El nombre ya esta en uso.',
                'name.min' => 'El nombre debe tener min 5 caracteres.',
            ];
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'required|unique:permissions,name|min:5',
            ], $messages);


            $permiso = Permission::create([
                'name' => $request->input('name'),
                'guard_name' => $request->input('guard_name'),
            ]);

            return response()->json($permiso, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear el permiso: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $permiso = PermisO::find($id);
            return response()->json($permiso);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el permiso: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            //mensajes personalizados
            $messages = [
                'name.required' => 'El nombre es obligatorio.',
                'name.min' => 'El nombre debe tener min 5 caracteres.',
            ];
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'nullable|min:5',
            ], $messages);


            $permiso = Permission::findOrFail($id);

            $permiso->name = $request->input('name');
            $permiso->guard_name = $request->input('guard_name');
            $permiso->save();

            return response()->json($permiso, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al actualizar el permiso: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

        try {
            Permiso::findOrFail($id)->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al eliminar el permiso: ' . $e->getMessage()], 500);
        }
    }


}
