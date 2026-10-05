<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('code', 40)->unique();
            $t->string('name');
            $t->string('type', 20);                    // platform | school
            $t->string('status', 20)->default('active'); // active | suspended
            $t->timestamps();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('name');
            $t->string('email');
            $t->string('password');
            $t->string('role', 40);
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_login_at')->nullable();
            $t->timestamps();
            $t->unique(['company_id', 'email']);
        });

        Schema::create('refresh_tokens', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('token_hash', 64)->unique();
            $t->timestamp('expires_at');
            $t->timestamp('revoked_at')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamps();
            $t->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        foreach (['refresh_tokens', 'users', 'companies'] as $tb) Schema::dropIfExists($tb);
    }
};
