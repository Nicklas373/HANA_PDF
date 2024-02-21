<?php

namespace App\Http\Controllers\proc;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\Exceptions\StartException;
use Ilovepdf\Exceptions\AuthException;
use Ilovepdf\Exceptions\UploadException;
use Ilovepdf\Exceptions\ProcessException;
use Ilovepdf\Exceptions\DownloadException;
use Ilovepdf\Exceptions\TaskException;
use Ilovepdf\Exceptions\PathException;

class watermarkController extends Controller
{
    public function watermark(Request $request) {
        $validator = Validator::make($request->all(),[
            'file' => '',
            'imgFile' => '',
            'action' => ['required', 'in:img,txt'],
            'wmFontColor' => '',
            'wmFontSize' => '',
            'wmFontStyle' => '',
            'wmFontFamily' => '',
            'wmLayoutStyle' => '',
            'wmRotation' => '',
            'wmPage' => '',
            'wmText' => '',
            'wmTransparency' => '',
            'wmMosaic' => ''
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
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','PDF Watermark failed !',$validator->messages());
                return response()->json([
                    'status' => 401,
                    'message' => 'PDF failed to upload !',
                    'error' => $validator->errors()->all(),
                    'processId' => $uuid
                ], 401);
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','Database connection error !',$ex->getMessage());
                return response()->json([
                    'status' => 401,
                    'message' => 'Database connection error !',
                    'error' => $ex->getMessage(),
                    'processId' => null
                ], 401);
            }
        } else {
            if ($request->has('file')) {
                $files = $request->post('file');
                $pdfEncKey = bin2hex(random_bytes(16));
                $pdfUpload_Location = env('PDF_UPLOAD');
                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                $pdfDownload_Location = $pdfProcessed_Location;
                $batchValue = false;
                $batchId = null;
                $str = rand(1000,10000000);
                foreach ($files as $file) {
                    $currentFileName = basename($file);
                    $trimPhase1 = str_replace(' ', '_', $currentFileName);
                    $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                    $randomizePdfFileName = 'pdfWatermark_'.substr(md5(uniqid($str)), 0, 8);
                    $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                    $fileSize = filesize($newFilePath);
                    $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                    $watermarkAction = $request->post('action');
                    if ($watermarkAction == 'img') {
                        $watermarkImage = $request->file('imgFile');
                        $currentImageName = basename($watermarkImage);
                        $trimPhase1 = str_replace(' ', '_', $currentImageName);
                        $randomizeImageExtension = pathinfo($watermarkImage->getClientOriginalName(), PATHINFO_EXTENSION);
                        $wmImageName = $newFileNameWithoutExtension.'.'.$randomizeImageExtension;
                        $watermarkImage->storeAs('public/upload', $wmImageName);
                    } else if ($watermarkAction == 'txt') {
                        $wmImageName = '';
                    } else {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfWatermark')->insert([
                            'fileName' => $currentFileName,
                            'fileSize' => $newFileSize,
                            'watermarkFontFamily' => null,
                            'watermarkFontStyle' => null,
                            'watermarkFontSize' => null,
                            'watermarkFontTransparency' => null,
                            'watermarkImage' => null,
                            'watermarkLayout' => null,
                            'watermarkMosaic' => null,
                            'watermarkRotation' => null,
                            'watermarkStyle' => null,
                            'watermarkText' => null,
                            'watermarkPage' => null,
                            'result' => false,
                            'isBatch' => $batchValue,
                            'processId' => $uuid,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'errReason' => 'Invalid request action !',
                                'errApiReason' => 'Current request: '.$watermarkAction
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Invalid request action !', 'Current request: '.$watermarkAction);
                        return response()->json([
                            'status' => 400,
                            'message' => 'PDF Watermark failed !',
                            'error' => 'Current request: '.$watermarkAction,
                            'processId' => $uuid
                        ], 400);
                    }
                    $tempPDF = $request->post('wmMosaic');
                    if ($tempPDF == 'true') {
                        $isMosaicDB = "true";
                        $isMosaic = true;
                    } else {
                        $isMosaicDB = "false";
                        $isMosaic = false;
                    }
                    $watermarkFontColorTemp = $request->post('wmFontColor');
                    if ($watermarkFontColorTemp == '') {
                        $watermarkFontColor = '#000000';
                    } else {
                        $watermarkFontColor = $watermarkFontColorTemp;
                    }
                    $watermarkFontFamily = $request->post('wmFontFamily');
                    $watermarkFontSizeTemp = $request->post('wmFontSize');
                    if ($watermarkFontSizeTemp == '') {
                        $watermarkFontSize = '12';
                    } else {
                        $watermarkFontSize = $watermarkFontSizeTemp;
                    }
                    $watermarkFontStyle = $request->post('wmFontStyle');
                    $watermarkLayout = $request->post('wmLayoutStyle');
                    $watermarkTempPage = $request->post('wmPage');
                    if (is_string($watermarkTempPage)) {
                        $watermarkPage = strtolower($watermarkTempPage);
                    } else {
                        $watermarkPage = $watermarkTempPage;
                    }
                    $watermarkRotation = $request->post('wmRotation');
                    $watermarkTransparency = $request->post('wmTransparency');
                    $watermarkText = $request->post('wmText');
                    try {
                        $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                        $ilovepdfTask = $ilovepdf->newTask('watermark');
                        $pdfFile = $ilovepdfTask->addFile($newFilePath);
                        if ($watermarkAction == 'img') {
                            $ilovepdfTask->setEncryption(true);
                            $wmImage = $ilovepdfTask->addElementFile(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$wmImageName));
                            $ilovepdfTask->setMode("image");
                            $ilovepdfTask->setImageFile($wmImage);
                            $ilovepdfTask->setTransparency($watermarkTransparency);
                            $ilovepdfTask->setRotation($watermarkRotation);
                            $ilovepdfTask->setLayer($watermarkLayout);
                            $ilovepdfTask->setPages($watermarkPage);
                            $ilovepdfTask->setMosaic($isMosaic);
                            $ilovepdfTask->setVerticalPosition("middle");
                        } else if ($watermarkAction == 'txt') {
                            $ilovepdfTask->setEncryptKey($pdfEncKey);
                            $ilovepdfTask->setEncryption(true);
                            $ilovepdfTask->setMode("text");
                            $ilovepdfTask->setText($watermarkText);
                            $ilovepdfTask->setPages($watermarkPage);
                            $ilovepdfTask->setVerticalPosition("middle");
                            $ilovepdfTask->setRotation($watermarkRotation);
                            $ilovepdfTask->setFontColor($watermarkFontColor);
                            $ilovepdfTask->setFontFamily($watermarkFontFamily);
                            $ilovepdfTask->setFontStyle($watermarkFontStyle);
                            $ilovepdfTask->setFontSize($watermarkFontSize);
                            $ilovepdfTask->setTransparency($watermarkTransparency);
                            $ilovepdfTask->setLayer($watermarkLayout);
                            $ilovepdfTask->setMosaic($isMosaic);
                        }
                        $ilovepdfTask->setOutputFileName($randomizePdfFileName);
                        $ilovepdfTask->execute();
                        $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                        $ilovepdfTask->delete();
                    }
                    catch (StartException $e) {
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $uuid,
                                'errReason' => null,
                                'errApiReason' => null
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
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
                                'message' => 'PDF Watermark failed !',
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
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
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
                                'message' => 'PDF Watermark failed !',
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
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
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
                                'message' => 'PDF Watermark failed !',
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
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
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
                                'message' => 'PDF Watermark failed !',
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
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
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
                                'message' => 'PDF Watermark failed !',
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
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
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
                                'message' => 'PDF Watermark failed !',
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
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
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
                                'message' => 'PDF Watermark failed !',
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
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
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
                                'message' => 'PDF Watermark failed !',
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
                    }
                    if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'))) {
                        $procFileSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'));
                        $newProcFileSize = AppHelper::instance()->convert($procFileSize, "MB");
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $uuid,
                                'errReason' => null,
                                'errApiReason' => null
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $randomizePdfFileName.'.pdf',
                                'fileSize' => $newProcFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => true,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            return response()->json([
                                'status' => 200,
                                'message' => 'OK',
                                'res' => Storage::disk('local')->url($pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'),
                                'fileName' => $randomizePdfFileName.'.pdf',
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                            ], 200);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Eloquent transaction error !',
                                'oldFile' => $currentFileName.'.pdf',
                                'newFile' => $randomizePdfFileName.'.pdf',
                                'error' => $e->getMessage(),
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
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $newFileNameWithoutExtension.'.pdf',
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            DB::table('appLogs')
                                ->where('processId', '=', $uuid)
                                ->update([
                                    'errReason' => 'Failed to download file from iLovePDF API !',
                                    'errApiReason' => null
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'Failed to download file from iLovePDF API !', 'null');
                            return response()->json([
                                'status' => 400,
                                'message' => 'PDF Watermark failed !',
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
                                'error' => $e->getMessage(),
                                'processId' => $uuid
                            ], 400);
                        }
                    }
                }
            } else {
                try {
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfWatermark')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'watermarkFontFamily' => null,
                        'watermarkFontStyle' => null,
                        'watermarkFontSize' => null,
                        'watermarkFontTransparency' => null,
                        'watermarkImage' => null,
                        'watermarkLayout' => null,
                        'watermarkMosaic' => null,
                        'watermarkRotation' => null,
                        'watermarkStyle' => null,
                        'watermarkText' => null,
                        'watermarkPage' => null,
                        'result' => false,
                        'isBatch' => null,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'PDF failed to upload !',
                            'errApiReason' => null
                    ]);
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'PDF failed to upload !', null);
                    return response()->json([
                        'status' => 400,
                        'message' => 'PDF failed to upload !',
                        'error' => null,
                        'processId' => $uuid
                    ], 400);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $uuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
