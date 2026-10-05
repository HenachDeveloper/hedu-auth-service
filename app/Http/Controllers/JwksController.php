<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * @tags Chaves públicas
 */
class JwksController extends Controller
{
    /** JWKS com a chave pública RS256 (consumida por super-admin-api e school-api para validar JWT). */
    public function __invoke(): JsonResponse
    {
        $key = openssl_pkey_get_public(file_get_contents(config('auth_service.public_key')));
        $rsa = openssl_pkey_get_details($key)['rsa'];
        $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        return response()->json(['keys' => [[
            'kty' => 'RSA', 'use' => 'sig', 'alg' => 'RS256', 'kid' => config('auth_service.kid'),
            'n' => $b64($rsa['n']), 'e' => $b64($rsa['e']),
        ]]])->header('Cache-Control', 'public, max-age=300');
    }
}
