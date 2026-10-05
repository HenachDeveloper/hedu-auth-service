<?php

use App\Http\Middleware\ForceJson;
use App\Http\Middleware\ServiceToken;
use App\Http\Middleware\VerifyJwt;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [ForceJson::class]);
        $middleware->alias(['jwt' => VerifyJwt::class, 'service' => ServiceToken::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn () => true);
        $err = fn (string $code, string $msg, int $status, array $extra = []) =>
            response()->json(['error' => ['code' => $code, 'message' => $msg] + $extra], $status);
        $exceptions->render(fn (ValidationException $e) => $err('validation_failed', 'Dados inválidos.', 422, ['fields' => $e->errors()]));
        $exceptions->render(fn (ModelNotFoundException $e) => $err('not_found', 'Recurso não encontrado.', 404));
        $exceptions->render(fn (HttpExceptionInterface $e) => $err('http_error', $e->getMessage() ?: 'Erro.', $e->getStatusCode()));
    })->create();
