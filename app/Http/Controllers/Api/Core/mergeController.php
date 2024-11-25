<?php

namespace App\Http\Controllers\Api\Core;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Models\appLogModel;
use App\Models\mergeModel;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;

class mergeController extends Controller
{
    public function merge(Request $request) {
        $validator = Validator::make($request->all(),[
            'batch' => ['required', 'in:true,false'],
            'file' => 'required',
		]);

        // Generate Uni UUID
        $uuid = AppHelper::Instance()->generateUniqueUuid(mergeModel::class, 'processId');
        $batchId = AppHelper::Instance()->generateUniqueUuid(mergeModel::class, 'groupId');

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
                $batchId,
                'FAIL',
                'merge',
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
                $batch = $request->post('batch');
                $pdfEncKey = bin2hex(random_bytes(16));
                $pdfUpload_Location = env('PDF_UPLOAD');
                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                $pdfPool_Location = env('PDF_POOL');
                $loopCount = count($files);
                $altPoolFiles = array();
                if ($batch == "true") {
                    $batchValue = true;
                } else {
                    $batchValue = false;
                }
                $pdfDownload_Location = $pdfPool_Location;
                $str = rand(1000,10000000);
                $randomizePdfFileName = 'pdfMerged_'.substr(md5(uniqid($str)), 0, 8);
                foreach ($files as $file) {
                    $currentFileName = basename($file);
                    $trimPhase1 = str_replace(' ', '_', $currentFileName);
                    $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                    if (Storage::disk('minio')->exists($pdfUpload_Location.'/'.$trimPhase1)) {
                        array_push($altPoolFiles, $newFileNameWithoutExtension);
                    }
                }
                if ($loopCount == count($altPoolFiles)) {
                    try {
                        $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                        $ilovepdfTask = $ilovepdf->newTask('merge');
                        $ilovepdfTask->setFileEncryption($pdfEncKey);
                        $ilovepdfTask->setEncryptKey($pdfEncKey);
                        $ilovepdfTask->setEncryption(true);
                        foreach ($files as $file) {
                            $procUuid = AppHelper::Instance()->generateUniqueUuid(mergeModel::class, 'processId');
                            $currentFileName = basename($file);
                            $trimPhase1 = str_replace(' ', '_', $currentFileName);
                            $firstTrim = basename($currentFileName, '.pdf');
                            $newFileNameWithoutExtension = str_replace('.', '_', $firstTrim);
                            $minioUpload = Storage::disk('minio')->get($pdfUpload_Location.'/'.$currentFileName);
                            file_put_contents(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName), $minioUpload);
                            $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName);
                            $fileSize = Storage::disk('minio')->size($pdfUpload_Location.'/'.$trimPhase1);
                            $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                            if ($batchValue) {
                                $newFileName = $randomizePdfFileName.'.zip';
                            } else {
                                $newFileName = $currentFileName;
                            }
                            $pdfName = $ilovepdfTask->addFile($newFilePath);
                            $pdfName->setPassword($pdfEncKey);
                            appLogModel::create([
                                'processId' => $procUuid,
                                'groupId' => $batchId,
                                'errReason' => null,
                                'errStatus' => null
                            ]);
                            mergeModel::create([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'result' => false,
                                'isBatch' => true,
                                'batchName' => $newFileName,
                                'groupId' => $batchId,
                                'processId' => $procUuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => null,
                                'procDuration' => null
                            ]);
                        }
                        $ilovepdfTask->setOutputFileName($randomizePdfFileName);
                        $ilovepdfTask->execute();
                        $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                        $ilovepdfTask->delete();
                        foreach ($files as $file) {
                            $currentFileName = basename($file);
                            $trimPhase1 = str_replace(' ', '_', $currentFileName);
                            Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                        }
                    } catch (\Exception $e) {
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        appLogModel::where('groupId', '=', $batchId)
                            ->update([
                                'errReason' => $e->getMessage(),
                                'errStatus' => 'PDF Merge failed'
                            ]);
                        mergeModel::where('groupId', '=', $batchId)
                            ->update([
                                'result' => false,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' => $duration->s.' seconds'
                            ]);
                        NotificationHelper::Instance()->sendErrNotify(
                            $currentFileName,
                            $fileSize,
                            $uuid,
                            'FAIL',
                            'merge',
                            'iLovePDF API Error !, Catch on Exception',
                            $e->getMessage()
                        );
                        foreach ($files as $file) {
                            $currentFileName = basename($file);
                            $trimPhase1 = str_replace(' ', '_', $currentFileName);
                            Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                        }
                        return $this->returnDataMesage(
                            400,
                            'PDF Merge failed !',
                            $e->getMessage(),
                            $batchId,
                            null,
                            'iLovePDF API Error !, Catch on Exception'
                        );
                    }
                    if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'))) {
                        Storage::disk('minio')->put(
                            $pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf',
                            file_get_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'))
                        );
                        Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf');
                        $mergedFileSize = Storage::disk('minio')->size($pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf');
                        $newMergedFileSize = AppHelper::instance()->convert($mergedFileSize, "MB");
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        appLogModel::where('groupId', '=', $batchId)
                            ->update([
                                'errReason' => null,
                                'errStatus' => null
                            ]);
                        mergeModel::where('groupId', '=', $batchId)
                            ->update([
                                'result' => true,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' => $duration->s.' seconds'
                            ]);
                        return $this->returnCoreMessage(
                            200,
                            'OK',
                            $randomizePdfFileName.'.pdf',
                            Storage::disk('minio')->temporaryUrl(
                                $pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf',
                                now()->addMinutes(5)
                            ),
                            'merge',
                            $batchId,
                            $newMergedFileSize,
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
                        mergeModel::where('groupId', '=', $batchId)
                            ->update([
                                'result' => false,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' => $duration->s.' seconds'
                            ]);
                        NotificationHelper::Instance()->sendErrNotify(
                            null,
                            null,
                            $uuid,
                            'FAIL',
                            'Failed to download file from iLovePDF API !',
                            null
                        );
                        return $this->returnDataMesage(
                            400,
                            'PDF Merge failed !',
                            null,
                            $batchId,
                            null,
                            'Failed to download file from iLovePDF API !'
                        );
                    }
                } else {
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    appLogModel::where('groupId', '=', $batchId)
                        ->update([
                            'errReason' => 'File not found on our end, please try again',
                            'errStatus' => 'File not found on the server'
                        ]);
                    mergeModel::where('groupId', '=', $batchId)
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
                        'merge',
                        'File not found on the server',
                        'File not found on our end, please try again'
                    );
                    return $this->returnDataMesage(
                        400,
                        'PDF Merge failed !',
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
                mergeModel::create([
                    'fileName' => null,
                    'fileSize' => null,
                    'result' => true,
                    'isBatch' => false,
                    'groupId' => $batchId,
                    'processId' => $uuid,
                    'procStartAt' => $startProc,
                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                    'procDuration' => $duration->s.' seconds'
                ]);
                NotificationHelper::Instance()->sendErrNotify(
                    null,
                    null,
                    $batchId,
                    'FAIL',
                    'merge',
                    'PDF failed to upload !'
                );
                return $this->returnDataMesage(
                    400,
                    'PDF Merge failed !',
                    null,
                    $batchId,
                    null,
                    'PDF failed to upload !'
                );
            }
        }
    }
}
