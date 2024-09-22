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
            'customPageSplit' => ['required', 'regex:/^(all|[0-9,-]+)$/'],
            'customPageDelete' => ['required', 'regex:/^(all|[0-9,-]+)$/'],
            'usedMethod' => ['required', 'in:range,custom']
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
                    'errReason' => 'Validation Failed!',
                    'errStatus' => $validator->messages()->first()
                ]);
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'FAIL','split','Validation failed',$validator->messages()->first(), true);
                return $this->returnDataMesage(
                    401,
                    'Validation failed',
                    null,
                    null,
                    $validator->messages()->first()
                );
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'FAIL','split','Database connection error !',$ex->getMessage(), false);
                return $this->returnDataMesage(
                    500,
                    'Database connection error !',
                    null,
                    null,
                    $ex->getMessage()
                );
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL','split','Eloquent transaction error !', $e->getMessage(), false);
                return $this->returnDataMesage(
                    500,
                    'Eloquent transaction error !',
                    null,
                    null,
                    $ex->getMessage()
                );
            }
		}  else {
            if ($request->has('file')) {
                $files = $request->post('file');
                $action = $request->post('action');
                $fromPage = $request->post('fromPage');
                $toPage = $request->post('toPage');
                $tempPDF = $request->post('mergePDF');
                $usedMethod = $request->post('usedMethod');
                $customInputSplitPage = $request->post('customPageSplit');
                $customInputDeletePage = $request->post('customPageDelete');
                $newPageRanges = '';
                $pdfEncKey = bin2hex(random_bytes(16));
                $pdfUpload_Location = env('PDF_UPLOAD');
                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                $pdfDownload_Location = $pdfProcessed_Location;
                $batchValue = false;
                $batchId = null;
                $str = rand(1000,10000000);
                if ($action == 'split' && $usedMethod == 'range') {
                    if (empty($fromPage) || empty($toPage)) {
                        return $this->returnDataMesage(
                            400,
                            'PDF Split failed !',
                            null,
                            null,
                            'First or last page can not be empty !'
                        );
                    }
                } else if ($action == 'split' && $usedMethod == 'custom') {
                    if (empty($customInputSplitPage)) {
                        return $this->returnDataMesage(
                            400,
                            'PDF Split failed !',
                            null,
                            null,
                            'Custom or selected page can not be empty !'
                        );
                    }
                }  else if ($action == 'delete' && $usedMethod == 'custom') {
                    if (empty($customInputDeletePage)) {
                        return $this->returnDataMesage(
                            400,
                            'PDF Split failed !',
                            null,
                            null,
                            'Custom or selected page can not be empty !'
                        );
                    }
                }
                foreach ($files as $file) {
                    $currentFileName = basename($file);
                    $trimPhase1 = str_replace(' ', '_', $currentFileName);
                    $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                    $randomizePdfFileName = 'pdfSplit_'.substr(md5(uniqid($str)), 0, 8);
                    $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                    $fileSize = filesize($newFilePath);
                    $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                    if ($tempPDF == 'true') {
                        if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf')) {
                            Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf');
                        }
                    }
                    if ($request->has('action')) {
                        if ($tempPDF == 'true') {
                            $mergeDBpdf = "true";
                            $mergePDF = true;
                        } else {
                            $mergeDBpdf = "false";
                            $mergePDF = false;
                        }
                        if ($action == 'split') {
                            if ($usedMethod == 'range') {
                                if ($fromPage != '' && $toPage != '') {
                                    try {
                                        $pdf = new Pdf($newFilePath);
                                        $pdfTotalPages = $pdf->pageCount();
                                        if ($toPage > $pdfTotalPages) {
                                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                            $duration = $end->diff($startProc);
                                            try {
                                                DB::table('appLogs')->insert([
                                                    'processId' => $uuid,
                                                    'errReason' => 'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                                    'errStatus' => null
                                                ]);
                                                DB::table('pdfSplit')->insert([
                                                    'fileName' => $currentFileName,
                                                    'fileSize' => $newFileSize,
                                                    'fromPage' => $fromPage,
                                                    'toPage' => $toPage,
                                                    'customSplitPage' => null,
                                                    'customDeletePage' => null,
                                                    'fixedRange' => null,
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
                                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'PDF split failed!', 'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')', true);
                                                return $this->returnDataMesage(
                                                    400,
                                                    'PDF Split failed !',
                                                    null,
                                                    null,
                                                    'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')'
                                                );

                                            } catch (QueryException $ex) {
                                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                                return $this->returnDataMesage(
                                                    500,
                                                    'Database connection error !',
                                                    null,
                                                    null,
                                                    $ex->getMessage()
                                                );
                                            } catch (\Exception $e) {
                                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                                return $this->returnDataMesage(
                                                    500,
                                                    'Eloquent transaction error !',
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
                                                    'errReason' => 'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                                    'errStatus' => null
                                                ]);
                                                DB::table('pdfSplit')->insert([
                                                    'fileName' => $currentFileName,
                                                    'fileSize' => $newFileSize,
                                                    'fromPage' => $fromPage,
                                                    'toPage' => $toPage,
                                                    'customSplitPage' => null,
                                                    'customDeletePage' => null,
                                                    'fixedRange' => null,
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
                                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'split', 'PDF split failed!', 'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')', true);
                                                return $this->returnDataMesage(
                                                    400,
                                                    'PDF Split failed !',
                                                    null,
                                                    null,
                                                    'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')'
                                                );
                                            } catch (QueryException $ex) {
                                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                                return $this->returnDataMesage(
                                                    500,
                                                    'Database connection error !',
                                                    null,
                                                    null,
                                                    $ex->getMessage()
                                                );
                                            } catch (\Exception $e) {
                                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                                return $this->returnDataMesage(
                                                    500,
                                                    'Eloquent transaction error !',
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
                                                    'errReason' => 'First Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                                    'errStatus' => null
                                                ]);
                                                DB::table('pdfSplit')->insert([
                                                    'fileName' => $currentFileName,
                                                    'fileSize' => $newFileSize,
                                                    'fromPage' => $fromPage,
                                                    'toPage' => $toPage,
                                                    'customSplitPage' => null,
                                                    'customDeletePage' => null,
                                                    'fixedRange' => null,
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
                                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'split', 'PDF split failed!', 'First Page has more page than last page ! (total page: '.$pdfTotalPages.')', true);
                                                return $this->returnDataMesage(
                                                    400,
                                                    'PDF Split failed !',
                                                    null,
                                                    null,
                                                    'First Page has more page than last page ! (total page: '.$pdfTotalPages.')'
                                                );
                                            } catch (QueryException $ex) {
                                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                                return $this->returnDataMesage(
                                                    500,
                                                    'Database connection error !',
                                                    null,
                                                    null,
                                                    $ex->getMessage()
                                                );
                                            } catch (\Exception $e) {
                                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                                return $this->returnDataMesage(
                                                    500,
                                                    'Eloquent transaction error !',
                                                    null,
                                                    null,
                                                    $e->getMessage()
                                                );
                                            }
                                        } else {
                                            if ($mergeDBpdf == "true") {
                                                $newPageRanges = $fromPage.'-'.$toPage;
                                            } else if ($mergeDBpdf == "false") {
                                                $pdfStartPages = $fromPage;
                                                $pdfTotalPages = $toPage;
                                                while($pdfStartPages <= intval($pdfTotalPages))
                                                {
                                                    $pdfArrayPages[] = $pdfStartPages;
                                                    $pdfStartPages += 1;
                                                }
                                                $newPageRanges = implode(', ', $pdfArrayPages);
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        try {
                                            DB::table('appLogs')->insert([
                                                'processId' => $uuid,
                                                'errReason' => 'Failed to count total PDF pages from '.$currentFileName,
                                                'errStatus' => $e->getMessage()
                                            ]);
                                            DB::table('pdfSplit')->insert([
                                                'fileName' => $currentFileName,
                                                'fileSize' => $newFileSize,
                                                'fromPage' => $fromPage,
                                                'toPage' => $toPage,
                                                'customSplitPage' => null,
                                                'customDeletePage' => null,
                                                'fixedRange' => null,
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
                                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Failed to count total PDF pages from '.$currentFileName, $e->getMessage(), false);
                                            return $this->returnDataMesage(
                                                400,
                                                'PDF Split failed !',
                                                $e->getMessage(),
                                                null,
                                                'Failed to count total PDF pages from '.$currentFileName
                                            );
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                            return $this->returnDataMesage(
                                                500,
                                                'Database connection error !',
                                                null,
                                                null,
                                                $ex->getMessage()
                                            );
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                            return $this->returnDataMesage(
                                                500,
                                                'Eloquent transaction error !',
                                                null,
                                                null,
                                                $e->getMessage()
                                            );
                                        }
                                    }
                                } else {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => 'First or Last page can not empty',
                                            'errStatus' => null
                                        ]);
                                        DB::table('pdfSplit')->insert([
                                            'fileName' => $currentFileName,
                                            'fileSize' => $newFileSize,
                                            'fromPage' => $fromPage,
                                            'toPage' => $toPage,
                                            'customSplitPage' => null,
                                            'customDeletePage' => null,
                                            'fixedRange' => null,
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
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'PDF split failed!', 'First or Last page can not empty', true);
                                        return $this->returnDataMesage(
                                            400,
                                            'PDF Split failed !',
                                            null,
                                            null,
                                            'First or Last page can not empty'
                                        );
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                        return $this->returnDataMesage(
                                            500,
                                            'Database connection error !',
                                            null,
                                            null,
                                            $ex->getMessage()
                                        );
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                        return $this->returnDataMesage(
                                            500,
                                            'Eloquent transaction error !',
                                            null,
                                            null,
                                            $e->getMessage()
                                        );
                                    }
                                }
                            } else if ($usedMethod == 'custom') {
                                if (is_numeric($customInputSplitPage)) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        $pdfTotalPages = AppHelper::instance()->count($newFilePath);
                                        if ($customInputSplitPage > $pdfTotalPages) {
                                            DB::table('appLogs')->insert([
                                                'processId' => $uuid,
                                                'errReason' => 'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                                'errStatus' => null
                                            ]);
                                            DB::table('pdfSplit')->insert([
                                                'fileName' => $currentFileName,
                                                'fileSize' => $newFileSize,
                                                'fromPage' => $fromPage,
                                                'toPage' => $toPage,
                                                'customSplitPage' => null,
                                                'customDeletePage' => null,
                                                'fixedRange' => null,
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
                                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'PDF split failed!', 'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')', true);
                                            return $this->returnDataMesage(
                                                400,
                                                'PDF Split failed !',
                                                null,
                                                null,
                                                'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')'
                                            );
                                        } else {
                                            $newPageRanges = $customInputSplitPage;
                                        }
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                        return $this->returnDataMesage(
                                            500,
                                            'Database connection error !',
                                            null,
                                            null,
                                            $ex->getMessage()
                                        );
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                        return $this->returnDataMesage(
                                            500,
                                            'Eloquent transaction error !',
                                            null,
                                            null,
                                            $e->getMessage()
                                        );
                                    }
                                } else if (is_string($customInputSplitPage)) {
                                    $newPageRanges = strtolower($customInputSplitPage);
                                } else {
                                    $newPageRanges = $customInputSplitPage;
                                }
                            }
                            if (!$mergePDF) {
                                $newFileNameWithoutExtension =  str_replace('.', '_', $trimPhase1.'_page');
                            }
                        } else {
                            if (is_numeric($customInputDeletePage)) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    $pdfTotalPages = AppHelper::instance()->count($newFilePath);
                                    if ($customInputDeletePage > $pdfTotalPages) {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => 'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                            'errStatus' => null
                                        ]);
                                        DB::table('pdfSplit')->insert([
                                            'fileName' => $currentFileName,
                                            'fileSize' => $newFileSize,
                                            'fromPage' => $fromPage,
                                            'toPage' => $toPage,
                                            'customSplitPage' => null,
                                            'customDeletePage' => null,
                                            'fixedRange' => null,
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
                                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'PDF split failed!', 'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')', true);
                                        return $this->returnDataMesage(
                                            400,
                                            'PDF Split failed !',
                                            null,
                                            null,
                                            'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')'
                                        );
                                    } else {
                                        $newPageRanges = $customInputDeletePage;
                                    }
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Database connection error !',
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Eloquent transaction error !',
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                }
                            } else if (is_string($customInputDeletePage)) {
                                $newPageRanges = strtolower($customInputDeletePage);
                            } else {
                                $newPageRanges = $customInputDeletePage;
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
                                $ilovepdfTask->setRanges($newPageRanges);
                                if ($mergePDF == 1) {
                                    $ilovepdfTask->setMergeAfter(true);
                                } else {
                                    $ilovepdfTask->setMergeAfter(false);
                                }
                            } else if ($action == 'delete') {
                                $ilovepdfTask->setRemovePages($newPageRanges);
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
                                    'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customSplitPage' => $customInputSplitPage,
                                    'customDeletePage' => $customInputDeletePage,
                                    'fixedRange' => $newPageRanges,
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on StartException', $e->getMessage(), true);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Split failed !',
                                    $e->getMessage(),
                                    null,
                                    'iLovePDF API Error !, Catch on StartException'
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Database connection error !',
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Eloquent transaction error !',
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
                                    'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customSplitPage' => $customInputSplitPage,
                                    'customDeletePage' => $customInputDeletePage,
                                    'fixedRange' => $newPageRanges,
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage(), true);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Split failed !',
                                    $e->getMessage(),
                                    null,
                                    'iLovePDF API Error !, Catch on AuthException'
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Database connection error !',
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Eloquent transaction error !',
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
                                    'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customSplitPage' => $customInputSplitPage,
                                    'customDeletePage' => $customInputDeletePage,
                                    'fixedRange' => $newPageRanges,
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage(), true);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Split failed !',
                                    $e->getMessage(),
                                    null,
                                    'iLovePDF API Error !, Catch on UploadException'
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Database connection error !',
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Eloquent transaction error !',
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
                                    'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customSplitPage' => $customInputSplitPage,
                                    'customDeletePage' => $customInputDeletePage,
                                    'fixedRange' => $newPageRanges,
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage(), true);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Split failed !',
                                    $e->getMessage(),
                                    null,
                                    'iLovePDF API Error !, Catch on ProcessException'
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Database connection error !',
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Eloquent transaction error !',
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
                                    'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customSplitPage' => $customInputSplitPage,
                                    'customDeletePage' => $customInputDeletePage,
                                    'fixedRange' => $newPageRanges,
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage(), true);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Split failed !',
                                    $e->getMessage(),
                                    null,
                                    'iLovePDF API Error !, Catch on DownloadException'
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Database connection error !',
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Eloquent transaction error !',
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
                                    'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customSplitPage' => $customInputSplitPage,
                                    'customDeletePage' => $customInputDeletePage,
                                    'fixedRange' => $newPageRanges,
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage(), true);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Split failed !',
                                    $e->getMessage(),
                                    null,
                                    'iLovePDF API Error !, Catch on TaskException'
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Database connection error !',
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Eloquent transaction error !',
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
                                    'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customSplitPage' => $customInputSplitPage,
                                    'customDeletePage' => $customInputDeletePage,
                                    'fixedRange' => $newPageRanges,
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on PathException', $e->getMessage(), true);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Split failed !',
                                    $e->getMessage(),
                                    null,
                                    'iLovePDF API Error !, Catch on PathException'
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Database connection error !',
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Eloquent transaction error !',
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
                                    'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customSplitPage' => $customInputSplitPage,
                                    'customDeletePage' => $customInputDeletePage,
                                    'fixedRange' => $newPageRanges,
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
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'iLovePDF API Error !, Catch on Exception', $e->getMessage(), true);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Split failed !',
                                    $e->getMessage(),
                                    null,
                                    'iLovePDF API Error !, Catch on Exception'
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Database connection error !',
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                return $this->returnDataMesage(
                                    500,
                                    'Eloquent transaction error !',
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
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customSplitPage' => $customInputSplitPage,
                                        'customDeletePage' => $customInputDeletePage,
                                        'fixedRange' => $newPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => true,
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
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.pdf', $newFileProcSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Database connection error !',
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.pdf', $newFileProcSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Eloquent transaction error !',
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
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customSplitPage' => $customInputSplitPage,
                                        'customDeletePage' => $customInputDeletePage,
                                        'fixedRange' => $newPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => true,
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
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.zip', $newFileProcSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Database connection error !',
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.zip', $newFileProcSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Eloquent transaction error !',
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
                                        'errReason' => 'Failed to download file from iLovePDF API !',
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customSplitPage' => $customInputSplitPage,
                                        'customDeletePage' => $customInputDeletePage,
                                        'fixedRange' => $newPageRanges,
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
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split', 'Failed to download file from iLovePDF API !', 'null', true);
                                    return $this->returnDataMesage(
                                        400,
                                        'PDF Split failed !',
                                        null,
                                        null,
                                        'Failed to download file from iLovePDF API !'
                                    );

                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Database connection error !',
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Eloquent transaction error !',
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
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customSplitPage' => $customInputSplitPage,
                                        'customDeletePage' => $customInputDeletePage,
                                        'fixedRange' => $newPageRanges,
                                        'mergePDF' => $mergeDBpdf,
                                        'action' => $action,
                                        'result' => true,
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
                                    NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.pdf', $newFileProcSize, $uuid, 'FAIL', 'split_delete', 'Database connection error !',$ex->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Database connection error !',
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileProcSize, $uuid, 'FAIL', 'split_delete', 'Eloquent transaction error !', $e->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Eloquent transaction error !',
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
                                        'errReason' => 'Failed to download file from iLovePDF API !',
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customSplitPage' => $customInputSplitPage,
                                        'customDeletePage' => $customInputDeletePage,
                                        'fixedRange' => $newPageRanges,
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
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split_delete', 'Failed to download file from iLovePDF API !', 'null', true);
                                    return $this->returnDataMesage(
                                        400,
                                        'PDF Split failed !',
                                        null,
                                        null,
                                        'Failed to download file from iLovePDF API !'
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split_delete', 'Database connection error !',$ex->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Database connection error !',
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'split_delete', 'Eloquent transaction error !', $e->getMessage(), false);
                                    return $this->returnDataMesage(
                                        500,
                                        'Eloquent transaction error !',
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
                                'errReason' => 'Invalid split request method !',
                                'errStatus' => null
                            ]);
                            DB::table('pdfSplit')->insert([
                                'fileName' => null,
                                'fileSize' => null,
                                'fromPage' => null,
                                'toPage' => null,
                                'customSplitPage' => null,
                                'customDeletePage' => null,
                                'fixedRange' => null,
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
                            NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'Invalid split request method !', null, true);
                            return $this->returnDataMesage(
                                400,
                                'PDF Split failed !',
                                null,
                                null,
                                'Invalid split request method !'
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Eloquent transaction error !',
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
                        'errReason' => 'PDF failed to upload !',
                        'errStatus' => null
                    ]);
                    DB::table('pdfSplit')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'fromPage' => null,
                        'toPage' => null,
                        'customSplitPage' => null,
                        'customDeletePage' => null,
                        'fixedRange' => null,
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
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'PDF failed to upload !', null, true);
                    return $this->returnDataMesage(
                        400,
                        'PDF Split failed !',
                        null,
                        null,
                        'PDF failed to upload !'
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'split', 'Database connection error !',$ex->getMessage(), false);
                    return $this->returnDataMesage(
                        500,
                        'Database connection error !',
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'split', 'Eloquent transaction error !', $e->getMessage(), false);
                    return $this->returnDataMesage(
                        500,
                        'Eloquent transaction error !',
                        null,
                        null,
                        $e->getMessage()
                    );
                }
            }
        }
    }
}
