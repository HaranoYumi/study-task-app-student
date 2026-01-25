<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\ConflictException;
use Throwable;

class ApiExceptionHandler
{
    private ApiResponse $response;

    public function __construct()
    {
        $this->response = new ApiResponse();
    }

    /**
     * API例外を処理する
     * 
     * @param Throwable $exception
     * @param Request $request
     * @return JsonResponse|null
     */
    public function handle(Throwable $exception, Request $request): ?JsonResponse
    {
        // API以外のリクエストは処理しない
        if (!$request->is('api/*')) {
            return null;
        }

        // リクエストIDを生成（エラー追跡用）
        $requestId = $request->header('X-Request-ID') ?? uniqid('req_', true);

        // 例外タイプに応じて処理を振り分け
        return match (true) {
            $exception instanceof NotFoundHttpException => $this->handleNotFound($requestId),
            $exception instanceof ModelNotFoundException => $this->handleNotFound($requestId, $exception->getMessage()),
            $exception instanceof ValidationException => $this->handleValidation($exception, $requestId),
            $exception instanceof AuthenticationException => $this->handleAuthentication($requestId),
            $exception instanceof AuthorizationException => $this->handleForbidden($exception->getMessage(), $requestId),
            $exception instanceof AccessDeniedHttpException => $this->handleForbidden($exception->getMessage(), $requestId),
            $exception instanceof ConflictException => $this->handleConflict($exception, $requestId),
            default => $this->handleServerError($exception, $request, $requestId),
        };
    }

    /**
     * 404エラー
     */
    private function handleNotFound(string $requestId, ?string $message = null): JsonResponse
    {
        return $this->response->notFound($message ?? '指定されたデータが見つかりません', $requestId);
    }

    /**
     * 権限エラー（403）
     * Laravel標準のAuthorizationExceptionまたはSymfonyのAccessDeniedHttpExceptionを処理
     */
    private function handleForbidden(string $message, string $requestId): JsonResponse
    {
        return $this->response->forbidden($message, $requestId);
    }

    /**
     * 競合エラー（409）
     * カスタム例外（Laravel標準にないため）
     */
    private function handleConflict(ConflictException $exception, string $requestId): JsonResponse
    {
        // 409 Conflictのレスポンスを返す
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'request_id' => $requestId,
        ], 409);
    }

    /**
     * バリデーションエラー
     */
    private function handleValidation(ValidationException $exception, string $requestId): JsonResponse
    {
        return $this->response->validationError('バリデーションエラー', $exception->errors(), $requestId);
    }

    /**
     * 認証エラー
     */
    private function handleAuthentication(string $requestId): JsonResponse
    {
        return $this->response->unauthorized(requestId: $requestId);
    }

    /**
     * サーバーエラー（500）
     * どれにも当てはまらない場合はサーバーエラーとして処理
     */
    private function handleServerError(Throwable $exception, Request $request, string $requestId): JsonResponse
    {
        // Sentryにコンテキスト情報を追加してエラーを送信
        $sentry = app('sentry');

        $sentry->configureScope(function (\Sentry\State\Scope $scope) use ($request, $requestId, $exception): void {
            // リクエスト情報をタグとして追加（Sentryで検索可能）
            $scope->setTag('request_id', $requestId);
            $scope->setTag('http_method', $request->method());
            $scope->setTag('route', $request->route()?->getName() ?? $request->path());
            $scope->setTag('exception_type', get_class($exception));

            // リクエストIDを追加情報として記録
            $scope->setExtra('request_id', $requestId);

            // ユーザー情報を追加（認証済みの場合）
            if ($user = $request->user()) {
                $scope->setUser([
                    'id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            // リクエスト詳細をコンテキストとして追加
            $scope->setContext('request', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_id' => $requestId,
            ]);

            // リクエストパラメータを追加（機密情報は除外）
            $params = $request->except(['password', 'password_confirmation', 'token']);
            $scope->setContext('request_data', $params);
        });

        // エラーメッセージにリクエストIDを含める（シンプル検索を可能にするため）
        $enhancedException = new \Exception(
            $exception->getMessage() . ' [ErrorID: ' . $requestId . ']',
            $exception->getCode(),
            $exception
        );
        $sentry->captureException($enhancedException);

        // 本番では固定メッセージ、開発中は詳細表示
        $message = config('app.debug') ? $exception->getMessage() : 'サーバーエラー';

        return $this->response->serverError($message, $requestId);
    }
}
