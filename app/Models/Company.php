<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Empresa/instituicao: 'platform' (Eduvia) ou 'school' (colegio cliente). */
class Company extends Model
{
    use HasUuids;

    protected $fillable = ['code', 'name', 'type', 'status'];

    public function users(): HasMany { return $this->hasMany(User::class); }
}
