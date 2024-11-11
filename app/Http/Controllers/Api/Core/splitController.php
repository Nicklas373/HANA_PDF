<?php

namespace App\Http\Controllers\Api\Core;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\appLogModel;
use App\Models\splitModel;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Spatie\PdfToImage\Pdf;

class splitController extends Controller
{
 	public function split(Request $request) {
		$validator = Validator::make($request->all(),[
            'file' => 'required',
            'action' => ['required', 'in:delete,split'],
            'fromPage' => ['nullable', 'numeric'],
            'toPage' => ['nullable', 'numeric'],
            'mergePDF' => ['required', 'in:true,false'],
            'customPageSplit' => ['nullable', 'regex:/^(all|[0-9,-]+)$/'],
            'customPageDelete' => ['nullable', 'regex:/^(all|[0-9,-]+)$/'],
            'usedMethod' => ['required', 'in:range,custom']
		]);

        // Generate Uni UUID
        $uuid = AppHelper::Instance()->generateUniqueUuid(splitModel::class, 'processId');
        $batchId = AppHelper::Instance()->generateUniqueUuid(splitModel::class, 'groupId');

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

		if ($validator->fails()) {
            appLogModel::create([
                'processId' => $uuid,
                'groupId' => $batchId,
                'errReason' => 'Validation Failed!',
                'errStatus' => $validator->messages()->first()
            ]);
            NotificationHelper::Instance()->sendErrNotify(
                null,
                null,
                $uuid,
                'FAIL',
                'split',
                'Validation failed',
                $validator->messages()->first()
            );
            return $this->returnDataMesage(
                400,
                'Validation failed',
                null,
                $batchId,
                null,
                $validator->messages()->first()
            );
		} else {
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
                $str = rand(1000,10000000);
                $loopCount = count($files);
                $altPoolFiles = array();
                $randomizePdfFileName = 'pdfSplit_'.substr(md5(uniqid($str)), 0, 8);
                if ($action == 'split' && $usedMethod == 'range') {
                    if (empty($fromPage) || empty($toPage)) {
                        return $this->returnDataMesage(
                            400,
                            'PDF Split failed !',
                            null,
                            $batchId,
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
                            $batchId,
                            null,
                            'Custom or selected page can not be empty !'
                        );
                    }
                } else if ($action == 'delete' && $usedMethod == 'custom') {
                    if (empty($customInputDeletePage)) {
                        return $this->returnDataMesage(
                            400,
                            'PDF Split failed !',
                            null,
                            $batchId,
                            null,
                            'Custom or selected page can not be empty !'
                        );
                    }
                }
                foreach ($files as $file) {
                    $currentFileName = basename($file);
                    $trimPhase1 = str_replace(' ', '_', $currentFileName);
                    $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                    $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                    if (file_exists($newFilePath)) {
                        array_push($altPoolFiles, $newFileNameWithoutExtension);
                    }
                }
                if ($loopCount == count($altPoolFiles)) {
                    foreach ($files as $file) {
                        $currentFileName = basename($file);
                        $trimPhase1 = str_replace(' ', '_', $currentFileName);
                        $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                        $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                        $fileSize = filesize($newFilePath);
                        $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        $procUuid = AppHelper::Instance()->generateUniqueUuid(splitModel::class, 'processId');
                        if ($tempPDF == 'true') {
                            if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf')) {
                                Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf');
                            }
                        }
                        if ($request->has('action')) {
                            if ($tempPDF == 'true') {
                                $mergeDBpdf = "true";
                                $mergePDF = true;
                                $newFileName = $currentFileName;
                            } else {
                                $mergeDBpdf = "false";
                                $mergePDF = false;
                                $newFileName = $randomizePdfFileName.'.zip';
                            }
                            appLogModel::create([
                                'processId' => $procUuid,
                                'groupId' => $batchId,
                                'errReason' => null,
                                'errStatus' => null
                            ]);
                            splitModel::create([
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
                                'groupId' => $batchId,
                                'processId' => $procUuid,
                                'batchName' => $newFileName,
                                'procStartAt' => $startProc,
                                'procEndAt' => null,
                                'procDuration' => null
                            ]);
                            if ($action == 'split') {
                                if ($usedMethod == 'range') {
                                    if ($fromPage != '' && $toPage != '') {
                                        try {
                                            $pdf = new Pdf($newFilePath);
                                            $pdfTotalPages = $pdf->pageCount();
                                            if ($toPage > $pdfTotalPages) {
                                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                                $duration = $end->diff($startProc);
                                                appLogModel::where('groupId', '=', $batchId)
                                                    ->update([
                                                        'errReason' => 'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                                        'errStatus' => null
                                                    ]);
                                                splitModel::where('groupId', '=', $batchId)
                                                    ->update([
                                                        'result' => false,
                                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                        'procDuration' => $duration->s.' seconds'
                                                    ]);
                                                NotificationHelper::Instance()->sendErrNotify(
                                                    $currentFileName,
                                                    $newFileSize,
                                                    $batchId,
                                                    'FAIL',
                                                    'split',
                                                    'PDF split failed!',
                                                    'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')'
                                                );
                                                return $this->returnDataMesage(
                                                    400,
                                                    'PDF Split failed !',
                                                    null,
                                                    $batchId,
                                                    null,
                                                    'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')'
                                                );
                                            } else if ($fromPage > $pdfTotalPages) {
                                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                                $duration = $end->diff($startProc);
                                                appLogModel::where('groupId', '=', $batchId)
                                                    ->update([
                                                        'errReason' => 'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                                        'errStatus' => null
                                                    ]);
                                                splitModel::where('groupId', '=', $batchId)
                                                    ->update([
                                                        'result' => false,
                                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                        'procDuration' => $duration->s.' seconds'
                                                    ]);
                                                NotificationHelper::Instance()->sendErrNotify(
                                                    $currentFileName,
                                                    $newFileSize,
                                                    $batchId,
                                                    'FAIL',
                                                    'split',
                                                    'PDF split failed!',
                                                    'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')'
                                                );
                                                return $this->returnDataMesage(
                                                    400,
                                                    'PDF Split failed !',
                                                    null,
                                                    $batchId,
                                                    null,
                                                    'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')'
                                                );
                                            } else if ($fromPage > $toPage) {
                                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                                $duration = $end->diff($startProc);
                                                appLogModel::where('groupId', '=', $batchId)
                                                    ->update([
                                                        'errReason' => 'First Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                                        'errStatus' => null
                                                    ]);
                                                splitModel::where('groupId', '=', $batchId)
                                                    ->update([
                                                        'result' => false,
                                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                        'procDuration' => $duration->s.' seconds'
                                                    ]);
                                                NotificationHelper::Instance()->sendErrNotify(
                                                    $currentFileName,
                                                    $newFileSize,
                                                    $batchId,
                                                    'FAIL',
                                                    'split',
                                                    'PDF split failed!',
                                                    'First Page has more page than last page ! (total page: '.$pdfTotalPages.')'
                                                );
                                                return $this->returnDataMesage(
                                                    400,
                                                    'PDF Split failed !',
                                                    null,
                                                    $batchId,
                                                    null,
                                                    'First Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                                );
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
                                            appLogModel::where('groupId', '=', $batchId)
                                                ->update([
                                                    'errReason' => 'Failed to count total PDF pages from '.$currentFileName,
                                                    'errStatus' => $e->getMessage()
                                                ]);
                                            splitModel::where('groupId', '=', $batchId)
                                                ->update([
                                                    'result' => false,
                                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                    'procDuration' => $duration->s.' seconds'
                                                ]);
                                            NotificationHelper::Instance()->sendErrNotify(
                                                $currentFileName,
                                                $newFileSize,
                                                $batchId,
                                                'FAIL',
                                                'split',
                                                'Failed to count total PDF pages from '.$currentFileName, $e->getMessage()
                                            );
                                            return $this->returnDataMesage(
                                                400,
                                                'PDF Split failed !',
                                                $e->getMessage(),
                                                $batchId,
                                                null,
                                                'Failed to count total PDF pages from '.$currentFileName
                                            );
                                        }
                                    } else {
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        appLogModel::where('groupId', '=', $batchId)
                                            ->update([
                                                'errReason' => 'First or Last page can not empty',
                                                'errStatus' => null
                                            ]);
                                        splitModel::where('groupId', '=', $batchId)
                                            ->update([
                                                'result' => false,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' => $duration->s.' seconds'
                                            ]);
                                        NotificationHelper::Instance()->sendErrNotify(
                                            $currentFileName,
                                            $newFileSize,
                                            $batchId,
                                            'FAIL',
                                            'split',
                                            'First or Last page can not empty'
                                        );
                                        return $this->returnDataMesage(
                                            400,
                                            'PDF Split failed !',
                                            null,
                                            $batchId,
                                            null,
                                            'First or Last page can not empty'
                                        );
                                    }
                                } else if ($usedMethod == 'custom') {
                                    if (is_numeric($customInputSplitPage)) {
                                        try {
                                            $pdf = new Pdf($newFilePath);
                                            $pdfTotalPages = $pdf->pageCount();
                                            if ($customInputSplitPage > $pdfTotalPages) {
                                                appLogModel::where('groupId', '=', $batchId)
                                                    ->update([
                                                        'errReason' => 'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                                        'errStatus' => null
                                                    ]);
                                                splitModel::where('groupId', '=', $batchId)
                                                    ->update([
                                                        'customSplitPage' => $customInputSplitPage,
                                                        'result' => false,
                                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                        'procDuration' => $duration->s.' seconds'
                                                    ]);
                                                NotificationHelper::Instance()->sendErrNotify(
                                                    $currentFileName,
                                                    $newFileSize,
                                                    $batchId,
                                                    'FAIL',
                                                    'split',
                                                    'PDF split failed!',
                                                    'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')'
                                                );
                                                return $this->returnDataMesage(
                                                    400,
                                                    'PDF Split failed !',
                                                    null,
                                                    $batchId,
                                                    null,
                                                    'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')'
                                                );
                                            } else {
                                                $newPageRanges = $customInputSplitPage;
                                            }
                                        } catch (\Exception $e) {
                                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                            $duration = $end->diff($startProc);
                                            appLogModel::where('groupId', '=', $batchId)
                                                ->update([
                                                    'errReason' => 'Failed to count total PDF pages from '.$currentFileName,
                                                    'errStatus' => $e->getMessage()
                                                ]);
                                            splitModel::where('groupId', '=', $batchId)
                                                ->update([
                                                    'result' => false,
                                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                    'procDuration' => $duration->s.' seconds'
                                                ]);
                                            NotificationHelper::Instance()->sendErrNotify(
                                                $currentFileName,
                                                $newFileSize,
                                                $batchId,
                                                'FAIL',
                                                'split',
                                                'Failed to count total PDF pages from '.$currentFileName, $e->getMessage()
                                            );
                                            return $this->returnDataMesage(
                                                400,
                                                'PDF Split failed !',
                                                $e->getMessage(),
                                                $batchId,
                                                null,
                                                'Failed to count total PDF pages from '.$currentFileName
                                            );
                                        }
                                    } else if (is_string($customInputSplitPage)) {
                                        $newPageRanges = strtolower($customInputSplitPage);
                                    } else {
                                        $newPageRanges = $customInputSplitPage;
                                    }
                                }
                            } else {
                                if (is_numeric($customInputDeletePage)) {
                                    try {
                                        $pdf = new Pdf($newFilePath);
                                        $pdfTotalPages = $pdf->pageCount();
                                        if ($customInputDeletePage > $pdfTotalPages) {
                                            appLogModel::where('groupId', '=', $batchId)
                                                ->update([
                                                    'errReason' => 'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                                    'errStatus' => null
                                                ]);
                                            splitModel::where('groupId', '=', $batchId)
                                                ->update([
                                                    'customDeletePage' => $customInputDeletePage,
                                                    'result' => false,
                                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                    'procDuration' => $duration->s.' seconds'
                                                ]);
                                            NotificationHelper::Instance()->sendErrNotify(
                                                $currentFileName,
                                                $newFileSize,
                                                $batchId,
                                                'FAIL',
                                                'split',
                                                'PDF split failed!',
                                                'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')'
                                            );
                                            return $this->returnDataMesage(
                                                400,
                                                'PDF Split failed !',
                                                null,
                                                $batchId,
                                                null,
                                                'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')'
                                            );
                                        } else {
                                            $newPageRanges = $customInputDeletePage;
                                        }
                                    } catch (\Exception $e) {
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        appLogModel::where('groupId', '=', $batchId)
                                            ->update([
                                                'errReason' => 'Failed to count total PDF pages from '.$currentFileName,
                                                'errStatus' => $e->getMessage()
                                            ]);
                                        splitModel::where('groupId', '=', $batchId)
                                            ->update([
                                                'result' => false,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' => $duration->s.' seconds'
                                            ]);
                                        NotificationHelper::Instance()->sendErrNotify(
                                            $currentFileName,
                                            $newFileSize,
                                            $batchId,
                                            'FAIL',
                                            'split',
                                            'Failed to count total PDF pages from '.$currentFileName, $e->getMessage()
                                        );
                                        return $this->returnDataMesage(
                                            400,
                                            'PDF Split failed !',
                                            $e->getMessage(),
                                            $batchId,
                                            null,
                                            'Failed to count total PDF pages from '.$currentFileName
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
                            } catch (\Exception $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                splitModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'customSplitPage' => $customInputSplitPage,
                                        'customDeletePage' => $customInputDeletePage,
                                        'fixedRange' => $newPageRanges,
                                        'result' => false,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' => $duration->s.' seconds'
                                    ]);
                                NotificationHelper::Instance()->sendErrNotify(
                                    $currentFileName,
                                    $fileSize,
                                    $batchId,
                                    'FAIL',
                                    'split',
                                    'iLovePDF API Error !, Catch on Exception',
                                    $e->getMessage()
                                );
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Split failed !',
                                    $e->getMessage(),
                                    $batchId,
                                    null,
                                    'iLovePDF API Error !, Catch on Exception'
                                );
                            }
                            if ($action == 'split') {
                                if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'))) {
                                    $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'));
                                    $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => null,
                                            'errStatus' => null
                                        ]);
                                    splitModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'customSplitPage' => $customInputSplitPage,
                                            'customDeletePage' => $customInputDeletePage,
                                            'fixedRange' => $newPageRanges,
                                            'result' => true,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' => $duration->s.' seconds'
                                        ]);
                                    return $this->returnCoreMessage(
                                        200,
                                        'OK',
                                        $newFileNameWithoutExtension.'.pdf',
                                        Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'),
                                        'split',
                                        $batchId,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        null
                                    );
                                } else if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'))) {
                                    $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'));
                                    $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => null,
                                            'errStatus' => null
                                        ]);
                                    splitModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'customSplitPage' => $customInputSplitPage,
                                            'customDeletePage' => $customInputDeletePage,
                                            'fixedRange' => $newPageRanges,
                                            'result' => true,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' => $duration->s.' seconds'
                                        ]);
                                    return $this->returnCoreMessage(
                                        200,
                                        'OK',
                                        $randomizePdfFileName.'.zip',
                                        Storage::disk('local')->url($pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'),
                                        'split',
                                        $batchId,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        null
                                    );
                                } else if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-'.$newPageRanges.'.pdf'))) {
                                    $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-'.$newPageRanges.'.pdf'));
                                    $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => null,
                                            'errStatus' => null
                                        ]);
                                    splitModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'customSplitPage' => $customInputSplitPage,
                                            'customDeletePage' => $customInputDeletePage,
                                            'fixedRange' => $newPageRanges,
                                            'result' => true,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' => $duration->s.' seconds'
                                        ]);
                                    return $this->returnCoreMessage(
                                        200,
                                        'OK',
                                        $randomizePdfFileName.'.zip',
                                        Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-'.$newPageRanges.'.pdf'),
                                        'split',
                                        $batchId,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        null
                                    );
                                } else {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => 'Failed to download file from iLovePDF API !',
                                            'errStatus' => null
                                        ]);
                                    splitModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'customSplitPage' => $customInputSplitPage,
                                            'customDeletePage' => $customInputDeletePage,
                                            'fixedRange' => $newPageRanges,
                                            'result' => false,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' => $duration->s.' seconds'
                                        ]);
                                    NotificationHelper::Instance()->sendErrNotify(
                                        $currentFileName.'.pdf',
                                        $newFileSize,
                                        $batchId,
                                        'FAIL',
                                        'split',
                                        'Failed to download file from iLovePDF API !',
                                        null
                                    );
                                    return $this->returnDataMesage(
                                        400,
                                        'PDF Split failed !',
                                        null,
                                        $batchId,
                                        null,
                                        'Failed to download file from iLovePDF API !'
                                    );
                                }
                            } else if ($action == 'delete') {
                                if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'))) {
                                    $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'));
                                    $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => null,
                                            'errStatus' => null
                                        ]);
                                    splitModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'customSplitPage' => $customInputSplitPage,
                                            'customDeletePage' => $customInputDeletePage,
                                            'fixedRange' => $newPageRanges,
                                            'result' => true,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' => $duration->s.' seconds'
                                        ]);
                                    return $this->returnCoreMessage(
                                        200,
                                        'OK',
                                        $newFileNameWithoutExtension.'.pdf',
                                        Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'),
                                        'split_delete',
                                        $batchId,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        null
                                    );
                                } else {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => 'Failed to download file from iLovePDF API !',
                                            'errStatus' => null
                                        ]);
                                    splitModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'customSplitPage' => $customInputSplitPage,
                                            'customDeletePage' => $customInputDeletePage,
                                            'fixedRange' => $newPageRanges,
                                            'result' => false,
                                            'procDuration' => $duration->s.' seconds'
                                        ]);
                                    NotificationHelper::Instance()->sendErrNotify(
                                        $currentFileName.'.pdf',
                                        $newFileSize,
                                        $batchId,
                                        'FAIL',
                                        'split_delete',
                                        'Failed to download file from iLovePDF API !',
                                        null
                                    );
                                    return $this->returnDataMesage(
                                        400,
                                        'PDF Split failed !',
                                        null,
                                        $batchId,
                                        null,
                                        'Failed to download file from iLovePDF API !'
                                    );
                                }
                            }
                        } else {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            appLogModel::where('groupId', '=', $batchId)
                                ->update([
                                    'errReason' => 'Invalid split request method !',
                                    'errStatus' => null
                                ]);
                            splitModel::where('groupId', '=', $batchId)
                                ->update([
                                    'result' => false,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' => $duration->s.' seconds'
                                ]);
                            NotificationHelper::Instance()->sendErrNotify(
                                null,
                                null,
                                $batchId,
                                'FAIL',
                                'Invalid split request method !',
                                null
                            );
                            return $this->returnDataMesage(
                                400,
                                'PDF Split failed !',
                                null,
                                $batchId,
                                null,
                                'Invalid split request method !'
                            );
                        }
                    }
                } else {
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    appLogModel::where('groupId', '=', $batchId)
                        ->update([
                            'errReason' => 'File not found on the server',
                            'errStatus' => 'File not found on our end, please try again'
                        ]);
                    splitModel::where('groupId', '=', $batchId)
                        ->update([
                            'result' => false,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                    NotificationHelper::Instance()->sendErrNotify(
                        $currentFileName,
                        null,
                        $batchId,
                        'FAIL',
                        'split',
                        'File not found on the server',
                        'File not found on our end, please try again'
                    );
                    return $this->returnDataMesage(
                        400,
                        'PDF Split failed !',
                        null,
                        $batchId,
                        null,
                        'File not found on our end, please try again'
                    );
                }
            } else {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                appLogModel::create([
                    'processId' => $uuid,
                    'groupId' => $batchId,
                    'errReason' => 'PDF failed to upload !',
                    'errStatus' => null
                ]);
                splitModel::create([
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
                    'groupId' => $batchId,
                    'processId' => $uuid,
                    'batchName' => null,
                    'procStartAt' => $startProc,
                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                    'procDuration' => $duration->s.' seconds'
                ]);
                NotificationHelper::Instance()->sendErrNotify(
                    null,
                    null,
                    $batchId,
                    'FAIL',
                    'PDF failed to upload !',
                    null
                );
                return $this->returnDataMesage(
                    400,
                    'PDF Split failed !',
                    null,
                    $batchId,
                    null,
                    'PDF failed to upload !'
                );
            }
        }
    }
}
