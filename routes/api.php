<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoriaProductoController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\LongitudController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\TamanoController;
use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::resource('categoriaproductos', CategoriaProductoController::class)->only(['index', 'store', 'update', 'show', 'destroy']);
Route::get('productos/searchByName', [ProductoController::class, 'searchByName']);

Route::resource('productos', ProductoController::class)->only(['index', 'store', 'update', 'show', 'destroy']);
Route::resource('colores', ColorController::class)->only(['index', 'store', 'update', 'show', 'destroy']);
Route::resource('longitudes', LongitudController::class)->only(['index', 'store', 'update', 'show', 'destroy']);
Route::resource('tamanos', TamanoController::class)->only(['index', 'store', 'update', 'show', 'destroy']);


Route::get('inventario/producto/{productoId}', [InventarioController::class, 'getInventarioByProducto']);
Route::get('inventario/producto/{productoId}/resumen', [InventarioController::class, 'getInventarioResumenByProducto']);
Route::get('inventario', [InventarioController::class, 'index']);
Route::resource('unidadesmedidas', UnidadMedidaController::class)->only(['index', 'store', 'update', 'show', 'destroy']);
Route::resource('ubicaciones', UbicacionController::class)->only(['index', 'store', 'update', 'show', 'destroy']);
Route::resource('proveedores', ProveedorController::class)->only(['index', 'store', 'update', 'show', 'destroy']);

//Gestion de Usuarios
Route::resource('usuarios', UsuarioController::class)->only(['index', 'store', 'update', 'show', 'destroy']);
Route::resource('roles', RolController::class)->only(['index', 'store', 'update', 'show', 'destroy']);
Route::resource('permissions', PermissionController::class)->only(['index', 'store', 'update', 'show', 'destroy']);


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('user-profile', [AuthController::class, 'userProfile']);
    Route::post('logout', [AuthController::class, 'logout']);
});
