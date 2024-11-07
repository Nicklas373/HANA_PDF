<?php

namespace App\Http\Controllers\Api\Core;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\appLogModel;
use App\Models\compressModel;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;

class compressController extends Controller
{
 	public function compress(Request $request) {
		$validator = Validator::make($request->all(),[
            'batch' => ['required', 'in:true,false'],
            'compMethod' => ['required', 'in:low,recommended,extreme'],
            'file' => 'required',
		]);

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

        // Generate Uni UUID
        $uuid = AppHelper::Instance()->generateUniqueUuid(compressModel::class, 'processId');
        $batchId = AppHelper::Instance()->generateSingleUniqueUuid(compressModel::class, 'groupId');

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
                'compress',
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
                $compMethod = $request->post('compMethod');
                $batch = $request->post('batch');
                $pdfEncKey = bin2hex(random_bytes(16));
                $pdfUpload_Location = env('PDF_UPLOAD');
                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                $pdfPool_Location = env('PDF_POOL');
                $loopCount = count($files);
                $altPoolFiles = array();
                $poolFiles = array();
                $procFile = 0;
                if ($batch == "true") {
                    $batchValue = true;
                } else {
                    $batchValue = false;
                }
                if ($loopCount > 1) {
                    $pdfDownload_Location = $pdfPool_Location;
                } else {
                    $pdfDownload_Location = $pdfProcessed_Location;
                }
                $str = rand(1000,10000000);
                $randomizePdfFileName = 'pdfCompress_'.substr(md5(uniqid($str)), 0, 8);
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
                    try {
                        $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                        $ilovepdfTask = $ilovepdf->newTask('compress');
                        $ilovepdfTask->setFileEncryption($pdfEncKey);
                        $ilovepdfTask->setEncryptKey($pdfEncKey);
                        $ilovepdfTask->setEncryption(true);
                        foreach ($files as $file) {
                            $currentFileName = basename($file);
                            $trimPhase1 = str_replace(' ', '_', $currentFileName);
                            $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                            array_push($poolFiles, $newFileNameWithoutExtension);
                            $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                            if ($batchValue) {
                                $newFileName = $randomizePdfFileName.'.zip';
                            } else {
                                $newFileName = $currentFileName;
                            }
                            $fileSize = filesize($newFilePath);
                            $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                            $procUuid = AppHelper::Instance()->generateUniqueUuid(compressModel::class, 'processId');
                            if ($loopCount <= 1) {
                                if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$trimPhase1)) {
                                    Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$trimPhase1);
                                }
                            }
                            $pdfFile = $ilovepdfTask->addFile($newFilePath);
                            $pdfFile->setPassword($pdfEncKey);
                            appLogModel::create([
                                'processId' => $procUuid,
                                'groupId' => $batchId,
                                'errReason' => null,
                                'errStatus' => null
                            ]);
                            compressModel::create([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'batchName' => $newFileName,
                                'groupId' => $batchId,
                                'processId' => $procUuid,
                                'procStartAt' => $startProc,
                                'procDuration' => null
                            ]);
                        }
                        $ilovepdfTask->setCompressionLevel($compMethod);
                        if ($batchValue) {
                            $ilovepdfTask->setPackagedFilename($randomizePdfFileName);
                        } else {
                            $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
                        }
                        $ilovepdfTask->execute();
                        $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                        $ilovepdfTask->delete();
                    } catch (\Exception $e) {
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        appLogModel::where('groupId', '=', $batchId)
                            ->update([
                                'errReason' => 'Failed to download file from iLovePDF API !',
                                'errStatus' => 'Error while processing file: '.$newFilePath
                            ]);
                        compressModel::where('groupId', '=', $batchId)
                            ->update([
                                'result' => false,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                        NotificationHelper::Instance()->sendErrNotify(
                            $currentFileName,
                            $fileSize,
                            $batchId,
                            'FAIL',
                            'compress',
                            'iLovePDF API Error !, Catch on Exception',
                            $e->getMessage()
                        );
                        return $this->returnDataMesage(
                            400,
                            'PDF Compression failed !',
                            $e->getMessage(),
                            $batchId,
                            null,
                            'iLovePDF API Error !, Catch on Exception'
                        );
                    }
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    if ($batchValue) {
                        if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'))) {
                            $compFileSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'));
                            $newCompFileSize = AppHelper::instance()->convert($compFileSize, "MB");
                            compressModel::where('groupId', '=', $batchId)
                                ->update([
                                    'compFileSize' => $newCompFileSize,
                                    'result' => true,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' => $duration->s.' seconds'
                                ]);
                            return $this->returnCoreMessage(
                                200,
                                'OK',
                                $randomizePdfFileName.'.zip',
                                Storage::disk('local')->url($pdfDownload_Location.'/'.$randomizePdfFileName.'.zip'),
                                'compress',
                                $batchId,
                                $newFileSize,
                                $newCompFileSize,
                                $compMethod,
                                null
                            );
                        } else {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            appLogModel::where('groupId', '=', $batchId)
                                ->update([
                                    'errReason' => 'Failed to download file from iLovePDF API !',
                                    'errStatus' => 'Error while processing file: '.$randomizePdfFileName.'.zip'
                                ]);
                            compressModel::where('groupId', '=', $batchId)
                                ->update([
                                    'result' => false,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                            NotificationHelper::Instance()->sendErrNotify(
                                $file,
                                $newFileSize,
                                $batchId,
                                'FAIL',
                                'compress',
                                'Failed to download file from iLovePDF API !',
                                'Error while processing file: '.$randomizePdfFileName.'.zip'
                            );
                            return $this->returnDataMesage(
                                400,
                                'PDF Compression failed !',
                                null,
                                $batchId,
                                null,
                                'Failed to download file from iLovePDF API !'
                            );
                        }
                    } else {
                        foreach ($poolFiles as $file) {
                            if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$file.'.pdf'))) {
                                $compFileSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$file.'.pdf'));
                                $newCompFileSize = AppHelper::instance()->convert($compFileSize, "MB");
                                compressModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'compFileSize' => $newCompFileSize,
                                        'result' => true,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' => $duration->s.' seconds'
                                    ]);
                                return $this->returnCoreMessage(
                                    200,
                                    'OK',
                                    $randomizePdfFileName.'.zip',
                                    Storage::disk('local')->url($pdfDownload_Location.'/'.$file.'.pdf'),
                                    'compress',
                                    $batchId,
                                    $newFileSize,
                                    $newCompFileSize,
                                    $compMethod,
                                    null
                                );
                            } else {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => 'Failed to download file from iLovePDF API !',
                                        'errStatus' => 'Error while processing file: '.$file
                                    ]);
                                compressModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'result' => false,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                NotificationHelper::Instance()->sendErrNotify(
                                    $file,
                                    $newFileSize,
                                    $batchId,
                                    'FAIL',
                                    'compress',
                                    'Failed to download file from iLovePDF API !',
                                    'Error while processing file: '.$file
                                );
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Compression failed !',
                                    null,
                                    $batchId,
                                    null,
                                    'Failed to download file from iLovePDF API !'
                                );
                            }
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
                    compressModel::where('groupId', '=', $batchId)
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
                        'compress',
                        'File not found on the server',
                        'File not found on our end, please try again'
                    );
                    return $this->returnDataMesage(
                        400,
                        'PDF Compression failed !',
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
                    'errReason' => 'PDF failed to upload !',
                    'errStatus' => null
                ]);
                compressModel::create([
                    'fileName' => null,
                    'fileSize' => null,
                    'compFileSize' => null,
                    'compMethod' => null,
                    'result' => false,
                    'isBatch' => false,
                    'batchName' => null,
                    'groupId' => $batchId,
                    'processId' => $uuid,
                    'procStartAt' => $startProc,
                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                    'procDuration' => $duration->s.' seconds'
                ]);
                return $this->returnDataMesage(
                    400,
                    'PDF Compression failed !',
                    null,
                    $batchId,
                    null,
                    'PDF failed to upload'
                );
            }
		}
	}
}
