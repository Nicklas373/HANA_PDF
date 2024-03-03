<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Data\limitLogController;
use App\Http\Controllers\Api\Data\notifyLogController;
use App\Http\Controllers\Api\Data\processLogController;
use App\Http\Controllers\Api\File\uploadController;
use App\Http\Controllers\Api\File\thumbnailController;
use App\Http\Controllers\Api\Core\compressController;
use App\Http\Controllers\Api\Core\convertController;
use App\Http\Controllers\Api\Core\htmltopdfController;
use App\Http\Controllers\Api\Core\mergeController;
use App\Http\Controllers\Api\Core\splitController;
use App\Http\Controllers\Api\Core\watermarkController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::group([
        'middleware' => 'api',
        'prefix' => 'v1/auth'
    ], function ($router) {
    Route::post('token', [AuthController::class, 'token']);
    Route::post('revoke', [AuthController::class, 'revoke']);
});

Route::middleware('auth:api')->prefix('v1/core')->group(function() {
    // API v1 Backend PDF Core Processing Route
    Route::post('compress', [compressController::class, 'compress']);
    Route::post('convert', [convertController::class, 'convert']);
    Route::post('html', [htmltopdfController::class, 'html']);
    Route::post('merge', [mergeController::class, 'merge']);
    Route::post('split', [splitController::class, 'split']);
    Route::post('watermark', [watermarkController::class, 'watermark']);
});

Route::middleware('auth:api')->prefix('v1/file')->group(function() {
    // API v1 Backend File Management Route
    Route::post('upload', [uploadController::class, 'upload']);
    Route::post('remove', [uploadController::class, 'remove']);
    Route::post('thumbnail', [thumbnailController::class, 'getThumbnail']);
});

Route::middleware('auth:api')->prefix('v1/logs')->group(function() {
    // API v1 Backend Logging Route
    Route::get('limit', [limitLogController::class, 'getLimit']);
    Route::post('proc/single', [processLogController::class, 'getLogs']);
    Route::post('proc/all', [processLogController::class, 'getAllLogs']);
    Route::post('notify/single', [notifyLogController::class, 'getLogs']);
    Route::post('notify/all', [notifyLogController::class, 'getAllLogs']);
});

Route::fallback(function () {
    try {
        return response()->json(['message' => 'Route not found'], 404);
    } catch (TokenExpiredException $e) {
        return response()->json(['error' => 'Token expired'], 401);
    }
});
