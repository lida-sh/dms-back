<?php

use App\Mail\ResetPassword;
use App\User;
use Illuminate\Support\Facades\Route;




Route::get('/', function () {
    event(new \App\Events\TestBroadcast('Hello Reverb!'));
    return 'Laravel is working!';
});

