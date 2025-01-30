<?php

namespace App\Http\Controllers;

use App\Models\Imagen;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Variacion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    //Mostrar todos los productos
    // public function index(Request $request)
    // {
    //     $perPage = min($request->get('per_page', 10), 100); // Paginación con un valor máximo de 10

    //     $query = Producto::with(['imagen', 'inventario'])
    //         ->leftJoin('inventario', 'producto.id', '=', 'inventario.producto_id')
    //         ->select('producto.id', 'producto.nombre', 'producto.descripcion', 'producto.categoria_producto_id', 'producto.estado') // Agrega aquí las columnas necesarias
    //         ->selectRaw('COALESCE(SUM(inventario.cantidad), 0) as total_stock')
    //         ->selectRaw('COALESCE(MAX(inventario.precio_unitario), 0) as precio_unitario_maximo')
    //         ->groupBy('producto.id', 'producto.nombre', 'producto.descripcion', 'producto.categoria_producto_id', 'producto.estado'); // Agregar todas las columnas seleccionadas


    //     // Filtrar por categoría
    //     if ($request->has('categoria')) {
    //         $query->where('producto.categoria_producto_id', $request->get('categoria'));
    //     }

    //     // Filtrar por estado
    //     if ($request->has('estado')) {
    //         $query->where('producto.estado', $request->get('estado'));
    //     }


    //     // Obtener los productos con paginación
    //     $productos = $query->paginate($perPage);

    //     return response()->json($productos);
    // }

    public function index(Request $request)
    {
        // Establecer la cantidad --- por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener productos con las relaciones cargadas
        $productos = Producto::with([
            'inventario',
            'proveedor',
            'categoria',
            'unidadMedida',
            'ubicacion',
            'imagenes'
        ])->paginate($perPage);

        // Retornar los productos con las relaciones en formato JSON
        return response()->json($productos);
    }

    // Mostrar un producto específico
    public function show($id)
    {
        try {
            $producto = Producto::with([
                'proveedor',
                'categoria',
                'unidadMedida',
                'imagenes',
                'ubicacion',
                'inventario.variacion.color',    // Incluir la relación color a través de inventario
                'inventario.variacion.tamano',   // Incluir la relación tamaño a través de inventario
                'inventario.variacion.longitud'  // Incluir la relación longitud a través de inventario
            ])->findOrFail($id);

            // Calcular el stock total y el precio
            $stock_total = $producto->inventario->sum('cantidad');
            $precio_base = $producto->inventario->min('precio_unitario');

            $productoTransformado = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'precio_unitario' => $precio_base,
                'stock' => $stock_total,
                'imagenes' => $producto->imagenes->map(function ($imagen) {
                    return [
                        'url' => $imagen->url,
                        'id' => $imagen->id
                    ];
                }),
                'colores' => $producto->inventario->pluck('variacion.color')->unique(),
                'tamanos' => $producto->inventario->pluck('variacion.tamano')->unique(),
                'longitudes' => $producto->inventario->pluck('variacion.longitud')->unique(),
                // Incluir el inventario completo para validaciones posteriores
                'inventario' => $producto->inventario->map(function ($inv) {
                    return [
                        'id' => $inv->id,
                        'variacion_id' => $inv->variacion->id,
                        'cantidad' => $inv->cantidad,
                        'precio_unitario' => $inv->precio_unitario
                    ];
                })
            ];

            return response()->json($productoTransformado);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el producto'], 500);
        }
    }

    public function searchByName(Request $request)
    {
        try {
            $query = $request->query('q');

            if (empty($query)) {
                return response()->json([]);
            }

            $products = Producto::where('nombre', 'like', '%' . $query . '%')
                ->with(['imagenes' => function ($query) {
                    $query->select('id', 'producto_id', 'url');
                }])
                ->select('id', 'nombre')
                ->whereHas('inventario', function ($query) {
                    $query->where('cantidad', '>', 0);
                })
                ->take(10)
                ->get();

            $transformedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'nombre' => $product->nombre,
                    'imagen' => $product->imagenes->first() ? $product->imagenes->first()->url : null
                ];
            });

            return response()->json($transformedProducts);
        } catch (\Exception $e) {
            Log::error('Error en búsqueda: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function storeImages($producto, $imagenes)
    {
        foreach ($imagenes as $imagen) {
            $url = $imagen->store('public/productos');
            $url = str_replace('public/', 'storage/', $url);

            Imagen::create([
                'producto_id' => $producto->id,
                'url' => $url,
                'alt_text' => $producto->nombre,
            ]);
        }
    }

    // Crear un nuevo producto
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            // Mensajes personalizados
            $messages = [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'El nombre ya está en uso.',
                'nombre.min' => 'El nombre debe tener al menos 4 caracteres.',
                'descripcion.required' => 'La descripción es obligatoria.',
                'descripcion.min' => 'La descripción debe tener al menos 5 caracteres.',
                'categoria_producto_id.required' => 'Elige una categoría.',
                'unidad_medida_id.required' => 'Elige una unidad de medida.',
                'proveedor_id.required' => 'Elegir El proveeodor es obligatorio',
                'ubicacion_id_id.required' => 'La ubicación es obligatoria.',
            ];

            // Validar los datos del formulario
            $request->validate([
                'nombre' => [
                    'required',
                    'string',
                    'max:100',
                    'min:4',
                    Rule::unique('producto'), // Validación de unicidad
                ],
                'descripcion' => 'required|string|max:255|min:5',
                'categoria_producto_id' => 'required|integer|exists:categoria_producto,id',
                'unidad_medida_id' => 'required|integer|exists:unidad_medida,id',
                'proveedor_id' => 'required|integer|exists:proveedor,id',
                'ubicacion_id' => 'nullable|exists:ubicacion,id',
                'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validar cada imagen
                'variantes' => 'required|array|min:1',
                'variantes.*.color_id' => 'nullable|exists:color,id',
                'variantes.*.tamano_id' => 'nullable|exists:tamano,id',
                'variantes.*.longitud_id' => 'nullable|exists:longitud,id',
                'variantes.*.precio_unitario' => 'required|numeric|min:0',
                'variantes.*.cantidad' => 'required|integer|min:1',
            ], $messages);

            // Crear el producto
            $producto = Producto::create($request->only([
                'nombre',
                'descripcion',
                'proveedor_id',
                'categoria_producto_id',
                'unidad_medida_id',
                'ubicacion_id',
                'estado' => $request->input('estado', 'ACTIVO')
            ]));

            // Procesar y guardar las variantes
            $variantes = $request->variantes;

            //Crear las variaciones en caso no existan en la base de datos
            //o si existen obtener la variacion, (por ejemple color=negro,tamano=L)
            //Otro ejemplo color=azul,tamano=null,longitud=null)

            foreach ($variantes as $variante) {
                try {
                    $variacion = Variacion::firstOrCreate([
                        'color_id' => $variante['color_id'],
                        'tamano_id' => $variante['tamano_id'],
                        'longitud_id' => $variante['longitud_id']
                    ]);

                    Inventario::create([
                        'producto_id' => $producto->id,
                        'variacion_id' => $variacion->id,
                        'precio_unitario' => $variante['precio_unitario'],
                        'cantidad' => $variante['cantidad']
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception("Error en variante: " . json_encode($variante) . " - " . $e->getMessage());
                }
            }

            if ($request->hasFile('imagenes')) {
                $this->storeImages($producto, $request->file('imagenes'));
            }

            DB::commit();
            return response()->json($producto, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en store: ' . $e->getMessage());

            return response()->json([
                'error' => 'Ocurrió un error al crear el producto.',
                'detalle' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ], 500);
        }
    }

    protected function deleteImages($imagenesAEliminar)
    {
        foreach ($imagenesAEliminar as $imagenId) {
            $imagen = Imagen::find($imagenId);
            if ($imagen) {
                // Eliminar el archivo de imagen del almacenamiento
                Log::info('Eliminando imagen de:', ['ruta' => $imagen->url]);

                Storage::delete(str_replace('storage/', 'public/', $imagen->url));
                // Elimina la imagen de la base de datos
                $imagen->delete();
            } else {
                // Log para imágenes no encontradas
                Log::warning('Imagen no encontrada:', ['id' => $imagenId]);
            }
        }
        Log::info('Imágenes a eliminar:', $imagenesAEliminar);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            Log::info('Datos recibidos:', $request->all());
            $producto = Producto::findOrFail($id);

            if (!$producto) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }

            // Mensajes personalizados
            $messages = [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'El nombre ya está en uso.',
                'nombre.min' => 'El nombre debe tener al menos 4 caracteres.',
                'descripcion.required' => 'La descripción es obligatoria.',
                'descripcion.min' => 'La descripción debe tener al menos 5 caracteres.',
                'categoria_producto_id.required' => 'Elige una categoría.',
                'unidad_medida_id.required' => 'Elige una unidad de medida.',
                'proveedor_id.required' => 'El proveedor es obligatorio',
                'ubicacion_id.required' => 'La ubicación es obligatoria.',
                'imagenes.*.image' => 'Cada archivo debe ser una imagen.',
                'imagenes.*.mimes' => 'Cada imagen debe ser de tipo jpg, jpeg o png.',
                'imagenes.*.max' => 'Cada imagen no debe superar los 2MB.',
            ];

            // Validar los datos del formulario
            $validated = $request->validate([
                'nombre' => 'nullable|string|max:100|min:4',
                'descripcion' => 'nullable|string|max:255|min:5',
                'categoria_producto_id' => 'nullable|integer|exists:categoria_productos,id',
                'unidad_medida_id' => 'nullable|integer|exists:unidad_medidas,id',
                'proveedor_id' => 'nullable|integer|exists:proveedores,id',
                'ubicacion_id' => 'nullable|exists:ubicaciones,id',
                'estado' => 'required',
                'imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Valida cada imagen en el array
                'variantes' => 'required|json',
                'variantes.*.color_id' => 'required|integer',
                'variantes.*.tamano_id' => 'nullable|integer',
                'variantes.*.longitud_id' => 'nullable|integer',
                'variantes.*.precio_unitario' => 'required|numeric',
                'variantes.*.cantidad' => 'required|integer|min:0',
            ], $messages);

            // Actualizar producto
            $producto->update($request->except('imagenes', 'imagenes_a_eliminar', 'variantes'));

            // Procesar las variantes
            $variantes = json_decode($request->input('variantes'), true);

            foreach ($variantes as $varianteData) {
                // Validar que los campos requeridos de variante están presentes
                if (!isset($varianteData['color_id'], $varianteData['cantidad'], $varianteData['precio_unitario'])) {
                    throw new \InvalidArgumentException('Faltan datos en variante.');
                }

                // Obtener los valores, permitiendo que sean null
                $color_id = $varianteData['color_id'];
                $tamano_id = $varianteData['tamano_id'] ?? null;
                $longitud_id = $varianteData['longitud_id'] ?? null;
                $cantidad = $varianteData['cantidad'];
                $precio_unitario = $varianteData['precio_unitario'];

                $variacion = Variacion::firstOrCreate([
                    'color_id' => $color_id,
                    'tamano_id' => $tamano_id,
                    'longitud_id' => $longitud_id
                ]);

                $inventarioExistente = Inventario::where([
                    ['producto_id', '=', $producto->id],
                    ['variacion_id', '=', $variacion->id],
                ])->first();

                if ($inventarioExistente) {
                    // Revisar cambio en cantidad
                    $diferenciaCantidad = $varianteData['cantidad'] - $inventarioExistente->cantidad;
                    $inventarioExistente->precio_unitario = $precio_unitario;
                    Log::info("Precio unitario antes de guardar: " . $inventarioExistente->precio_unitario);
                    $inventarioExistente->save();
                    if ($diferenciaCantidad != 0) {
                        // Actualizar inventario y crear movimiento
                        $inventarioExistente->cantidad = $varianteData['cantidad'];
                        $inventarioExistente->save();
                    }
                } else {
                    // Crear nuevo inventario
                    Inventario::create([
                        'producto_id' => $producto->id,
                        'variacion_id' => $variacion->id,
                        'precio_unitario' => $varianteData['precio_unitario'] ?? 0, // Asume 0 si no se proporciona
                        'cantidad' => $varianteData['cantidad'],
                    ]);
                }
            }

            // Eliminar imágenes si hay IDs
            if ($request->has('imagenes_a_eliminar')) {
                $this->deleteImages($request->input('imagenes_a_eliminar'));
            }

            // Si se han subido nuevas imágenes, manejarlas
            if ($request->hasFile('imagenes')) {
                // Guarda las nuevas imágenes
                foreach ($request->file('imagenes') as $imagen) {
                    $url = $imagen->store('public/productos'); // Almacena la imagen y obtiene la ruta
                    $url = str_replace('public/', 'storage/', $url); // Ajusta la URL para que sea accesible públicamente
                    // Crea un nuevo registro de imagen
                    $producto->imagenes()->create([
                        'url' => $url, // Aquí pasas el campo 'url'
                        'alt_text' => $producto->nombre // Opcional, solo si deseas añadir
                    ]);
                }
            }

            DB::commit();
            return response()->json($producto);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar el producto:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'No se pudo actualizar el producto', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            // Buscar el producto
            $producto = Producto::findOrFail($id);

            // Solo llamar a deleteImages si el producto tiene imágenes
            if ($producto->imagenes && $producto->imagenes->isNotEmpty()) {
                try {
                    // Eliminar imágenes asociadas en el almacenamiento y en la base de datos
                    $this->deleteImages($producto->imagenes->pluck('id')->toArray());
                } catch (\Exception $e) {
                    Log::error('Error al eliminar las imagenes asociadas al producto: ' . $e->getMessage());
                    throw new \Exception('Error al eliminar las imagenes asociadas al producto: ' . $e->getMessage());
                }
            } else {
                Log::info('El producto no tiene imágenes asociadas');
            }

            // Eliminar inventario relacionado al producto
            $inventario = Inventario::where('producto_id', $producto->id);

            if ($inventario) {
                $inventario->delete();
            }

            $producto->delete();

            DB::commit();
            return response()->json(
                null,
                204
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                ['error' => 'Ocurrió un error al eliminar el producto: ' . $e->getMessage()],
                500
            );
        }
    }
}
