<?php

use App\Exceptions\PerubahanStatusOperasionalTidakValidException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (
            ValidationException $exception,
            Request $request,
        ): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $detail = collect($exception->errors())
                ->flatten()
                ->first();

            return response()->json([
                'type' => 'about:blank',
                'title' => 'Request tidak valid',
                'status' => 422,
                'detail' => $detail,
                'instance' => $request->getPathInfo(),
            ], 422, [
                'Content-Type' => 'application/problem+json',
            ]);
        });

        $exceptions->render(function (
            NotFoundHttpException $exception,
            Request $request,
        ): ?JsonResponse {
            $previous = $exception->getPrevious();

            if (
                ! $request->is('api/*')
                || ! $previous instanceof ModelNotFoundException
            ) {
                return null;
            }

            return response()->json([
                'type' => 'about:blank',
                'title' => 'Pesanan operasional tidak ditemukan',
                'status' => 404,
                'detail' => 'Pesanan operasional yang diminta tidak ditemukan.',
                'instance' => $request->getPathInfo(),
            ], 404, [
                'Content-Type' => 'application/problem+json',
            ]);
        });

        $exceptions->render(function (
            PerubahanStatusOperasionalTidakValidException $exception,
            Request $request,
        ): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'type' => 'about:blank',
                'title' => 'Perubahan status tidak diperbolehkan',
                'status' => 409,
                'detail' => $exception->getMessage(),
                'instance' => $request->getPathInfo(),
            ], 409, [
                'Content-Type' => 'application/problem+json',
            ]);
        });
    })->create();
