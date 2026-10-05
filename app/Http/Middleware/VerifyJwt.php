<?php

namespace App\Http\Middleware;

use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;

class VerifyJwt
{
    public function __construct(private TokenService $tokens) {}

    public function handle(Request $request, Closure $next)
    {
        $jwt = $request->bearerToken();
        abort_unless($jwt, 401, 'Token em falta.');
        try {
            $claims = $this->tokens->decode($jwt);
        } catch (\Throwable) {
            $claims = null;
        }
        abort_unless($claims, 401, 'Token inválido ou expirado.');
        $request->attributes->set('claims', $claims);
        return $next($request);
    }
}
