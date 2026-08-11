<?php

use App\Http\Middleware\ResolveOrganization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'organization' => ResolveOrganization::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => (object) [],
                'links' => (object) [],
                'error' => [
                    'message' => 'The submitted data is invalid.',
                    'code' => 'validation_failed',
                    'fields' => $exception->errors(),
                ],
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => (object) [],
                'links' => (object) [],
                'error' => ['message' => 'Authentication is required.', 'code' => 'unauthenticated'],
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => (object) [],
                'links' => (object) [],
                'error' => [
                    'message' => 'You do not have permission to perform this action.',
                    'code' => 'forbidden',
                ],
            ], 403);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => (object) [],
                'links' => (object) [],
                'error' => ['message' => 'The requested resource was not found.', 'code' => 'not_found'],
            ], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*') || ! in_array($exception->getStatusCode(), [403, 404], true)) {
                return null;
            }

            $notFound = $exception->getStatusCode() === 404;

            return response()->json([
                'data' => null,
                'meta' => (object) [],
                'links' => (object) [],
                'error' => [
                    'message' => $notFound
                        ? 'The requested resource was not found.'
                        : 'You do not have permission to perform this action.',
                    'code' => $notFound ? 'not_found' : 'forbidden',
                ],
            ], $exception->getStatusCode());
        });
    })->create();
