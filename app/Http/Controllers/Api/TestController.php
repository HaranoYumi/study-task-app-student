<?php
// app/Http/Controllers/Api/TestController.php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends ApiController
{
    /**
     * テスト用：シンプルなレスポンスを返す
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'message' => 'pong',
            'status' => 'ok',
        ]);
    }

    /**
     * テスト用：受け取ったデータをそのまま返す
     */
    public function echo(Request $request): JsonResponse
    {
        return response()->json([
            'received' => $request->all(),
        ]);
    }
}
