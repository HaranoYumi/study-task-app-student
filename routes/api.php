<?php

use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Lesson6-3: After版 - Route Model Bindingを使った適切なルーティング
 * {id}ではなく{モデル名}を使うことで、自動的に404チェックが行われます
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

    // Projects（✅ Route Model Binding使用）
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    // Tasks（✅ Route Model Binding使用）
    Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::post('/tasks/{task}/start', [TaskController::class, 'start']);
    Route::post('/tasks/{id}/complete', [TaskController::class, 'complete']);

    // Members（✅ Route Model Binding使用）
    Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index']);
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store']);
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy']);
});
