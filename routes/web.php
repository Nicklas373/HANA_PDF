<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controller\data\dataLogController;
use App\Http\Controller\proc\convertController;
use App\Http\Controller\proc\compressController;
use App\Http\Controller\proc\htmltopdfController;
use App\Http\Controller\proc\mergeController;
use App\Http\Controller\proc\splitController;
use App\Http\Controller\proc\watermarkController;

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

// API Generate CRSF Token
Route::get('/api/v1/token', function (Request $request) {
    if (!$request->has('token')) {
        return response()->json(['status'=>'400','message' => 'Missing required token'], 400);
    }

    $origToken = env('TOKEN_GENERATE');
    $inputToken = $request->input('token');
    $hashedInputToken = hash('sha512', $inputToken);

    if ($hashedInputToken !== $origToken) {
        return response()->json(['status'=>'401','message' => 'Token verification failed'], 401);
    } else {
        $token = $request->session()->token();
        $token = csrf_token();
        return response()->json(['status'=>'200','token' => $token]);
    }
});

// A[I Redirect Route
Route::get('/api/v1/proc/compress', function()
{
    return redirect()->back();
});

Route::get('/api/v1/proc/convert', function()
{
    return redirect()->back();
});

Route::get('/api/v1/proc/html', function()
{
    return redirect()->back();
});

Route::get('/api/v1/proc/merge', function()
{
    return redirect()->back();
});

Route::get('/api/v1/proc/split', function()
{
    return redirect()->back();
});

Route::get('/api/v1/proc/watermark', function()
{
    return redirect()->back();
});

// API Processing Route
Route::post('/api/v1/proc/compress', 'App\Http\Controllers\proc\compressController@compress');
Route::post('/api/v1/proc/convert', 'App\Http\Controllers\proc\convertController@convert');
Route::post('/api/v1/proc/html', 'App\Http\Controllers\proc\htmltopdfController@html');
Route::post('/api/v1/proc/merge', 'App\Http\Controllers\proc\mergeController@merge');
Route::post('/api/v1/proc/split', 'App\Http\Controllers\proc\splitController@split');
Route::post('/api/v1/proc/watermark', 'App\Http\Controllers\proc\watermarkController@watermark');

// API Backend Data Route
Route::post('/api/v1/logs/single', 'App\Http\Controllers\data\dataLogController@getSingleLogs');
Route::post('/api/v1/logs/all', 'App\Http\Controllers\data\dataLogController@getAllLogs');
