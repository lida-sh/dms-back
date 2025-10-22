<?php

use App\Mail\ResetPassword;
use App\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\FileSearchController3;
use App\Http\Controllers\V1\FileSearchController4;
use App\Events\TestOcrEvent;
use App\Events\TestBroadcast;
use Illuminate\Support\Facades\Cache;
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
Route::get('/send', function () {
    event(new TestBroadcast('سلام از لاراول 🚀'));
    return 'Event Sent!';
});
Route::get('/test-broadcast', function () {
    $testData = [
        'files' => ['test1.pdf', 'test2.pdf'],
        'pages' => [1, 2, 3],
        'status' => 'completed'
    ];
    
    // event(new TestOcrEvent('Hello from Laravel Broadcast!', $testData));
    
    return response()->json([
        'status' => 'success',
        'message' => 'Broadcast sent!'
    ]);
});

Route::get('/test-soketi', function () {
    $host = env('PUSHER_HOST', '127.0.0.1');
    $port = env('PUSHER_PORT', 6001);
    $connected = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($connected) {
        fclose($connected);
        return "✅ Laravel can connect to Soketi at {$host}:{$port}";
    } else {
        return "❌ Laravel cannot connect to Soketi at {$host}:{$port} — $errstr ($errno)";
    }
});
 
