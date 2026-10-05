<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Protege a API interna (chamada apenas pelo super-admin-api). */
class ServiceToken
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('auth_service.service_token');
        abort_unless($expected !== '' && hash_equals($expected, (string) $request->bearerToken()), 401, 'Token de serviço inválido.');
        return $next($request);
    }
}
