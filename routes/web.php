<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Main Routes
Route::get('/', function()
{
   return View::make('pages.welcome');
});

Route::get('/compress', function()
{
   return View::make('pages.compress');
});

Route::get('/convert', function()
{
   return View::make('pages.cnvMain');
});

Route::get('/cnvFromPDF', function()
{
   return View::make('pages.cnvFromPDF');
});

Route::get('/cnvToPDF', function()
{
   return View::make('pages.cnvToPDF');
});

Route::get('/htmltopdf', function()
{
    return View::make('pages.htmltopdf');
});

Route::get('/merge', function()
{
   return View::make('pages.merge');
});

Route::get('/split', function()
{
   return View::make('pages.split');
});

Route::get('/watermark', function()
{
   return View::make('pages.watermark');
});

// API v1 CSRF Token
Route::get('/api/v1/token', 'App\Http\Controllers\main\tokenController@getToken');

// API v1 Backend Logging Route
Route::post('/api/v1/logs/limit', 'App\Http\Controllers\data\limitLogController@getLimit');
Route::post('/api/v1/logs/proc/single', 'App\Http\Controllers\data\processLogController@getLogs');
Route::post('/api/v1/logs/proc/all', 'App\Http\Controllers\data\processLogController@getAllLogs');
Route::post('/api/v1/logs/notify/single', 'App\Http\Controllers\data\notifyLogController@getLogs');
Route::post('/api/v1/logs/notify/all', 'App\Http\Controllers\data\notifyLogController@getAllLogs');

// API v1 Upload Route
Route::post('api/v1/file/upload', 'App\Http\Controllers\main\uploadController@upload');
Route::post('api/v1/file/remove', 'App\Http\Controllers\main\uploadController@remove');
Route::post('api/v1/file/thumbnail', 'App\Http\Controllers\main\thumbnailController@getThumbnail');

// API v2 Processing Route
Route::post('api/v2/proc/compress', 'App\Http\Controllers\proc\compressController@compress');
Route::post('api/v2/proc/convert', 'App\Http\Controllers\proc\convertController@convert');
Route::post('api/v2/proc/html', 'App\Http\Controllers\proc\htmltopdfController@html');
Route::post('api/v2/proc/merge', 'App\Http\Controllers\proc\mergeController@merge');
Route::post('api/v2/proc/split', 'App\Http\Controllers\proc\splitController@split');
Route::post('api/v2/proc/watermark', 'App\Http\Controllers\proc\watermarkController@watermark');
