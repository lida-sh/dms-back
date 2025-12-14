<?php

use App\Mail\ResetPassword;
use App\User;
use Illuminate\Support\Facades\Route;




Route::get('/', function () {
    event(new \App\Events\TestBroadcast('Hello Reverb!'));
    return 'Laravel is working!!!!!!!!!!!!!';
});
Route::get('/pdf/{dir}/{file}', function ($file, $dir) {
    $path = public_path("storage/files/{$dir}/$file");
    if (!file_exists($path)) {
        return response()->json(['error' => 'File not found'], 404)
            ->header('Access-Control-Allow-Origin', '*');
    }
    $headers = [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept',
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'no-cache, must-revalidate',
    ];

    return response()->file($path, $headers);
    
})->name('pdf.view');

