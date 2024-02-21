<?php

namespace App\Http\Controllers\proc;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\HtmlpdfTask;
use Ilovepdf\Exceptions\StartException;
use Ilovepdf\Exceptions\AuthException;
use Ilovepdf\Exceptions\UploadException;
use Ilovepdf\Exceptions\ProcessException;
use Ilovepdf\Exceptions\DownloadException;
use Ilovepdf\Exceptions\TaskException;
use Ilovepdf\Exceptions\PathException;

class htmltopdfController extends Controller
{
    public function html(Request $request) {
        $validator = Validator::make($request->all(),[
		    'urlToPDF' => 'required',
	    ]);

        $uuid = AppHelper::Instance()->get_guid();

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

        if($validator->fails()) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => $validator->messages(),
                    'errApiReason' => null
                ]);
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','Failed to convert HTML to PDF !',$validator->messages());
                return response()->json([
                    'status' => 401,
                    'message' => 'Failed to convert HTML to PDF !',
                    'error' => $validator->errors()->all(),
                    'processId' => $uuid
                ], 401);
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','Database connection error !',$ex->getMessage());
                return response()->json([
                    'status' => 401,
                    'message' => 'Database connection error !',
                    'error' => $ex->getMessage(),
                    'processId' => $uuid
                ], 401);
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                return response()->json([
                    'status' => 400,
                    'message' => 'Eloquent transaction error !',
                    'error' => $ex->getMessage(),
                    'processId' => $uuid
                ], 400);
            }
        } else {
            $start = Carbon::parse($startProc);
            $str = rand(1000,10000000);
            $pdfEncKey = bin2hex(random_bytes(16));
            $pdfDefaultFileName ='pdf_convert_'.substr(md5(uniqid($str)), 0, 8);
            $pdfProcessed_Location = env('PDF_DOWNLOAD');
            $pdfUpload_Location = env('PDF_UPLOAD');
            $pdfUrl = $request->post('urlToPDF');
            $newUrl = '';
            if (AppHelper::Instance()->checkWebAvailable($pdfUrl)) {
                $newUrl = $pdfUrl;
            } else {
                if (AppHelper::Instance()->checkWebAvailable('https://'.$pdfUrl)) {
                    $newUrl = 'https://'.$pdfUrl;
                } else if (AppHelper::Instance()->checkWebAvailable('http://'.$pdfUrl)) {
                    $newUrl = 'http://'.$pdfUrl;
                } else if (AppHelper::Instance()->checkWebAvailable('www.'.$pdfUrl)) {
                    $newUrl = 'www.'.$pdfUrl;
                } else {
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfHtml')->insert([
                            'urlName' => $request->post('urlToPDF'),
                            'result' => false,
                            'processId' => $uuid,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'processId' => $uuid,
                                'errReason' => '404',
                                'errApiReason' => 'Webpage are not available or not valid'
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'HTML To PDF Conversion Failed !', 'Webpage are not available or not valid');
                        return response()->json([
                            'status' => 400,
                            'message' => 'HTML To PDF Conversion Failed !',
                            'error' => 'Webpage are not available or not valid',
                            'processId' => $uuid
                        ], 400);
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'Database connection error !',
                            'error' => $ex->getMessage(),
                            'processId' => $uuid
                        ], 400);
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'Eloquent transaction error !',
                            'error' => $ex->getMessage(),
                            'processId' => $uuid
                        ], 400);
                    }
                }
            }
            try {
                $ilovepdfTask = new HtmlpdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                $ilovepdfTask->setEncryptKey($pdfEncKey);
                $ilovepdfTask->setEncryption(true);
                $pdfFile = $ilovepdfTask->addUrl($newUrl);
                $ilovepdfTask->setOutputFileName($pdfDefaultFileName);
                $ilovepdfTask->execute();
                $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
            } catch (StartException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'errReason' => 'iLovePDF API Error !, Catch on StartException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Failed to convert HTML to PDF !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Eloquent transaction error !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                }
            } catch (AuthException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Failed to convert HTML to PDF !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Eloquent transaction error !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                }
            } catch (UploadException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Failed to convert HTML to PDF !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Eloquent transaction error !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                }
            } catch (ProcessException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Failed to convert HTML to PDF !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Eloquent transaction error !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                }
            } catch (DownloadException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Failed to convert HTML to PDF !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Eloquent transaction error !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                }
            } catch (TaskException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Failed to convert HTML to PDF !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Eloquent transaction error !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                }
            } catch (PathException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'iLovePDF API Error !, Catch on PathException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Failed to convert HTML to PDF !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Eloquent transaction error !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                }
            } catch (\Exception $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'errReason' => 'iLovePDF API Error !, Catch on Exception',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Failed to convert HTML to PDF !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Eloquent transaction error !',
                        'error' => $e->getMessage(),
                        'processId' => $uuid
                    ], 400);
                }
            }
            if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfDefaultFileName.'.pdf'))) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => true,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                    ]);
                    return response()->json([
                        'status' => 200,
                        'message' => 'OK',
                        'res' => Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfDefaultFileName.'.pdf'),
                        'fileName' => $pdfDefaultFileName.'.pdf',
                        'urlSource' => $pdfUrl
                    ], 200);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Eloquent transaction error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                }
            } else {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'Failed to download converted file from iLovePDF API !',
                            'errApiReason' => null
                    ]);
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'HTML To PDF Conversion Failed !', null);
                    return response()->json([
                        'status' => 400,
                        'message' => 'HTML To PDF Conversion Failed !',
                        'error' => null,
                        'processId' => $uuid
                    ], 400);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Eloquent transaction error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                }
            }
        }
    }
}
