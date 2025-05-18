<?php


use App\Http\Controllers\V1\ArchitectureClientController;
use App\Http\Controllers\V1\ProcedureClientController;
use App\Http\Controllers\V1\ProcessClientController;
use App\Http\Controllers\V1\SearchController;
use App\Http\Controllers\V1\SubProcessClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\AuthController;
use \V1\Admin\ProcedureController;

use \V1\Admin\SubProcessController;
use \V1\Admin\ArchitectureController;
use \V1\Admin\ProcessController;
use \V1\Admin\UserController;
use \V1\Admin\RoleController;
use \V1\Admin\PermissionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();php
// });
Route::group(['prefix' => 'admin','middleware' => ['auth:api']], function () {
    Route::apiResource('/architectures', ArchitectureController::class);
    Route::apiResource('/processes', ProcessController::class);
    Route::apiResource('/sub-processes', SubProcessController::class);
    Route::apiResource('/procedures', ProcedureController::class);
    Route::apiResource('/users', UserController::class);
    Route::apiResource('/permissions', PermissionController::class);
    Route::apiResource('/roles', RoleController::class);
    Route::get('/processes-details/{slug}', [App\Http\Controllers\V1\Admin\ProcessController::class, "showBySlug"]);
    Route::get('/sub-processes-details/{slug}', [App\Http\Controllers\V1\Admin\SubProcessController::class, "showBySlug"]);
    Route::get('/procedures-details/{slug}', [App\Http\Controllers\V1\Admin\ProcedureController::class, "showBySlug"]);
    Route::get("/architectures/{architecture}/processes", [App\Http\Controllers\V1\Admin\ArchitectureController::class, "getProcessesOfArchitecture"]);
    Route::get('/identity', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/get-roles-permissions', [App\Http\Controllers\V1\Admin\UserController::class, "getRolesAndPermissions"]);
    Route::get('/get-architectures', [App\Http\Controllers\V1\Admin\ArchitectureController::class, "getArchitectures"]);
});



Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/refresh', [AuthController::class, 'refreshToken']);



Route::get('/architectures', [SearchController::class, 'getArchitectures']);
Route::get("/architectures/{architecture}/processes", [SearchController::class, 'getProcessesOfArchitecture']);
Route::get("/advanced-search", [SearchController::class, 'doAdvancedSearch']);
Route::get("/search", [SearchController::class, 'doSearch']);
Route::get('/procedures', [ProcedureClientController::class, 'index']);
Route::get('/processes', [ProcessClientController::class, 'index']);
Route::get('/sub-processes', [SubProcessClientController::class, 'index']);
Route::get('/procedures-details/{slug}', [ProcedureClientController::class, "showBySlug"]);
Route::get('/sub-processes-details/{slug}', [SubProcessClientController::class, "showBySlug"]);
Route::get('/processes-details/{slug}', [ProcessClientController::class, "showBySlug"]);
Route::get('/architectures/{slug}', [ArchitectureClientController::class, "getTreeStructure"]);