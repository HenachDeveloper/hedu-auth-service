<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Model
{
    use HasUuids;

    protected $fillable = ['company_id', 'name', 'email', 'password', 'role', 'is_active'];
    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean', 'last_login_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
