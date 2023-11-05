<?php

use Illuminate\Support\Facades\Route;
//use App\Http\Controller\apiController;
use App\Http\Controller\convertController;
use App\Http\Controller\compressController;
use App\Http\Controller\htmltopdfController;
use App\Http\Controller\mergeController;
use App\Http\Controller\pdftoexcelController;
use App\Http\Controller\pdftojpgController;
use App\Http\Controller\pdftowordController;
use App\Http\Controller\splitController;
use App\Http\Controller\watermarkController;

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

Route::get('/', function()
{
   return View::make('pages.welcome');
});
/*
Route::get('/api', function()
{
   return View::make('pages.api_information');
});
*/
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
Route::post('/compress/pdf', 'App\Http\Controllers\compressController@pdf_init');
Route::post('/convert/pdf', 'App\Http\Controllers\convertController@pdf_init');
Route::post('/htmltopdf/web', 'App\Http\Controllers\htmltopdfController@html_pdf');
Route::post('/merge/pdf', 'App\Http\Controllers\mergeController@pdf_merge');
Route::post('/split/pdf', 'App\Http\Controllers\splitController@pdf_split');
Route::post('/watermark/pdf', 'App\Http\Controllers\watermarkController@pdf_watermark');
