<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        
        Schema::defaultStringLength(191);
        validator::extend('bpm', function ($attribute, $value, $parameters, $validator) {
            return $value->getClientOriginalExtension() === 'bpm';
        });
        Passport::routes();
        Passport::tokensExpireIn(now()->addMinutes(1));
        Passport::refreshTokensExpireIn(now()->addHours(5));
       
       

    }
}
