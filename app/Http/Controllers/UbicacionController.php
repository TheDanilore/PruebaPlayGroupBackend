<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use Illuminate\Http\Request;

//Exportar excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//Exportar en pdf
use Barryvdh\DomPDF\Facade\Pdf;

//Exportar en Word
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class UbicacionController extends Controller
{

    public function index(Request $request)
    {
        // Establecer la cantidad --- por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener paginados
        $ubicaciones = Ubicacion::paginate($perPage);

        // Retornar paginados en formato JSON
        return response()->json($ubicaciones->items());  // Solo devuelve los items
    }


    public function store(Request $request)
    {
        try {
            ///mensajes personalizados
            $messages = [
                'codigo.required' => 'El código es obligatorio.',
                'codigo.min' => 'El código debe contener min 5 caracteres.',
                'descripcion.min' => 'El nombre debe contener min 8 caracteres.',
                'descripcion.required' => 'El nombre es obligatorio.',
            ];
            $validatedData = $request->validate([
                'codigo' => 'required|string|max:100|min:5',
                'descripcion' => 'required|string|max:100|min:8',
            ], $messages);

            $ubicaciones = Ubicacion::create($validatedData);
            return response()->json($ubicaciones, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear la ubicacion: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $ubicacion = Ubicacion::with('audit.usercreated', 'audit.userupdated')->find($id);
            return response()->json($ubicacion);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar la Ubicación: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $ubicacion = Ubicacion::findOrFail($id);
            //mensajes personalizados
            $messages = [
                'codigo.required' => 'El código es obligatorio.',
                'codigo.min' => 'El código debe contener min 5 caracteres.',
                'descripcion.min' => 'El nombre debe contener min 8 caracteres.',
                'descripcion.required' => 'El nombre es obligatorio.',
            ];
            $validatedData = $request->validate(
                [
                    'codigo' => 'required|string|max:100|min:5',
                    'descripcion' => 'required|string|max:100|min:8',
                ],
                $messages
            );

            $ubicacion->update($validatedData);
            return response()->json($ubicacion);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al editar la ubicacion: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $ubicacion = Ubicacion::findOrFail($id);
            $ubicacion->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al eliminar la ubicacion: ' . $e->getMessage()], 500);
        }
    }

}
