<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Laravel\Passport\Bridge\User;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response as Psr7Response;

class AccessTokenController
{
    use HandlesOAuthErrors;

    /**
     * @var AuthorizationServer
     */
    protected $server;

    /**
     * Create a new controller instance.
     *
     * @param  AuthorizationServer  $server
     * @return void
     */
    public function __construct(AuthorizationServer $server)
    {
        $this->server = $server;
    }

    /**
     * Issue an access token to the user.
     *
     * @param  ServerRequestInterface  $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function issueToken(ServerRequestInterface $request)
    {
        return $this->withErrorHandling(function () use ($request) {
            return $this->server->respondToAccessTokenRequest($request, new Psr7Response);
        });
    }
}
