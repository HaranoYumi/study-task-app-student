<?php

use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Lesson6-2用：Laravelのデフォルトエラーハンドリングを学ぶための教材ルーティング
 * 敢えてRoute Model Bindingを使わず、{id}形式にしています
 */

// 認証済みユーザー情報を取得
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/test', function () {
    throw new \Exception("テストエラー");
});


// 認証が必要なAPI
Route::middleware(['auth:sanctum'])->group(function () {
    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/dropdown', [UserController::class, 'dropdown']);

    // Projects（❌ 冗長：{id}形式を使用）
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

    // Tasks（❌ 冗長：{id}形式を使用）
    Route::get('/projects/{projectId}/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{projectId}/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    // Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
    Route::post('/tasks/{id}/start', [TaskController::class, 'start']);
    Route::post('/tasks/{id}/complete', [TaskController::class, 'complete']);

    // Members（❌ 冗長：{id}形式を使用）
    Route::get('/projects/{projectId}/members', [ProjectMemberController::class, 'index']);
    Route::post('/projects/{projectId}/members', [ProjectMemberController::class, 'store']);
    Route::delete('/projects/{projectId}/members/{userId}', [ProjectMemberController::class, 'destroy']);
});
