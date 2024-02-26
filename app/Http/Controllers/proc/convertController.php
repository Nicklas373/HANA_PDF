<?php

namespace App\Http\Controllers\proc;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Aspose\Words\WordsApi;
use Aspose\Words\Model\Requests\{SaveAsRequest, UploadFileRequest};
use Aspose\Words\Model\{DocxSaveOptionsData};
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\ImagepdfTask;
use Ilovepdf\OfficepdfTask;
use Ilovepdf\PdfjpgTask;
use Ilovepdf\Exceptions\StartException;
use Ilovepdf\Exceptions\AuthException;
use Ilovepdf\Exceptions\UploadException;
use Ilovepdf\Exceptions\ProcessException;
use Ilovepdf\Exceptions\DownloadException;
use Ilovepdf\Exceptions\TaskException;
use Ilovepdf\Exceptions\PathException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class convertController extends Controller
{
	public function convert(Request $request) {
		$validator = Validator::make($request->all(),[
            'batch' => 'required',
            'convertType' => 'required',
            'file' => 'required',
            'extImage' => 'required'
		]);

        $uuid = AppHelper::Instance()->get_guid();

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

        if ($validator->fails()) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => $validator->messages(),
                    'errApiReason' => null
                ]);
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','PDF Conversion failed !',$validator->messages());
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
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                return response()->json([
                    'status' => 401,
                    'message' => 'Eloquent transaction error !',
                    'error' => $ex->getMessage(),
                    'processId' => $Nuuid
                ], 401);
            }
		} else {
            if ($request->has('file')) {
                $files = $request->post('file');
                $images = $request->post('extImage');
                $convertType = $request->post('convertType');
                $batch = $request->post('batch');
                $pdfEncKey = bin2hex(random_bytes(16));
                $pdfUpload_Location = env('PDF_UPLOAD');
                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                $pdfPool_Location = env('PDF_POOL');
                $pdfExtImage_Location = env('PDF_IMG_POOL');
                $pdfImageTrueName = '';
                $loopCount = count($files);
                $poolFiles = array();
                $procFile = 0;
                if ($batch == "true") {
                    $batchValue = true;
                    $batchId = $uuid;
                } else {
                    $batchValue = false;
                    $batchId = null;
                }
                if ($loopCount > 1) {
                    $pdfDownload_Location = $pdfPool_Location;
                } else {
                    $pdfDownload_Location = $pdfProcessed_Location;
                }
                if ($images == "true") {
                    $imageModes = 'extract';
                    $extMode = true;
                } else {
                    $imageModes = 'pages';
                    $extMode = false;
                }
                $str = rand(1000,10000000);
                $randomizePdfFileName = 'pdfConvert_'.substr(md5(uniqid($str)), 0, 8);
                foreach ($files as $file) {
                    $Nuuid = AppHelper::Instance()->get_guid();
                    $currentFileName = basename($file);
                    $trimPhase1 = str_replace(' ', '_', $currentFileName);
                    $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                    array_push($poolFiles, $newFileNameWithoutExtension);
                    $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                    $fileSize = filesize($newFilePath);
                    $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                    $pdfTotalPages = AppHelper::instance()->count($newFilePath);
                    $pdfNameWithExtension = pathinfo($currentFileName, PATHINFO_EXTENSION);
                    if ($convertType == 'xlsx') {
                        $asposeAPI = new Process([
                            'python3',
                            public_path().'/ext-python/asposeAPI.py',
                            env('ASPOSE_CLOUD_CLIENT_ID'),
                            env('ASPOSE_CLOUD_TOKEN'),
                            "xlsx",
                            $newFilePath,
                            $newFileNameWithoutExtension.".xlsx"
                        ],
                        null,
                        [
                            'SYSTEMROOT' => getenv('SYSTEMROOT'),
                            'PATH' => getenv("PATH")
                        ]);
                        try {
                            ini_set('max_execution_time', 600);
                            $asposeAPI->setTimeout(600);
                            $asposeAPI->run();
                        } catch (RuntimeException $message) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'Symfony runtime process out of time exception !',
                                        'errApiReason' => $message->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Symfony runtime process out of time exception !', $message->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'HANA PDF process timeout !',
                                    'error' => $message->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        } catch (ProcessFailedException $message) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'Symfony runtime process fail exception !',
                                        'errApiReason' => $message->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Symfony runtime process fail exception !', $message->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'HANA PDF process timeout !',
                                    'error' => $message->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        }
                        if (!$asposeAPI->isSuccessful()) {
                            if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.xlsx'), $newFileNameWithoutExtension.".xlsx") == true) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.xlsx'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $procFile += 1;
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => null,
                                            'errApiReason' => null
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } else {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'Converted file not found on the server !',
                                            'errApiReason' => $asposeAPI->getErrorOutput(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Converted file not found on the server !', $asposeAPI->getErrorOutput());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => null,
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            }
                        } else {
                            if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.xlsx'), $newFileNameWithoutExtension.".xlsx") == true) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.xlsx'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $procFile += 1;
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => null,
                                            'errApiReason' => null
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } else {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'Converted file not found on the server !',
                                            'errApiReason' => $asposeAPI->getErrorOutput(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Converted file not found on the server !', $asposeAPI->getErrorOutput());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => null,
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            }
                        }
                    } else if ($convertType == 'pptx') {
                        $asposeAPI = new Process([
                            'python3',
                            public_path().'/ext-python/asposeAPI.py',
                            env('ASPOSE_CLOUD_CLIENT_ID'),
                            env('ASPOSE_CLOUD_TOKEN'),
                            "pptx",
                            $newFilePath,
                            $newFileNameWithoutExtension.".pptx"
                        ],
                        null,
                        [
                            'SYSTEMROOT' => getenv('SYSTEMROOT'),
                            'PATH' => getenv("PATH")
                        ]);
                        try {
                            ini_set('max_execution_time', 600);
                            $asposeAPI->setTimeout(600);
                            $asposeAPI->run();
                        } catch (RuntimeException $message) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'Symfony runtime process out of time exception !',
                                        'errApiReason' => $message->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Symfony runtime process out of time exception !', $message->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'HANA PDF process timeout !',
                                    'error' => $message->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        } catch (ProcessFailedException $message) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'Symfony runtime process fail exception !',
                                        'errApiReason' => $message->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Symfony runtime process fail exception !', $message->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'HANA PDF process timeout !',
                                    'error' => $message->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        }
                        if (!$asposeAPI->isSuccessful()) {
                            if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pptx'), $newFileNameWithoutExtension.".pptx") == true) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pptx'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $procFile += 1;
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => null,
                                            'errApiReason' => null
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } else {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'Converted file not found on the server !',
                                            'errApiReason' => $asposeAPI->getErrorOutput(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Converted file not found on the server !', $asposeAPI->getErrorOutput());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $asposeAPI->getErrorOutput(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            }
                        } else {
                            if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pptx'), $newFileNameWithoutExtension.".pptx") == true) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pptx'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $procFile += 1;
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => null,
                                            'errApiReason' => null
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } else {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'Converted file not found on the server !',
                                            'errApiReason' => $asposeAPI->getErrorOutput(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Converted file not found on the server !', $asposeAPI->getErrorOutput());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => null,
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            }
                        }
                    } else if ($convertType == 'docx') {
                        try {
                            $wordsApi = new WordsApi(env('ASPOSE_CLOUD_CLIENT_ID'), env('ASPOSE_CLOUD_TOKEN'));
                            $uploadFileRequest = new UploadFileRequest($newFilePath, $currentFileName);
                            $wordsApi->uploadFile($uploadFileRequest);
                            $requestSaveOptionsData = new DocxSaveOptionsData(array(
                                "save_format" => "docx",
                                "file_name" => $newFileNameWithoutExtension.".docx",
                            ));
                            $request = new SaveAsRequest(
                                $currentFileName,
                                $requestSaveOptionsData,
                                NULL,
                                NULL,
                                NULL,
                                NULL
                            );
                            $result = $wordsApi->saveAs($request);
                        } catch (\Exception $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'Aspose PDF API Error !',
                                        'errApiReason' => $e->getMessage()
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Aspose PDF API Error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Error while connecting to external API !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        }
                        if (json_decode($result, true) !== NULL) {
                            if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.".docx"), $newFileNameWithoutExtension.".docx") == true) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.docx'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $procFile += 1;
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => null,
                                            'errApiReason' => null
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } else {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'FTP Server Connection Failed !',
                                            'errApiReason' => null
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Aspose PDF API Error !', null);
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Error while connecting to external API !',
                                        'error' => null,
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            }
                        } else {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'Aspose Clouds API has fail while process, Please look on Aspose Dashboard !',
                                        'errApiReason' => null
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Aspose PDF API Error !', null);
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Error while connecting to external API !',
                                    'error' => null,
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        }
                    } else if ($convertType == 'jpg') {
                        try {
                            $ilovepdfTask = new PdfjpgTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                            $ilovepdfTask->setFileEncryption($pdfEncKey);
                            $ilovepdfTask->setEncryptKey($pdfEncKey);
                            $ilovepdfTask->setEncryption(true);
                            $pdfFile = $ilovepdfTask->addFile($newFilePath);
                            $ilovepdfTask->setMode($imageModes);
                            $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
                            $ilovepdfTask->setPackagedFilename($newFileNameWithoutExtension);
                            $ilovepdfTask->execute();
                            $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                        } catch (StartException $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                        'errApiReason' => $e->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'PDF Conversion failed !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        } catch (AuthException $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                        'errApiReason' => $e->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'PDF Conversion failed !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        } catch (UploadException $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                        'errApiReason' => $e->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'PDF Conversion failed !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        } catch (ProcessException $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                        'errApiReason' => $e->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'PDF Conversion failed !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        } catch (DownloadException $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                        'errApiReason' => $e->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'PDF Conversion failed !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        } catch (TaskException $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                        'errApiReason' => $e->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'PDF Conversion failed !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        } catch (PathException $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                        'errApiReason' => $e->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'PDF Conversion failed !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        } catch (\Exception $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                        'errApiReason' => $e->getMessage(),
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'PDF Conversion failed !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        }
                        if ($pdfTotalPages == 1 && $extMode) {
                            foreach (glob(Storage::disk('local')->path('public/'.$pdfExtImage_Location).'/*.jpg') as $filename) {
                                rename($filename, Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg'));
                            }
                        }
                        if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.zip'))) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            $procFile += 1;
                            $pdfImageTrueName = $newFileNameWithoutExtension.'.zip';
                            $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.zip'));
                            $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileProcSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => true,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => null,
                                        'errApiReason' => null
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $e->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        } else {
                            if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg'))) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $procFile += 1;
                                $pdfImageTrueName = $newFileNameWithoutExtension.'.jpg';
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => $extMode,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => null,
                                            'errApiReason' => null
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } else if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-0001.jpg'))) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $procFile += 1;
                                $pdfImageTrueName = $newFileNameWithoutExtension.'-0001.jpg';
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-0001.jpg'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => $extMode,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => null,
                                            'errApiReason' => null
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            }
                        }
                    } else if ($convertType == 'pdf') {
                        if ($pdfNameWithExtension == "jpg" || $pdfNameWithExtension == "jpeg" || $pdfNameWithExtension == "png" || $pdfNameWithExtension == "tiff") {
                            try {
                                $ilovepdfTask = new ImagepdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                $ilovepdfTask->setFileEncryption($pdfEncKey);
                                $ilovepdfTask->setEncryptKey($pdfEncKey);
                                $ilovepdfTask->setEncryption(true);
                                $pdfFile = $ilovepdfTask->addFile($newFilePath);
                                $ilovepdfTask->setPageSize('fit');
                                $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
                                $ilovepdfTask->setPackagedFilename($newFileNameWithoutExtension);
                                $ilovepdfTask->execute();
                                $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                            } catch (StartException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (AuthException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (UploadException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (ProcessException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (DownloadException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (TaskException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'processId' => $uuid,
                                            'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (PathException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (\Exception $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            }
                        } else {
                            try {
                                $ilovepdfTask = new OfficepdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                                $pdfFile = $ilovepdfTask->addFile($newFilePath);
                                $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
                                $ilovepdfTask->execute();
                                $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                            } catch (StartException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (AuthException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (UploadException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (ProcessException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (DownloadException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (TaskException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'processId' => $uuid,
                                            'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (PathException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            } catch (\Exception $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $Nuuid)
                                        ->update([
                                            'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                            'errApiReason' => $e->getMessage(),
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF Conversion failed !',
                                        'error' => $e->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Database connection error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $ex->getMessage(),
                                        'processId' => $Nuuid
                                    ], 400);
                                }
                            }
                        }
                        if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'))) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'));
                            $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                            $procFile += 1;
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileProcSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => true,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => null,
                                        'errApiReason' => null
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileProcSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        } else {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $Nuuid)
                                    ->update([
                                        'errReason' => 'Failed to download converted file from iLovePDF API !',
                                        'errApiReason' => null
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Failed to download converted file from iLovePDF API !', null);
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'PDF Conversion failed !',
                                    'error' => null,
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        }
                    }
                }
                if ($loopCount == $procFile) {
                    if ($loopCount == 1) {
                        if ($convertType == 'jpg') {
                            return response()->json([
                                'status' => 200,
                                'message' => 'OK',
                                'res' => Storage::disk('local')->url($pdfDownload_Location.'/'.$pdfImageTrueName),
                                'fileName' => $pdfImageTrueName,
                                'convertType' => $convertType
                            ], 200);
                        } else {
                            return response()->json([
                                'status' => 200,
                                'message' => 'OK',
                                'res' => Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.'.$convertType),
                                'fileName' => $newFileNameWithoutExtension.'.'.$convertType,
                                'convertType' => $convertType
                            ], 200);
                        }
                    } else {
                        $folderPath = Storage::disk('local')->path('public/'.$pdfDownload_Location);
                        $zipFilePath = Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip');
                        $zip = new ZipArchive();
                        try {
                            if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
                                if ($convertType == 'jpg') {
                                    foreach ($poolFiles as $file) {
                                        $filePath = $folderPath.DIRECTORY_SEPARATOR.$file.'.zip';
                                        if (file_exists($filePath)) {
                                            $relativePath = $file.'.zip';
                                            $zip->addFile($filePath, $relativePath);
                                        } else {
                                            return response()->json([
                                                'status' => 400,
                                                'message' => 'Failed convert PDF file !',
                                                'error' => 'File '. $filePath . ' was not found',
                                                'processId' => $uuid
                                            ], 400);
                                        }
                                    }
                                    $zip->close();
                                } else {
                                    foreach ($poolFiles as $file) {
                                        $filePath = $folderPath.DIRECTORY_SEPARATOR.$file.'.'.$convertType;
                                        if (file_exists($filePath)) {
                                            $relativePath = $file.'.'.$convertType;
                                            $zip->addFile($filePath, $relativePath);
                                        }
                                    }
                                    $zip->close();
                                }
                                $tempPDFfiles = glob(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/*'));
                                foreach($tempPDFfiles as $file){
                                    if(is_file($file)) {
                                       unlink($file);
                                    }
                                }
                                return response()->json([
                                    'status' => 200,
                                    'message' => 'OK',
                                    'res' => Storage::disk('local')->url($pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip'),
                                    'fileName' => $randomizePdfFileName.'.zip',
                                    'convertType' => $convertType
                                ], 200);
                            } else {
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Failed archiving PDF files !',
                                    'error' => 'Archive processing failure',
                                    'processId' => $uuid
                                ], 400);
                            }
                        } catch (\Exception $e) {
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => 'Archive processing failure',
                                    'errApiReason' => $e->getMessage()
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.zip', null, $uuid, 'FAIL', 'Failed convert PDF file!', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Failed archiving PDF files !',
                                    'error' => $e->getMessage(),
                                    'processId' => $uuid
                                ], 400);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Database connection error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return response()->json([
                                    'status' => 400,
                                    'message' => 'Eloquent transaction error !',
                                    'error' => $ex->getMessage(),
                                    'processId' => $Nuuid
                                ], 400);
                            }
                        }
                    }
                } else {
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'PDF convert failed',
                            'errApiReason' => 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.zip', null, $uuid, 'FAIL', 'PDF Compress failed !', 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount);
                        return response()->json([
                            'status' => 400,
                            'message' => 'PDF Conversion failed !',
                            'error' => 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount,
                            'processId' => $uuid
                        ], 400);
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'Database connection error !',
                            'error' => $ex->getMessage(),
                            'processId' => $Nuuid
                        ], 400);
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'Eloquent transaction error !',
                            'error' => $ex->getMessage(),
                            'processId' => $Nuuid
                        ], 400);
                    }
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
                    DB::table('pdfConvert')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'container' => null,
                        'imgExtract' => false,
                        'result' => false,
                        'isBatch' => false,
                        'processId' => $uuid,
                        'batchId' => null,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $Nuuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'PDF failed to upload !',
                            'errApiReason' => $e->getMessage(),
                    ]);
                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'PDF failed to upload !', 'null');
                    return response()->json([
                        'status' => 400,
                        'message' => 'PDF failed to upload !',
                        'error' => null,
                        'processId' => $uuid
                    ], 400);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Database connection error !',
                        'error' => $ex->getMessage(),
                        'processId' => $Nuuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return response()->json([
                        'status' => 400,
                        'message' => 'Eloquent transaction error !',
                        'error' => $ex->getMessage(),
                        'processId' => $Nuuid
                    ], 400);
                }
            }
        }
	}
}
