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

class mergeController extends Controller
{
    public function merge(Request $request) {
        $validator = Validator::make($request->all(),[
            'batch' => 'required',
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
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','PDF Merged failed !',$validator->messages());
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
                $batch = $request->post('batch');
                $pdfEncKey = bin2hex(random_bytes(16));
                $pdfUpload_Location = env('PDF_UPLOAD');
                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                $pdfPool_Location = env('PDF_POOL');
                if ($batch == "true") {
                    $batchValue = true;
                    $batchId = $uuid;
                } else {
                    $batchValue = false;
                    $batchId = null;
                }
                $pdfDownload_Location = $pdfPool_Location;
                $str = rand(1000,10000000);
                $randomizePdfFileName = 'pdfMerged_'.substr(md5(uniqid($str)), 0, 8);
                try {
                    $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                    $ilovepdfTask = $ilovepdf->newTask('merge');
                    $ilovepdfTask->setFileEncryption($pdfEncKey);
                    $ilovepdfTask->setEncryptKey($pdfEncKey);
                    $ilovepdfTask->setEncryption(true);
                    foreach ($files as $file) {
                        $Nuuid = AppHelper::Instance()->get_guid();
                        $currentFileName = basename($file);
                        $trimPhase1 = str_replace(' ', '_', $currentFileName);
                        $firstTrim = basename($currentFileName, '.pdf');
                        $newFileNameWithoutExtension = str_replace('.', '_', $firstTrim);
                        $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                        $fileSize = filesize($newFilePath);
                        $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        $pdfName = $ilovepdfTask->addFile($newFilePath);
                    }
                    $pdfName->setPassword($pdfEncKey);
                    $ilovepdfTask->setOutputFileName($randomizePdfFileName);
                    $ilovepdfTask->execute();
                    $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                    $ilovepdfTask->delete();
                } catch (StartException $e) {
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
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
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'PDF Merged failed !',
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
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
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'PDF Merged failed !',
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
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
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'PDF Merged failed !',
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
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
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'PDF Merged failed !',
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                'errApiReason' => $e->getMessage()
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'PDF Merged failed !',
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
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
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'PDF Merged failed !',
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                'errApiReason' => $e->getMessage()
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'PDF Merged failed !',
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
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
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                        return response()->json([
                            'status' => 400,
                            'message' => 'PDF Merged failed !',
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
                if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'))) {
                    $mergedFileSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'));
                    $newMergedFileSize = AppHelper::instance()->convert($mergedFileSize, "MB");
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfMerge')->insert([
                        'fileName' => $randomizePdfFileName.'.pdf',
                        'fileSize' => $newMergedFileSize,
                        'result' => true,
                        'isBatch' => false,
                        'processId' => $uuid,
                        'batchId' => null,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    return response()->json([
                        'status' => 200,
                        'message' => 'OK',
                        'res' => Storage::disk('local')->url($pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'),
                        'fileName' => $randomizePdfFileName.'.pdf',
                        'fileSize' => $newMergedFileSize,
                    ], 200);
                } else {
                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Failed to download file from iLovePDF API !', 'null');
                    return response()->json([
                        'status' => 400,
                        'message' => 'PDF Merge failed !',
                        'error' => null,
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
                    DB::table('pdfMerge')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'result' => false,
                        'isBatch' => false,
                        'processId' => $uuid,
                        'batchId' => null,
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
