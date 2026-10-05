<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'token_hash', 'expires_at', 'revoked_at', 'ip'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }
}
