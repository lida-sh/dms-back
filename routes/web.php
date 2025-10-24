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
// Route::get('/test-soketi', function () {
//     $host = env('PUSHER_HOST', '127.0.0.1');
//     $port = env('PUSHER_PORT', 6001);
//     $connected = @fsockopen($host, $port, $errno, $errstr, 1);
//     if ($connected) {
//         fclose($connected);
//         return "✅ Laravel can connect to Soketi at {$host}:{$port}";
//     } else {
//         return "❌ Laravel cannot connect to Soketi at {$host}:{$port} — $errstr ($errno)";
//     }
// });


Route::get('/', function () {
    event(new \App\Events\TestBroadcast('Hello Reverb!'));
    return 'Laravel is working!';
});

