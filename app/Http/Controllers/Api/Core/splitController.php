<?php

namespace App\Http\Controllers\Api\Core;

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
            'fromPage' => ['nullable', 'numeric'],
            'toPage' => ['nullable', 'numeric'],
            'mergePDF' => ['required', 'in:true,false'],
            'customPageSplit' => ['nullable', 'regex:/^[0-9a-zA-Z,]+$/'],
            'customPageDelete' => ['nullable', 'regex:/^[0-9a-zA-Z,]+$/']
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
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'FAIL', 'split','PDF Split failed !',$validator->messages());
                return $this->returnCoreMessage(
                    200,
                    'PDF Split failed !',
                    null,
                    null,
                    'split',
                    $uuid,
                    null,
                    null,
                    null,
                    $validator->errors()->all()
                );
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                return $this->returnCoreMessage(
                    200,
                    'Database connection error !',
                    null,
                    null,
                    'split',
                    $uuid,
                    null,
                    null,
                    null,
                    $ex->getMessage()
                );
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                return $this->returnCoreMessage(
                    200,
                    'Eloquent transaction error !',
                    null,
                    null,
                    'split',
                    $uuid,
                    null,
                    null,
                    null,
                    $e->getMessage()
                );
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
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'PDF split failed!', 'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')');
                                        return $this->returnCoreMessage(
                                            200,
                                            'PDF split failed!',
                                            $currentFileName,
                                            null,
                                            'split',
                                            $uuid,
                                            $fileSize,
                                            null,
                                            null,
                                            'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')'
                                        );
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                        return $this->returnCoreMessage(
                                            200,
                                            'Database connection error !',
                                            $currentFileName,
                                            null,
                                            'split',
                                            $uuid,
                                            $fileSize,
                                            null,
                                            null,
                                            $ex->getMessage()
                                        );
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                        return $this->returnCoreMessage(
                                            200,
                                            'Eloquent transaction error !',
                                            $currentFileName,
                                            null,
                                            'split',
                                            $uuid,
                                            $fileSize,
                                            null,
                                            null,
                                            $e->getMessage()
                                        );
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
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'split', 'PDF split failed!', 'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')');
                                        return $this->returnCoreMessage(
                                            200,
                                            'PDF split failed!',
                                            $currentFileName,
                                            null,
                                            'split',
                                            $uuid,
                                            $fileSize,
                                            null,
                                            null,
                                            'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')'
                                        );
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                        return $this->returnCoreMessage(
                                            200,
                                            'Database connection error !',
                                            $currentFileName,
                                            null,
                                            'split',
                                            $uuid,
                                            $fileSize,
                                            null,
                                            null,
                                            $ex->getMessage()
                                        );
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                        return $this->returnCoreMessage(
                                            200,
                                            'Eloquent transaction error !',
                                            $currentFileName,
                                            null,
                                            'split',
                                            $uuid,
                                            $fileSize,
                                            null,
                                            null,
                                            $e->getMessage()
                                        );
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
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'split', 'PDF split failed!', 'First Page has more page than last page ! (total page: '.$pdfTotalPages.')');
                                        return $this->returnCoreMessage(
                                            200,
                                            'PDF split failed!',
                                            $currentFileName,
                                            null,
                                            'split',
                                            $uuid,
                                            $fileSize,
                                            null,
                                            null,
                                            'First Page has more page than last page ! (total page: '.$pdfTotalPages.')'
                                        );
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                        return $this->returnCoreMessage(
                                            200,
                                            'Database connection error !',
                                            $currentFileName,
                                            null,
                                            'split',
                                            $uuid,
                                            $fileSize,
                                            null,
                                            null,
                                            $ex->getMessage()
                                        );
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                        return $this->returnCoreMessage(
                                            200,
                                            'Eloquent transaction error !',
                                            $currentFileName,
                                            null,
                                            'split',
                                            $uuid,
                                            $fileSize,
                                            null,
                                            null,
                                            $e->getMessage()
                                        );
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF split failed !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF split failed !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF split failed !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF split failed !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF split failed !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF split failed !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'split', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF split failed !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF split failed !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'split',
                                    $uuid,
                                    $fileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
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
                                    return $this->returnCoreMessage(
                                        200,
                                        'OK',
                                        $newFileNameWithoutExtension.'.pdf',
                                        Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'),
                                        'split',
                                        $uuid,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        null
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.pdf', $newFileProcSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $newFileNameWithoutExtension.'.pdf',
                                        null,
                                        'split',
                                        $uuid,
                                        $fileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.pdf', $newFileProcSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $newFileNameWithoutExtension.'.pdf',
                                        null,
                                        'split',
                                        $uuid,
                                        $fileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
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
                                    return $this->returnCoreMessage(
                                        200,
                                        'OK',
                                        $randomizePdfFileName.'.zip',
                                        Storage::disk('local')->url($pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'),
                                        'split',
                                        $uuid,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        null
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.zip', $newFileProcSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $newFileNameWithoutExtension.'.zip',
                                        null,
                                        'split',
                                        $uuid,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.zip', $newFileProcSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $newFileNameWithoutExtension.'.zip',
                                        null,
                                        'split',
                                        $uuid,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
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
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split', 'Failed to download file from iLovePDF API !', 'null');
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF split failed!',
                                        $currentFileName.'.pdf',
                                        null,
                                        'split',
                                        $uuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        null
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName.'.pdf',
                                        null,
                                        'split',
                                        $uuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName.'.pdf',
                                        null,
                                        'split',
                                        $uuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
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
                                    return $this->returnCoreMessage(
                                        200,
                                        'OK',
                                        $newFileNameWithoutExtension.'.pdf',
                                        Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'),
                                        'split_delete',
                                        $uuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        null
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.pdf', $newFileProcSize, $uuid, 'FAIL', 'split_delete', 'Database connection error !',$ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $newFileNameWithoutExtension.'.pdf',
                                        null,
                                        'split_delete',
                                        $uuid,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileProcSize, $uuid, 'FAIL', 'split_delete', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $newFileNameWithoutExtension.'.pdf',
                                        null,
                                        'split_delete',
                                        $uuid,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
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
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split_delete', 'Failed to download file from iLovePDF API !', 'null');
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF split failed!',
                                        $currentFileName.'.pdf',
                                        null,
                                        'split_delete',
                                        $uuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        null
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split_delete', 'Database connection error !',$ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName.'.pdf',
                                        null,
                                        'split_delete',
                                        $uuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split_delete', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName.'.pdf',
                                        null,
                                        'split_delete',
                                        $uuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
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
                            return $this->returnCoreMessage(
                                200,
                                'PDF split failed!',
                                null,
                                null,
                                'split',
                                $uuid,
                                null,
                                null,
                                null,
                                null
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                            return $this->returnCoreMessage(
                                200,
                                'Database connection error !',
                                $currentFileName.'.pdf',
                                null,
                                'split',
                                $uuid,
                                null,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                            return $this->returnCoreMessage(
                                200,
                                'Eloquent transaction error !',
                                $currentFileName.'.pdf',
                                null,
                                'split',
                                $uuid,
                                null,
                                null,
                                null,
                                $e->getMessage()
                            );
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
                    return $this->returnCoreMessage(
                        200,
                        'PDF failed to upload !',
                        null,
                        null,
                        'split',
                        $uuid,
                        null,
                        null,
                        null,
                        null
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        $currentFileName.'.pdf',
                        null,
                        'split',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        $currentFileName.'.pdf',
                        null,
                        'split',
                        $uuid,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                }
            }
        }
    }
}
