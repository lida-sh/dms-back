<?php

use App\Mail\ResetPassword;
use App\User;
use Illuminate\Support\Facades\Route;
<<<<<<< HEAD

=======
use App\Http\Controllers\V1\FileSearchController3;
use App\Http\Controllers\V1\FileSearchController4;
use App\Events\TestOcrEvent;
use App\Events\TestBroadcast;
use Illuminate\Support\Facades\Cache;
>>>>>>> 873a1e90d14db84a497bd8087330c6a47e18d704
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

