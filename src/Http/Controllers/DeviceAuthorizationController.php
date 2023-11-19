<?php

namespace Laravel\Passport\Http\Controllers;

use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Http\Request;
use Nyholm\Psr7\Response as Psr7Response;

class DeviceAuthorizationController
{
    use HandlesOAuthErrors;

    /**
     * The authorization server.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * Create a new controller instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @return void
     */
    public function __construct(AuthorizationServer $server)
    {
        $this->server = $server;
    }

    /**
     * Issue a device its device codes.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return \Illuminate\Http\Response
     */
    public function issueDeviceCode(ServerRequestInterface $request)
    {
        return $this->withErrorHandling(function () use ($request) {
            return $this->convertResponse(
                $this->server->respondToDeviceAuthorizationRequest($request, new Psr7Response)
            );
        });
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authorize(Request $request)
    {
        return $this->withErrorHandling(function () use ($request) {
            $this->server->completeDeviceAuthorizationRequest($request->get('code'), $request->get('user_id'), true);
        });
    }
}