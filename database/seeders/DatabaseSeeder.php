<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Idempotente. Cria: empresa da plataforma (eduvia) + super admin, e a empresa demo
 * (colegio-demo) com utilizadores. Nao repoe palavras-passe de utilizadores existentes.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $eduvia = Company::updateOrCreate(['code' => 'eduvia'], ['name' => 'Eduvia', 'type' => 'platform', 'status' => 'active']);
        $demo = Company::updateOrCreate(['code' => 'colegio-demo'], ['name' => 'Colégio Demo', 'type' => 'school', 'status' => 'active']);

        $users = [
            [$eduvia, 'Super Admin', env('SEED_PLATFORM_ADMIN_EMAIL', 'admin@eduvia.ao'), env('SEED_PLATFORM_ADMIN_PASSWORD'), 'platform_admin'],
            [$demo, 'Direcção Colégio Demo', env('SEED_SCHOOL_ADMIN_EMAIL', 'director@colegiodemo.ao'), env('SEED_SCHOOL_ADMIN_PASSWORD'), 'school_admin'],
            [$demo, 'Professor Demo', env('SEED_TEACHER_EMAIL', 'professor@colegiodemo.ao'), env('SEED_TEACHER_PASSWORD'), 'school_teacher'],
        ];
        foreach ($users as [$company, $name, $email, $password, $role]) {
            if (! $password) continue; // sem palavra-passe definida nao cria o utilizador
            User::firstOrCreate(
                ['company_id' => $company->id, 'email' => strtolower($email)],
                ['name' => $name, 'password' => $password, 'role' => $role, 'is_active' => true],
            );
        }
    }
}
