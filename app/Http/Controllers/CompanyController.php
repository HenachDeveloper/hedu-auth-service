<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * API interna (token de serviço): gestão de empresas e utilizadores pelo Super Admin.
 * @tags Interno: Empresas e utilizadores
 */
class CompanyController extends Controller
{
    /** Cria a empresa (colégio) e o seu administrador inicial. Se `admin.password` faltar, gera uma palavra-passe temporária devolvida uma única vez. */
    public function store(Request $request): JsonResponse
    {
        $d = $request->validate([
            'code' => ['required', 'regex:/^[a-z0-9-]{3,40}$/', 'unique:companies,code'],
            'name' => 'required|string|max:255',
            'admin.name' => 'required|string|max:255', 'admin.email' => 'required|email', 'admin.password' => 'nullable|string|min:12|max:200',
        ]);
        $generated = empty($d['admin']['password']);
        $password = $d['admin']['password'] ?? Str::password(16);

        $result = DB::transaction(function () use ($d, $password) {
            $company = Company::create(['code' => $d['code'], 'name' => $d['name'], 'type' => 'school', 'status' => 'active']);
            $admin = User::create(['company_id' => $company->id, 'name' => $d['admin']['name'], 'email' => strtolower($d['admin']['email']),
                'password' => $password, 'role' => 'school_admin', 'is_active' => true]);
            return [$company, $admin];
        });
        [$company, $admin] = $result;

        return response()->json([
            'company' => ['id' => $company->id, 'code' => $company->code, 'name' => $company->name, 'status' => $company->status],
            'admin' => ['id' => $admin->id, 'email' => $admin->email] + ($generated ? ['temporary_password' => $password] : []),
        ], 201);
    }

    public function show(string $code): JsonResponse
    {
        $c = Company::where('code', $code)->withCount('users')->firstOrFail();
        return response()->json(['id' => $c->id, 'code' => $c->code, 'name' => $c->name, 'type' => $c->type, 'status' => $c->status, 'users' => $c->users_count]);
    }

    /** Suspende/reactiva. Suspender revoga os refresh tokens; access tokens em curso expiram em ≤ 15 min. */
    public function status(Request $request, string $code): JsonResponse
    {
        $status = $request->validate(['status' => ['required', Rule::in(['active', 'suspended'])]])['status'];
        $c = Company::where('code', $code)->firstOrFail();
        abort_if($c->type === 'platform', 422, 'A empresa da plataforma não pode ser suspensa.');
        $c->update(['status' => $status]);
        if ($status === 'suspended') {
            RefreshToken::whereIn('user_id', User::where('company_id', $c->id)->select('id'))->whereNull('revoked_at')->update(['revoked_at' => now()]);
        }
        return response()->json(['code' => $c->code, 'status' => $c->status]);
    }

    /** Adiciona utilizador à empresa (papéis de colégio). */
    public function addUser(Request $request, string $code): JsonResponse
    {
        $c = Company::where('code', $code)->firstOrFail();
        $d = $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email',
            'role' => ['required', Rule::in($c->type === 'school' ? ['school_admin', 'school_teacher', 'school_finance', 'school_guardian'] : ['platform_admin', 'platform_ops', 'platform_support'])],
            'password' => 'required|string|min:12|max:200']);
        abort_if(User::where('company_id', $c->id)->where('email', strtolower($d['email']))->exists(), 422, 'E-mail já existe nesta empresa.');
        $u = User::create(['company_id' => $c->id, 'name' => $d['name'], 'email' => strtolower($d['email']), 'password' => $d['password'], 'role' => $d['role']]);
        return response()->json(['id' => $u->id, 'email' => $u->email, 'role' => $u->role], 201);
    }
}
