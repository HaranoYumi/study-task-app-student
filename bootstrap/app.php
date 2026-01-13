<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Exceptions\ApiExceptionHandler;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sentry統合
        // Integration::handles($exceptions);

        // 研修用に必要に応じて以下のコメントアウトの切り替え（コメントアウト時はLaravelのデフォルトハンドラーが使用される）



        // API例外ハンドラーを登録
        // $apiHandler = new ApiExceptionHandler();

        // $exceptions->render(function (\Throwable $e, Request $request) use ($apiHandler) {
        //     return $apiHandler->handle($e, $request);
        // });
    })->create();
