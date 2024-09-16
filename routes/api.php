<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Data\limitLogController;
use App\Http\Controllers\Api\Data\notifyLogController;
use App\Http\Controllers\Api\Core\compressController;
use App\Http\Controllers\Api\Core\convertController;
use App\Http\Controllers\Api\Core\htmltopdfController;
use App\Http\Controllers\Api\Core\mergeController;
use App\Http\Controllers\Api\Core\splitController;
use App\Http\Controllers\Api\Core\watermarkController;
use App\Http\Controllers\Api\File\uploadController;
use App\Http\Controllers\Api\File\thumbnailController;
use App\Http\Controllers\Api\Misc\versionController;

Route::group([
    'middleware' => 'api',
    'prefix' => 'v1/auth'
], function ($router) {
    // API v1 Backend Init Route
    Route::get('/getToken', [AuthController::class, 'getToken'])->name('getToken');
    Route::post('/initToken', [AuthController::class, 'initToken'])->name('initToken');
    Route::post('/refreshToken', [AuthController::class, 'refreshToken'])->middleware('auth:api')->name('refreshToken');
    Route::post('/revokeToken', [AuthController::class, 'revokeToken'])->middleware('auth:api')->name('revokeToken');
});

Route::middleware(['auth:api'],['throttle:api'])->prefix('v1/file')->group(function() {
    // API v1 Backend File Management Route
    Route::post('/upload', [uploadController::class, 'upload']);
    Route::post('/remove', [uploadController::class, 'remove']);
    Route::post('/thumbnail', [thumbnailController::class, 'getThumbnail']);
});

Route::middleware(['auth:api'],['throttle:api'])->prefix('v1/pdf')->group(function() {
    // API v1 Backend PDF Management Route
    Route::post('/compress', [compressController::class, 'compress']);
    Route::post('/convert', [convertController::class, 'convert']);
    Route::post('/getTotalPagesPDF', [uploadController::class, 'getTotalPagesPDF']);
    Route::post('/html', [htmltopdfController::class, 'html']);
    Route::post('/merge', [mergeController::class, 'merge']);
    Route::post('/split', [splitController::class, 'split']);
    Route::post('/watermark', [watermarkController::class, 'watermark']);
});

Route::middleware(['auth:api'],['throttle:api'])->prefix('v1/ilovepdf')->group(function() {
    // API v1 Backend IlovePDF Route
    Route::get('/limit', [limitLogController::class, 'getLimit']);
});

Route::middleware(['auth:api'],['throttle:api'])->prefix('v1/logs')->group(function() {
    // API v1 Backend Logging Route
    Route::post('/single', [notifyLogController::class, 'getLogs']);
    Route::post('/all', [notifyLogController::class, 'getAllLogs']);
});

Route::middleware(['auth:api'],['throttle:api'])->prefix('v1/version')->group(function() {
    // API v1 Backend PDF Version Validation Route
    Route::post('/check', [versionController::class, 'versioningCheck']);
    Route::get('/fetch', [versionController::class, 'versioningFetch']);
});