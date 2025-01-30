<?php

namespace App\Http\Controllers;

use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

//Exportar excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//Exportar en pdf
use Barryvdh\DomPDF\Facade\Pdf;

//Exportar en Word
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class UnidadMedidaController extends Controller
{

    /*     function __construct()
    {
        $this->middleware('permission:lista-unidadmedida|ver-unidadmedida|crear-unidadmedida|editar-unidadmedida|borrar-unidadmedida', ['only' => ['index']]);
        $this->middleware('permission:crear-unidadmedida', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-unidadmedida', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-unidadmedida', ['only' => ['destroy']]);
        $this->middleware('permission:ver-unidadmedida', ['only' => ['show']]);
    } */

    public function index(Request $request)
    {
        // Establecer la cantidad --- por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener paginados
        $unidadmedida = UnidadMedida::paginate($perPage);

        // Retornar paginados en formato JSON
        return response()->json($unidadmedida->items());  // Solo devuelve los items
    }

    public function store(Request $request)
    {
        try {
            //mensajes personalizados
            $messages = [
                'descripcion.required' => 'La descripcion es obligatorio.',
                'descripcion.unique' => 'La descripcion ya está en uso.',
                'abreviatura.required' => 'La abreviacion es obligatorio.',
                'abreviatura.unique' => 'La abreviacion ya está en uso.',
            ];

            // Validar los datos del formulario
            $validatedData = $request->validate([
                'descripcion' => [
                    'required',
                    'string',
                    'max:50',
                    'min:5',
                    Rule::unique('unidad_medida'), // Validación de unicidad
                ],
                'abreviatura' => [
                    'required',
                    'string',
                    'max:4',
                    'min:1',
                    Rule::unique('unidad_medida'), // Validación de unicidad
                ],
            ], $messages);

            $unidadmedida = UnidadMedida::create($validatedData);

            return response()->json($unidadmedida, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear la unidad de medida: ' . $e->getMessage()], 500);
        }
    }


    // Mostrar una categoría específica
    public function show($id)
    {
        $unidadmedida = UnidadMedida::findOrFail($id);
        return response()->json($unidadmedida);
    }


    public function update(Request $request, $id)
    {
        try {
            //mensajes personalizados
            $messages = [
                'descripcion.required' => 'La descripcion es obligatorio.',
                'abreviatura.required' => 'La abreviacion es obligatorio.',
            ];

            // Validar los datos del formulario
            $validatedData = $request->validate([
                'descripcion' => [
                    'nullable',
                    'string',
                    'max:50',
                    'min:5',

                ],
                'abreviatura' => [
                    'nullable',
                    'string',
                    'max:4',
                    'min:1',

                ],
            ], $messages);

            $unidadmedida = UnidadMedida::findOrFail($id);
            $unidadmedida->update($validatedData);

            return response()->json($unidadmedida, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al actualizar la unidad de medida: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $unidadmedi = UnidadMedida::findOrFail($id);
            $unidadmedi->delete();

            // Devolver una respuesta adecuada para confirmar la eliminación
            //return response()->json(null, 204);
            return response()->json(['message' => 'Unidad medida eliminado']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al eliminar la unidad de medida: ' . $e->getMessage()], 500);
        }
    }

}
