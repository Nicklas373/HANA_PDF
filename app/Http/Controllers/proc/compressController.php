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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class compressController extends Controller
{
 	public function compress(Request $request) {
		$validator = Validator::make($request->all(),[
            'batch' => 'required',
            'compMethod' => 'required',
            'file' => 'required',
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
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','PDF Compression failed !',$validator->messages());
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
                    'processId' => $uuid
                ], 401);
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                return response()->json([
                    'status' => 400,
                    'message' => 'Eloquent transaction error !',
                    'error' => $ex->getMessage(),
                    'processId' => $Nuuid
                ], 400);
            }
		} else {
            if ($request->has('file')) {
                $files = $request->post('file');
                $compMethod = $request->post('compMethod');
                $batch = $request->post('batch');
                $pdfEncKey = bin2hex(random_bytes(16));
                $pdfUpload_Location = env('PDF_UPLOAD');
                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                $pdfPool_Location = env('PDF_POOL');
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
                $str = rand(1000,10000000);
                $randomizePdfFileName = 'pdfCompress_'.substr(md5(uniqid($str)), 0, 8);
                foreach ($files as $file) {
                    $Nuuid = AppHelper::Instance()->get_guid();
                    $currentFileName = basename($file);
                    $trimPhase1 = str_replace(' ', '_', $currentFileName);
                    $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                    array_push($poolFiles, $newFileNameWithoutExtension);
                    $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                    $fileSize = filesize($newFilePath);
                    $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                    try {
                        $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                        $ilovepdfTask = $ilovepdf->newTask('compress');
                        $ilovepdfTask->setFileEncryption($pdfEncKey);
                        $ilovepdfTask->setEncryptKey($pdfEncKey);
                        $ilovepdfTask->setEncryption(true);
                        $pdfFile = $ilovepdfTask->addFile($newFilePath);
                        $pdfFile->setPassword($pdfEncKey);
                        $ilovepdfTask->setCompressionLevel($compMethod);
                        $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
                        $ilovepdfTask->execute();
                        $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                        $ilovepdfTask->delete();
                    } catch (StartException $e) {
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $Nuuid,
                                'errReason' => null,
                                'errApiReason' => null
                            ]);
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
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
                                    'errApiReason' => $e->getMessage()
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'PDF Compression failed !',
                                'error' => $e->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Database connection error !',
                                'error' => $ex->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
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
                                    'errApiReason' => $e->getMessage()
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'PDF Compression failed !',
                                'error' => $e->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Database connection error !',
                                'error' => $ex->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
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
                                    'errApiReason' => $e->getMessage()
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'PDF Compression failed !',
                                'error' => $e->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Database connection error !',
                                'error' => $ex->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
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
                                    'errApiReason' => $e->getMessage()
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'iLovePDF API Error !, Catch on ProcessException',
                                'error' => $e->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Database connection error !',
                                'error' => $ex->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
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
                                    'processId' => $Nuuid,
                                    'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                    'errApiReason' => $e->getMessage()
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'PDF Compression failed !',
                                'error' => $e->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Database connection error !',
                                'error' => $ex->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
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
                                    'errApiReason' => $e->getMessage()
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'PDF Compression failed !',
                                'error' => $e->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Database connection error !',
                                'error' => $ex->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
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
                                    'processId' => $Nuuid,
                                    'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                    'errApiReason' => $e->getMessage()
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'PDF Compression failed !',
                                'error' => $e->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Database connection error !',
                                'error' => $ex->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName.'.pdf',
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
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
                                    'errApiReason' => $e->getMessage()
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'PDF Compression failed !',
                                'error' => $ex->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Database connection error !',
                                'error' => $ex->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Eloquent transaction error !',
                                'error' => $e->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        }
                    }
                    if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'))) {
                        $compFileSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'));
                        $newCompFileSize = AppHelper::instance()->convert($compFileSize, "MB");
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        $procFile += 1;
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $Nuuid,
                                'errReason' => null,
                                'errApiReason' => null
                            ]);
                            DB::table('pdfCompress')->insert([
                                'fileName' => $newFileNameWithoutExtension.'.pdf',
                                'fileSize' => $newFileSize,
                                'compFileSize' => $newCompFileSize,
                                'compMethod' => $compMethod,
                                'result' => true,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.pdf', $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Eloquent transaction error !',
                                'oldFile' => $currentFileName.'.pdf',
                                'newFile' => $newFileNameWithoutExtension.'.pdf',
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName.'.pdf',
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
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
                            NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $Nuuid, 'FAIL', 'Failed to download file from iLovePDF API !', 'null');
                            return response()->json([
                                'status' => 400,
                                'message' => 'PDF Compression failed !',
                                'error' => null,
                                'processId' => $uuid
                            ], 400);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $Nuuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                            return response()->json([
                                'status' => 400,
                                'message' => 'Eloquent transaction error !',
                                'error' => $e->getMessage(),
                                'processId' => $Nuuid
                            ], 400);
                        }
                    }
                }
                if ($loopCount == $procFile) {
                    if ($loopCount == 1) {
                        return response()->json([
                            'status' => 200,
                            'message' => 'OK',
                            'res' => Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'),
                            'fileName' => $newFileNameWithoutExtension.'.pdf',
                            'curFileSize' => $newFileSize,
                            'newFileSize' => $newCompFileSize,
                            'compMethod' => $compMethod
                        ], 200);
                    } else {
                        $folderPath = Storage::disk('local')->path('public/'.$pdfDownload_Location);
                        $zipFilePath = Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip');
                        $zip = new ZipArchive();
                        try {
                            if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
                                foreach ($poolFiles as $file) {
                                    $filePath = $folderPath.DIRECTORY_SEPARATOR.$file.'.pdf';
                                    if (file_exists($filePath)) {
                                        $relativePath = $file.'.pdf';
                                        $zip->addFile($filePath, $relativePath);
                                    } else {
                                        return response()->json([
                                            'status' => 400,
                                            'message' => 'Failed Compress PDF file !',
                                            'error' => 'File '. $filePath . ' was not found',
                                            'processId' => $uuid
                                        ], 400);
                                    }
                                }
                                $zip->close();
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
                                    'curFileSize' => '',
                                    'newFileSize' => '',
                                    'compMethod' => $compMethod
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
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.zip', null, $uuid, 'FAIL', 'Failed archiving PDF files !', $e->getMessage());
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
                            'errReason' => 'PDF Compress failed',
                            'errApiReason' => 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.zip', null, $uuid, 'FAIL', 'PDF Compress failed !', 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount);
                        return response()->json([
                            'status' => 400,
                            'message' => 'PDF Compression failed',
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
                    DB::table('pdfCompress')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'compFileSize' => null,
                        'compMethod' => null,
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
                        'processId' => $Nuuid
                    ], 400);
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
