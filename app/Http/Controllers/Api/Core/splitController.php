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
                'errReason' => $validator->messages()->first(),
                'errStatus' => 'Validation Failed!'
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
                    try {
                        if (Storage::disk('minio')->exists($pdfUpload_Location.'/'.$trimPhase1)) {
                            array_push($altPoolFiles, $newFileNameWithoutExtension);
                        }
                    } catch (\Exception $e) {
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        appLogModel::create([
                            'processId' => $procUuid,
                            'groupId' => $batchId,
                            'errReason' => $e->getMessage(),
                            'errStatus' => $currentFileName.' could not be found in the object storage'
                        ]);
                        splitModel::create([
                            'fileName' => $currentFileName,
                            'fileSize' => null,
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
                            'batchName' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        NotificationHelper::Instance()->sendErrNotify(
                            $currentFileName,
                            $newFileSize,
                            $batchId,
                            'FAIL',
                            'split',
                            $currentFileName.' could not be found in the object storage',
                            $e->getMessage()
                        );
                        return $this->returnDataMesage(
                            400,
                            'PDF Split failed !',
                            null,
                            $batchId,
                            null,
                            $currentFileName.' could not be found in the object storage'
                        );
                    }
                }
                if ($loopCount == count($altPoolFiles)) {
                    foreach ($files as $file) {
                        $currentFileName = basename($file);
                        $trimPhase1 = str_replace(' ', '_', $currentFileName);
                        $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                        $newFormattedFilename = str_replace('_pdf', '', $newFileNameWithoutExtension);
                        $minioUpload = Storage::disk('minio')->get($pdfUpload_Location.'/'.$currentFileName);
                        file_put_contents(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName), $minioUpload);
                        $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName);
                        $fileSize = Storage::disk('minio')->size($pdfUpload_Location.'/'.$trimPhase1);
                        $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        $procUuid = AppHelper::Instance()->generateUniqueUuid(splitModel::class, 'processId');
                        if ($tempPDF == 'true') {
                            if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFormattedFilename.'.pdf')) {
                                Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFormattedFilename.'.pdf');
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
                                        $pdfTotalPages = AppHelper::instance()->count($newFilePath);
                                        if ($toPage > $pdfTotalPages) {
                                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                            $duration = $end->diff($startProc);
                                            appLogModel::where('groupId', '=', $batchId)
                                                ->update([
                                                    'errReason' => 'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                                    'errStatus' => 'PDF split failed!'
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
                                            Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
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
                                                    'errStatus' => 'PDF split failed!'
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
                                            Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
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
                                                    'errStatus' => 'PDF split failed!'
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
                                            Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
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
                                    } else {
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        appLogModel::where('groupId', '=', $batchId)
                                            ->update([
                                                'errReason' => 'First or Last page can not empty',
                                                'errStatus' => 'PDF Split failed !'
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
                                        Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
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
                                        $pdfTotalPages = AppHelper::instance()->count($newFilePath);
                                        if ($customInputSplitPage > $pdfTotalPages) {
                                            appLogModel::where('groupId', '=', $batchId)
                                                ->update([
                                                    'errReason' => 'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                                    'errStatus' => 'PDF Split failed !'
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
                                            Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
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
                                    } else if (is_string($customInputSplitPage)) {
                                        $newPageRanges = strtolower($customInputSplitPage);
                                    } else {
                                        $newPageRanges = $customInputSplitPage;
                                    }
                                }
                            } else {
                                if (is_numeric($customInputDeletePage)) {
                                    $pdfTotalPages = AppHelper::instance()->count($newFilePath);
                                    if ($customInputDeletePage > $pdfTotalPages) {
                                        appLogModel::where('groupId', '=', $batchId)
                                            ->update([
                                                'errReason' => 'Input Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                                'errStatus' => 'PDF split failed!'
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
                                        Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
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
                                } else if (is_string($customInputDeletePage)) {
                                    $newPageRanges = strtolower($customInputDeletePage);
                                } else {
                                    $newPageRanges = $customInputDeletePage;
                                }
                            }
                            try {
                                Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                                $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                $ilovepdfTask = $ilovepdf->newTask('split');
                                $ilovepdfTask->setFileEncryption($pdfEncKey);
                                $ilovepdfTask->setEncryptKey($pdfEncKey);
                                $ilovepdfTask->setEncryption(true);
                                $pdfTempUrl =  Storage::disk('minio')->temporaryUrl(
                                    $pdfUpload_Location.'/'.$trimPhase1,
                                    now()->addSeconds(30)
                                );
                                $pdfFile = $ilovepdfTask->addFileFromUrl($pdfTempUrl);
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
                                $ilovepdfTask->setOutputFileName($newFormattedFilename);
                                $ilovepdfTask->execute();
                                $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                                $ilovepdfTask->delete();
                            } catch (\Exception $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => $e->getMessage(),
                                        'errStatus' => 'iLovePDF API Error !, Catch on Exception'
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
                                Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
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
                                if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFormattedFilename.'.pdf'))) {
                                    Storage::disk('minio')->put(
                                        $pdfDownload_Location.'/'.$newFormattedFilename.'.pdf',
                                        file_get_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFormattedFilename.'.pdf'))
                                    );
                                    Storage::disk('local')->delete('public/'.$newFormattedFilename.'.pdf');
                                    $fileProcSize = Storage::disk('minio')->size($pdfDownload_Location.'/'.$newFormattedFilename.'.pdf');
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
                                        $newFormattedFilename.'.pdf',
                                        Storage::disk('minio')->temporaryUrl(
                                            $pdfDownload_Location.'/'.$newFormattedFilename.'.pdf',
                                            now()->addMinutes(5)
                                        ),
                                        'split',
                                        $batchId,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        null
                                    );
                                } else if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'))) {
                                    Storage::disk('minio')->put(
                                        $pdfDownload_Location.'/'.$randomizePdfFileName.'.zip',
                                        file_get_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'))
                                    );
                                    Storage::disk('local')->delete('public/'.$randomizePdfFileName.'.zip');
                                    $fileProcSize = Storage::disk('minio')->size($pdfDownload_Location.'/'.$randomizePdfFileName.'.zip');
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
                                        Storage::disk('minio')->temporaryUrl(
                                            $pdfDownload_Location.'/'.$randomizePdfFileName.'.zip',
                                            now()->addMinutes(5)
                                        ),
                                        'split',
                                        $batchId,
                                        $newFileProcSize,
                                        null,
                                        null,
                                        null
                                    );
                                } else if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFormattedFilename.'-'.$newPageRanges.'.pdf'))) {
                                    Storage::disk('minio')->put(
                                        $pdfDownload_Location.'/'.$newFormattedFilename.'-'.$newPageRanges.'.pdf',
                                        file_get_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFormattedFilename.'-'.$newPageRanges.'.pdf'))
                                    );
                                    Storage::disk('local')->delete('public/'.$newFormattedFilename.'-'.$newPageRanges.'.pdf');
                                    $fileProcSize = Storage::disk('minio')->size($pdfDownload_Location.'/'.$newFormattedFilename.'-'.$newPageRanges.'.pdf');
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
                                        $newFormattedFilename.'-'.$newPageRanges.'.pdf',
                                        Storage::disk('minio')->temporaryUrl(
                                            $pdfDownload_Location.'/'.$newFormattedFilename.'-'.$newPageRanges.'.pdf',
                                            now()->addMinutes(5)
                                        ),
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
                                            'errReason' => null,
                                            'errStatus' => 'Failed to download file from iLovePDF API !'
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
                                if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFormattedFilename.'.pdf'))) {
                                    Storage::disk('minio')->put(
                                        $pdfDownload_Location.'/'.$newFormattedFilename.'.pdf',
                                        file_get_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFormattedFilename.'.pdf'))
                                    );
                                    Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFormattedFilename.'.pdf');
                                    $fileProcSize = Storage::disk('minio')->size($pdfDownload_Location.'/'.$newFormattedFilename.'.pdf');
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
                                        $newFormattedFilename.'.pdf',
                                        Storage::disk('minio')->temporaryUrl(
                                            $pdfDownload_Location.'/'.$newFormattedFilename.'.pdf',
                                            now()->addMinutes(5)
                                        ),
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
                                            'errReason' => null,
                                            'errStatus' => 'Failed to download file from iLovePDF API !'
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
                                    'errReason' => null,
                                    'errStatus' => 'Invalid split request method !'
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
                            'errReason' => 'File not found on our end, please try again',
                            'errStatus' => 'File not found on the server'
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
                    'errReason' => null,
                    'errStatus' => 'PDF failed to upload !'
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
