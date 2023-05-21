<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controller\apiController;
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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api', 'App\Http\Controllers\apiController@api');
Route::get('/compress', 'App\Http\Controllers\compressController@compress');
Route::get('/htmltopdf', 'App\Http\Controllers\htmltopdfController@html');
Route::get('/merge', 'App\Http\Controllers\mergeController@merge');
Route::get('/pdftoexcel', 'App\Http\Controllers\pdftoexcelController@excel');
Route::get('/pdftojpg', 'App\Http\Controllers\pdftojpgController@image');
Route::get('/pdftoword', 'App\Http\Controllers\pdftowordController@word');
Route::get('/split', 'App\Http\Controllers\splitController@split');
Route::get('/watermark', 'App\Http\Controllers\watermarkController@watermark');
Route::post('/compress/pdf', 'App\Http\Controllers\compressController@pdf_init');
Route::post('/htmltopdf/web', 'App\Http\Controllers\htmltopdfController@html_pdf');
Route::post('/merge/pdf', 'App\Http\Controllers\mergeController@pdf_merge');
Route::post('/pdftoexcel/xls', 'App\Http\Controllers\pdftoexcelController@pdf_excel');
Route::post('/pdftojpg/image', 'App\Http\Controllers\pdftojpgController@pdf_image');
Route::post('/pdftoword/docx', 'App\Http\Controllers\pdftowordController@pdf_word');
Route::post('/split/pdf', 'App\Http\Controllers\splitController@pdf_split');
Route::post('/watermark/pdf', 'App\Http\Controllers\watermarkController@pdf_watermark');