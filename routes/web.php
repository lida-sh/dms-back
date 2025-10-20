<?php

use App\Mail\ResetPassword;
use App\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//FileSearchController
Route::get('/test-laravel', function() {
    error_log("Laravel route reached!");
    return "Laravel route works!";
});
Route::get('/', function () {
    return 'Laravel is working!';
});

