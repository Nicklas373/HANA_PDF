<?php

use Illuminate\Support\Facades\Route;

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
