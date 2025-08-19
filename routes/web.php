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

Route::get('/', function () {
    $user = User::find(2);
    
    $token = DB::table("password_reset_tokens")->where('email', $user->email)->first()->token;
    return new ResetPassword($token, $user->email);
});
