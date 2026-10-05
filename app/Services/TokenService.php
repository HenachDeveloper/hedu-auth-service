<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TokenService
{
    public function issueFor(User $user, ?string $ip = null): array
    {
        $user->loadMissing('company');
        $cfg = config('auth_service');
        $now = time();
        $claims = [
            'iss' => $cfg['issuer'], 'aud' => $cfg['audience'], 'sub' => $user->id,
            'iat' => $now, 'nbf' => $now, 'exp' => $now + $cfg['access_ttl'], 'jti' => (string) Str::uuid(),
            'cid' => $user->company->id, 'ccode' => $user->company->code, 'ctype' => $user->company->type,
            'name' => $user->name, 'email' => $user->email,
            'roles' => [$user->role], 'permissions' => $cfg['roles'][$user->role] ?? [],
        ];
        $access = JWT::encode($claims, file_get_contents($cfg['private_key']), 'RS256', $cfg['kid']);

        $plain = bin2hex(random_bytes(32));
        RefreshToken::create([
            'user_id' => $user->id, 'token_hash' => hash('sha256', $plain), 'ip' => $ip,
            'expires_at' => now()->addDays($cfg['refresh_ttl_days']),
        ]);

        return [
            'access_token' => $access, 'refresh_token' => $plain, 'token_type' => 'Bearer', 'expires_in' => $cfg['access_ttl'],
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role,
                'company' => ['id' => $user->company->id, 'code' => $user->company->code, 'name' => $user->company->name, 'type' => $user->company->type]],
        ];
    }

    /** Rotacao: cada refresh token so serve uma vez; a reutilizacao revoga todas as sessoes do utilizador. */
    public function rotate(string $plain, ?string $ip = null): array
    {
        $hash = hash('sha256', $plain);
        $rt = RefreshToken::where('token_hash', $hash)->first();
        abort_unless($rt, 401, 'Refresh token inválido.');

        if ($rt->revoked_at) {
            RefreshToken::where('user_id', $rt->user_id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            abort(401, 'Refresh token reutilizado: sessões revogadas.');
        }
        abort_if($rt->expires_at->isPast(), 401, 'Refresh token expirado.');

        return DB::transaction(function () use ($hash, $ip) {
            $rt = RefreshToken::where('token_hash', $hash)->lockForUpdate()->first();
            abort_if(! $rt || $rt->revoked_at, 401, 'Refresh token inválido.');
            $user = User::with('company')->find($rt->user_id);
            abort_unless($user && $user->is_active && $user->company->status === 'active', 401, 'Conta inactiva.');
            $rt->update(['revoked_at' => now()]);
            return $this->issueFor($user, $ip);
        });
    }

    public function revoke(string $plain): void
    {
        RefreshToken::where('token_hash', hash('sha256', $plain))->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    public function decode(string $jwt): array
    {
        JWT::$leeway = 30;
        $obj = JWT::decode($jwt, new Key(file_get_contents(config('auth_service.public_key')), 'RS256'));
        $c = json_decode(json_encode($obj), true);
        if (($c['iss'] ?? null) !== config('auth_service.issuer') || ($c['aud'] ?? null) !== config('auth_service.audience')) {
            throw new \UnexpectedValueException('iss/aud inválidos');
        }
        return $c;
    }
}
