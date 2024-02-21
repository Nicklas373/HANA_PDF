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

class splitController extends Controller
{
 	public function split(Request $request) {
		$validator = Validator::make($request->all(),[
            'file' => 'required',
            'action' => ['required', 'in:delete,split'],
            'fromPage' => '',
            'toPage' => '',
            'mergePDF' => 'required',
            'customPageSplit' => '',
            'customPageDelete' => ''
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
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','PDF Split failed !',$validator->messages());
                return response()->json([
                    'status' => 401,
                    'message' => 'PDF failed to upload !',
                    'error' => $validator->errors()->all(),
                    'processId' => $uuid
                ], 401);
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','Database connection error !',$ex->getMessage());
                return response()->json([
                    'status' => 400,
                    'message' => 'Database connection error !',
                    'error' => $ex->getMessage(),
                    'processId' => null
                ], 400);
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
                    $randomizePdfFileName = 'pdfSplit_'.substr(md5(uniqid($str)), 0, 8);
                    $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                    $fileSize = filesize($newFilePath);
                    $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                    if ($request->has('action')) {
                        $action = $request->post('action');
                        $fromPage = $request->post('fromPage');
                        $toPage = $request->post('toPage');
                        $tempPDF = $request->post('mergePDF');
                        if ($tempPDF == 'true') {
                            $mergeDBpdf = "true";
                            $mergePDF = true;
                        } else {
                            $mergeDBpdf = "false";
                            $mergePDF = false;
                        }
                        $fixedPage = $request->post('fixedPage');
                        if ($action == 'split') {
                            $customInputPage = $request->post('customPageSplit');
                            if (is_string($customInputPage)) {
                                $customPage = strtolower($customInputPage);
                            } else {
                                $customPage = $customInputPage;
                            }
                            if ($fromPage != '') {
                                $pdfTotalPages = AppHelper::instance()->count($newFilePath);
                                if ($toPage > $pdfTotalPages) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfSplit')->insert([
                                            'fileName' => $currentFileName,
                                            'fileSize' => $newFileSize,
                                            'fromPage' => $fromPage,
                                            'toPage' => $toPage,
                                            'customPage' => null,
                                            'fixedPage' => null,
                                            'fixedPageRange' => null,
                                            'mergePDF' => $mergeDBpdf,
                                            'action' => $action,
                                            'result' => false,
                                            'isBatch' => $batchValue,
                                            'processId' => $uuid,
                                            'batchId' => $batchId,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'errReason' => 'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                                'errApiReason' => null
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'PDF split failed!', 'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')');
                                        return response()->json([
                                            'status' => 400,
                                            'message' => 'PDF split failed!',
                                            'error' => 'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
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
                                } else if ($fromPage > $pdfTotalPages) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfSplit')->insert([
                                            'fileName' => $currentFileName,
                                            'fileSize' => $newFileSize,
                                            'fromPage' => $fromPage,
                                            'toPage' => $toPage,
                                            'customPage' => null,
                                            'fixedPage' => null,
                                            'fixedPageRange' => null,
                                            'mergePDF' => $mergeDBpdf,
                                            'action' => $action,
                                            'result' => false,
                                            'isBatch' => $batchValue,
                                            'processId' => $uuid,
                                            'batchId' => $batchId,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'errReason' => 'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                                'errApiReason' => null
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'PDF split failed!', 'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')');
                                        return response()->json([
                                            'status' => 400,
                                            'message' => 'PDF split failed!',
                                            'error' => 'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
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
                                } else if ($fromPage > $toPage) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfSplit')->insert([
                                            'fileName' => $currentFileName,
                                            'fileSize' => $newFileSize,
                                            'fromPage' => $fromPage,
                                            'toPage' => $toPage,
                                            'customPage' => null,
                                            'fixedPage' => null,
                                            'fixedPageRange' => null,
                                            'mergePDF' => $mergeDBpdf,
                                            'action' => $action,
                                            'result' => false,
                                            'isBatch' => $batchValue,
                                            'processId' => $uuid,
                                            'batchId' => $batchId,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'errReason' => 'First Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                                'errApiReason' => null
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'PDF split failed!', 'First Page has more page than last page ! (total page: '.$pdfTotalPages.')');
                                        return response()->json([
                                            'status' => 400,
                                            'message' => 'PDF split failed!',
                                            'error' => 'First Page has more page than last page ! (total page: '.$pdfTotalPages.')',
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
                                } else {
                                    if ($mergeDBpdf == "true") {
                                        $fixedPageRanges = $fromPage.'-'.$toPage;
                                    } else if ($mergeDBpdf == "false") {
                                        $pdfStartPages = $fromPage;
                                        $pdfTotalPages = $toPage;
                                        while($pdfStartPages <= intval($pdfTotalPages))
                                        {
                                            $pdfArrayPages[] = $pdfStartPages;
                                            $pdfStartPages += 1;
                                        }
                                        $fixedPageRanges = implode(', ', $pdfArrayPages);
                                    }
                                }
                            } else {
                                $fixedPageRanges = $customPage;
                            }
                            if (!$mergePDF) {
                                $newFileNameWithoutExtension =  str_replace('.', '_', $trimPhase1.'_page');
                            }
                        } else {
                            $customInputPage = $request->post('customPageDelete');
                            $fixedPageRanges = null;
                            if (is_string($customInputPage)) {
                                $customPage = strtolower($customInputPage);
                            } else {
                                $customPage = $customInputPage;
                            }
                        }
                        try {
                            $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                            $ilovepdfTask = $ilovepdf->newTask('split');
                            $ilovepdfTask->setFileEncryption($pdfEncKey);
                            $ilovepdfTask->setEncryptKey($pdfEncKey);
                            $ilovepdfTask->setEncryption(true);
                            $pdfFile = $ilovepdfTask->addFile($newFilePath);
                            $pdfFile->setPassword($pdfEncKey);
                            if ($action == 'split') {
                                $ilovepdfTask->setRanges($fixedPageRanges);
                                if ($mergePDF == 1) {
                                    $ilovepdfTask->setMergeAfter(true);
                                } else {
                                    $ilovepdfTask->setMergeAfter(false);
                                }
                            } else if ($action == 'delete') {
                                $ilovepdfTask->setRemovePages($customPage);
                            }
                            $ilovepdfTask->setPackagedFilename($randomizePdfFileName);
                            $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
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
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'action' => $action,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $uuid,
                                    'batchId' => $batchId,
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
                                    'message' => 'PDF split failed!',
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
                            } catch (AuthException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
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
                                        'message' => 'PDF split failed!',
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
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
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
                                        'message' => 'PDF split failed!',
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
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
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
                                        'message' => 'PDF split failed!',
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
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
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
                                        'message' => 'PDF split failed!',
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
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
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
                                        'message' => 'PDF split failed!',
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
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
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
                                        'message' => 'PDF split failed!',
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
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
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
                                        'message' => 'PDF split failed!',
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
                        }
                        if ($action == 'split') {
                            if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'))) {
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    return response()->json([
                                        'status' => 200,
                                        'message' => 'OK',
                                        'res' => Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'),
                                        'fileName' => $newFileNameWithoutExtension.'.pdf',
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                    ], 200);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.pdf', $newFileProcSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'oldFile' => $currentFileName.'.pdf',
                                        'newFile' => $newFileNameWithoutExtension.'.pdf',
                                        'error' => $e->getMessage(),
                                        'processId' => $uuid
                                    ], 400);
                                }
                            } else if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'))) {
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    return response()->json([
                                        'status' => 200,
                                        'message' => 'OK',
                                        'res' => Storage::disk('local')->url($pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'),
                                        'fileName' => $randomizePdfFileName.'.zip',
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                    ], 200);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.zip', $newFileProcSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'oldFile' => $currentFileName.'.pdf',
                                        'newFile' => $newFileNameWithoutExtension.'.pdf',
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
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
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
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'Failed to download file from iLovePDF API !', 'null');
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF split failed!',
                                        'error' => null,
                                        'processId' => $uuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'error' => $e->getMessage(),
                                        'processId' => $uuid
                                    ], 400);
                                }
                            }
                        } else if ($action == 'delete') {
                            if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'))) {
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    return response()->json([
                                        'status' => 200,
                                        'message' => 'OK',
                                        'res' => Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'),
                                        'fileName' => $newFileNameWithoutExtension.'.pdf',
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                    ], 200);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.pdf', $newFileProcSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'Eloquent transaction error !',
                                        'oldFile' => $currentFileName.'.pdf',
                                        'newFile' => $newFileNameWithoutExtension.'.pdf',
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
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => $customPage,
                                        'fixedPage' => $fixedPage,
                                        'fixedPageRange' => $fixedPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
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
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'Failed to download file from iLovePDF API !', 'null');
                                    return response()->json([
                                        'status' => 400,
                                        'message' => 'PDF split failed!',
                                        'error' => null,
                                        'processId' => $uuid
                                    ], 400);
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                            DB::table('pdfSplit')->insert([
                                'fileName' => null,
                                'fileSize' => null,
                                'fromPage' => null,
                                'toPage' => null,
                                'customPage' => null,
                                'fixedPage' => null,
                                'fixedPageRange' => null,
                                'mergePDF' => null,
                                'action' => null,
                                'result' => false,
                                'isBatch' => null,
                                'processId' => $uuid,
                                'batchId' => null,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            DB::table('appLogs')
                                ->where('processId', '=', $uuid)
                                ->update([
                                    'errReason' => 'Invalid split request method !',
                                    'errApiReason' => null
                            ]);
                            NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'Invalid split request method !', null);
                            return response()->json([
                                'status' => 400,
                                'message' => 'PDF split failed!',
                                'error' => null,
                                'processId' => $uuid
                            ], 400);
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                    DB::table('pdfSplit')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'fromPage' => null,
                        'toPage' => null,
                        'customPage' => null,
                        'fixedPage' => null,
                        'fixedPageRange' => null,
                        'mergePDF' => null,
                        'action' => null,
                        'result' => false,
                        'isBatch' => null,
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
