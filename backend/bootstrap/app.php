<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $error) => $request->is('api/*') || $request->expectsJson());
        $exceptions->report(function (QueryException $error) {
            // SQL/bindings can contain agendas or credentials: do not log them.
            Log::error('database_operation_failed', [
                'sqlstate' => $error->errorInfo[0] ?? (string) $error->getCode(),
                'driver_code' => $error->errorInfo[1] ?? null,
            ]);

            return false;
        });
        $exceptions->render(function (QueryException $error, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'The data service is unavailable. Please retry later.'], 503);
            }
        });
    })->create();
