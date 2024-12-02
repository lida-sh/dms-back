<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\AuthController;
use \V1\Admin\ProcedureController;
use \V1\Admin\SubProcessController;
use \V1\Admin\ArchitectureController;
use \V1\Admin\ProcessController;

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
Route::group(['middleware' => ['auth:api']], function () {
Route::apiResource('/architectures', ArchitectureController::class);
Route::apiResource('/processes', ProcessController::class);
Route::apiResource('/sub-processes', SubProcessController::class);
Route::apiResource('/procedures', ProcedureController::class);
Route::get('/processes-details/{slug}', [App\Http\Controllers\V1\Admin\ProcessController::class, "showBySlug"] );
Route::get('/sub-processes-details/{slug}', [App\Http\Controllers\V1\Admin\SubProcessController::class, "showBySlug"] );
Route::get('/procedures-details/{slug}', [App\Http\Controllers\V1\Admin\ProcedureController::class, "showBySlug"] );
Route::get("/architectures/{architecture}/processes", [App\Http\Controllers\V1\Admin\ArchitectureController::class, "getProcessesOfArchitecture"] );
Route::get('/identity', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});



Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/refresh', [AuthController::class, 'refreshToken']);
// Route::group(['middleware' => ['auth:api']], function () {
    

// });