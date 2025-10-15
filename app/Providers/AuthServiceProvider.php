<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use Laravel\Passport\Bridge\UserRepository;
use Laravel\Passport\Bridge\ClientRepository;
use Laravel\Passport\Bridge\ScopeRepository;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\AuthorizationServer;
use DateInterval;
use Illuminate\Support\Facades\App;
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        // Passport::routes();
        $server = app(AuthorizationServer::class);
        $grant = new PasswordGrant(
            app(UserRepository::class),
            app(RefreshTokenRepository::class)
        );
        $grant->setRefreshTokenTTL(new DateInterval('P1M')); // Refresh token valid for 1 month

        $server->enableGrantType(
            $grant,
            new DateInterval('P1Y') // Access token valid for 1 year
        );
        // عمر توکن‌ها (اختیاری)
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // Passport::enableImplicitGrant();
        //
    }
}
