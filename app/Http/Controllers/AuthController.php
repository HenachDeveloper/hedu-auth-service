<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @tags Autenticação
 */
class AuthController extends Controller
{
    public function __construct(private TokenService $tokens) {}

    /** Login por código da empresa + e-mail + palavra-passe. Devolve access token (JWT RS256) e refresh token. */
    public function login(Request $request): JsonResponse
    {
        $d = $request->validate(['company_code' => 'required|string|max:40', 'email' => 'required|email', 'password' => 'required|string|max:200']);
        $company = Company::where('code', strtolower($d['company_code']))->first();
        $user = $company ? User::where('company_id', $company->id)->where('email', strtolower($d['email']))->first() : null;

        // Hash de referencia evita diferenca de tempo quando o utilizador nao existe.
        $dummy = Cache::rememberForever('auth.dummy_hash', fn () => Hash::make(Str::random(24)));
        $ok = Hash::check($d['password'], $user->password ?? $dummy) && $user;

        if (! $ok) {
            Log::warning('login_failed', ['company' => $d['company_code'], 'email' => $d['email'], 'ip' => $request->ip()]);
            return response()->json(['error' => ['code' => 'invalid_credentials', 'message' => 'Credenciais inválidas.']], 401);
        }
        if (! $user->is_active) return response()->json(['error' => ['code' => 'user_inactive', 'message' => 'Utilizador inactivo.']], 403);
        if ($company->status !== 'active') return response()->json(['error' => ['code' => 'company_suspended', 'message' => 'Instituição suspensa.']], 403);

        $user->forceFill(['last_login_at' => now()])->save();
        Log::info('login_ok', ['user' => $user->id, 'company' => $company->code, 'ip' => $request->ip()]);
        return response()->json($this->tokens->issueFor($user, $request->ip()));
    }

    /** Troca um refresh token por um novo par (rotação obrigatória). */
    public function refresh(Request $request): JsonResponse
    {
        $rt = $request->validate(['refresh_token' => 'required|string|size:64'])['refresh_token'];
        return response()->json($this->tokens->rotate($rt, $request->ip()));
    }

    /** Revoga o refresh token informado. */
    public function logout(Request $request)
    {
        $rt = $request->validate(['refresh_token' => 'required|string|size:64'])['refresh_token'];
        $this->tokens->revoke($rt);
        return response()->noContent();
    }

    /** Identidade e permissões do token actual. */
    public function me(Request $request): JsonResponse
    {
        $c = $request->attributes->get('claims');
        return response()->json(['id' => $c['sub'], 'name' => $c['name'], 'email' => $c['email'], 'roles' => $c['roles'],
            'permissions' => $c['permissions'], 'company' => ['id' => $c['cid'], 'code' => $c['ccode'], 'type' => $c['ctype']]]);
    }
}
