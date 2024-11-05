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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

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
                    array_push($poolFiles, $newFileNameWithoutExtension);
                    $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                    if ($batchValue) {
                        $newFileName = $randomizePdfFileName.'.zip';
                    } else {
                        $newFileName = $currentFileName;
                    }
                    if (file_exists($newFilePath)) {
                        $fileSize = filesize($newFilePath);
                        $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        $procUuid = AppHelper::Instance()->generateUniqueUuid(compressModel::class, 'processId');
                        if ($loopCount <= 1) {
                            if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$trimPhase1)) {
                                Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$trimPhase1);
                            }
                        }
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
                    }
                    if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'))) {
                        $compFileSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'));
                        $newCompFileSize = AppHelper::instance()->convert($compFileSize, "MB");
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        $procFile += 1;
                    } else {
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
                            $newFileSize,
                            $batchId,
                            'FAIL',
                            'compress',
                            'Failed to download file from iLovePDF API !',
                            'Error while processing file: '.$newFilePath
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
                if ($loopCount == $procFile) {
                    if ($loopCount == 1) {
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
                            $newFileNameWithoutExtension.'.pdf',
                            Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'),
                            'compress',
                            $batchId,
                            $newFileSize,
                            $newCompFileSize,
                            $compMethod,
                            null
                        );
                    } else {
                        $folderPath = Storage::disk('local')->path('public/'.$pdfDownload_Location);
                        $zipFilePath = Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip');
                        $zip = new ZipArchive();
                        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
                            foreach ($poolFiles as $file) {
                                $filePath = $folderPath.DIRECTORY_SEPARATOR.$file.'.pdf';
                                if (file_exists($filePath)) {
                                    $relativePath = $file.'.pdf';
                                    $zip->addFile($filePath, $relativePath);
                                } else {
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => 'Failed Compress PDF file !',
                                            'errStatus' => 'File '. $filePath . ' was not found'
                                        ]);
                                    compressModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'result' => false,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                    return $this->returnDataMesage(
                                        400,
                                        'PDF Compression failed !',
                                        null,
                                        $batchId,
                                        null,
                                        'File '. $filePath . ' was not found'
                                    );
                                }
                            }
                            $zip->close();
                            $tempPDFfiles = glob(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/*'));
                            $zipFileSize = filesize($zipFilePath);
                            $newZipFileSize = AppHelper::instance()->convert($zipFileSize, "MB");
                            foreach($tempPDFfiles as $file){
                                if(is_file($file)) {
                                    unlink($file);
                                }
                            };
                            compressModel::where('groupId', '=', $batchId)
                                ->update([
                                    'compFileSize' => $newZipFileSize,
                                    'result' => true,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                            return $this->returnCoreMessage(
                                200,
                                'OK',
                                $randomizePdfFileName.'.zip',
                                Storage::disk('local')->url($pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip'),
                                'compress',
                                $batchId,
                                null,
                                $newZipFileSize,
                                $compMethod,
                                null
                            );
                        } else {
                            appLogModel::where('groupId', '=', $batchId)
                                ->update([
                                    'errReason' => 'Failed archiving PDF files !',
                                    'errStatus' => null
                                ]);
                            compressModel::where('groupId', '=', $batchId)
                                ->update([
                                    'result' => false,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                            NotificationHelper::Instance()->sendErrNotify(
                                $randomizePdfFileName.'.zip',
                                null,
                                $batchId,
                                'FAIL',
                                'compress',
                                'Failed archiving PDF files !',
                                null
                            );
                            return $this->returnDataMesage(
                                400,
                                'PDF Compression failed !',
                                null,
                                $batchId,
                                null,
                                'Failed archiving PDF files !'
                            );
                        }
                    }
                } else {
                    appLogModel::where('groupId', '=', $batchId)
                        ->update([
                            'errReason' => 'PDF Compress failed',
                            'errStatus' => 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount
                        ]);
                    compressModel::where('groupId', '=', $batchId)
                        ->update([
                            'result' => false,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                    return $this->returnDataMesage(
                        400,
                        'PDF Compression failed !',
                        null,
                        $batchId,
                        null,
                        'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount
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
