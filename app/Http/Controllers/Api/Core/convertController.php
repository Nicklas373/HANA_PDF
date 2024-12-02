<?php

namespace App\Http\Controllers\Api\Core;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\appLogModel;
use App\Models\cnvModel;
use Aspose\Words\WordsApi;
use Aspose\Words\Model\Requests\{SaveAsRequest, UploadFileRequest};
use Aspose\Words\Model\{DocxSaveOptionsData};
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\ImagepdfTask;
use Ilovepdf\OfficepdfTask;
use Ilovepdf\PdfjpgTask;
use Spatie\PdfToImage\Pdf;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class convertController extends Controller
{
	public function convert(Request $request) {
		$validator = Validator::make($request->all(),[
            'batch' => ['required', 'in:true,false'],
            'convertType' => ['required', 'in:jpg,docx,pptx,xlsx,pdf'],
            'file' => 'required',
            'extImage' => ['required', 'in:true,false']
		]);

        // Generate Uni UUID
        $uuid = AppHelper::Instance()->generateUniqueUuid(cnvModel::class, 'processId');
        $batchId = AppHelper::Instance()->generateUniqueUuid(cnvModel::class, 'groupId');

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
                'convert',
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
                $images = $request->post('extImage');
                $convertType = $request->post('convertType');
                $batch = $request->post('batch');
                $pdfEncKey = bin2hex(random_bytes(16));
                $pdfUpload_Location = env('PDF_UPLOAD');
                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                $pdfPool_Location = env('PDF_POOL');
                $pdfExtImage_Location = env('PDF_IMG_POOL');
                $pdfImageTrueName = null;
                $loopCount = count($files);
                $poolFiles = array();
                $altPoolFiles = array();
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
                if ($images == "true") {
                    $imageModes = 'extract';
                    $extMode = true;
                } else {
                    $imageModes = 'pages';
                    $extMode = false;
                }
                $str = rand(1000,10000000);
                $randomizePdfFileName = 'pdfConvert_'.substr(md5(uniqid($str)), 0, 8);
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
                        cnvModel::create([
                            'fileName' => $currentFileName,
                            'fileSize' => $newFileSize,
                            'container' => $convertType,
                            'imgExtract' => false,
                            'result' => false,
                            'isBatch' => $batchValue,
                            'batchName' => $newFileName,
                            'groupId' => $batchId,
                            'processId' => $procUuid,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        NotificationHelper::Instance()->sendErrNotify(
                            $currentFileName,
                            null,
                            $batchId,
                            'FAIL',
                            'convert',
                            $currentFileName.' could not be found in the object storage',
                            $e->getMessage()
                        );
                        return $this->returnDataMesage(
                            400,
                            'PDF Convert failed !',
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
                        if ($batchValue) {
                            $newFileName = $randomizePdfFileName.'.zip';
                        } else {
                            $newFileName = $currentFileName;
                        }
                        $fileSize = Storage::disk('minio')->size($pdfUpload_Location.'/'.$trimPhase1);
                        $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        $procUuid = AppHelper::Instance()->generateUniqueUuid(cnvModel::class, 'processId');
                        $pdfNameWithExtension = pathinfo($currentFileName, PATHINFO_EXTENSION);
                        appLogModel::create([
                            'processId' => $procUuid,
                            'groupId' => $batchId,
                            'errReason' => null,
                            'errStatus' => null
                        ]);
                        cnvModel::create([
                            'fileName' => $currentFileName,
                            'fileSize' => $newFileSize,
                            'container' => $convertType,
                            'imgExtract' => false,
                            'result' => false,
                            'isBatch' => $batchValue,
                            'batchName' => $newFileName,
                            'groupId' => $batchId,
                            'processId' => $procUuid,
                            'procStartAt' => $startProc,
                            'procEndAt' => null,
                            'procDuration' => null
                        ]);
                        if ($convertType == 'xlsx' || $convertType == 'pptx') {
                            if ($loopCount <= 1) {
                                if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.'.$convertType)) {
                                    Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.'.$convertType);
                                }
                            }
                            $minioUpload = Storage::disk('minio')->get($pdfUpload_Location.'/'.$currentFileName);
                            file_put_contents(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName), $minioUpload);
                            $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName);
                            $asposeToken = AppHelper::instance()->getAsposeToken(env('ASPOSE_CLOUD_CLIENT_ID'), env('ASPOSE_CLOUD_TOKEN'));
                            if ($asposeToken) {
                                $fileContent = file_get_contents($newFilePath);
                                try {
                                    $asposeAPI = Http::timeout(300)
                                        ->withToken($asposeToken)
                                        ->attach('file', $fileContent, basename($newFilePath))
                                        ->put("https://api.aspose.cloud/v3.0/pdf/convert/{$convertType}?outPath={$newFileNameWithoutExtension}.{$convertType}");
                                    if ($asposeAPI->successful()) {
                                        if (Storage::disk('ftp')->exists($newFileNameWithoutExtension.".xlsx")) {
                                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                            $duration = $end->diff($startProc);
                                            array_push($poolFiles, $newFileNameWithoutExtension);
                                            $procFile += 1;
                                            $minioDownload = Storage::disk('ftp')->get($newFileNameWithoutExtension.'.'.$convertType);
                                            file_put_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.'.$convertType), $minioDownload);
                                            $newFilePath = Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.'.$convertType);
                                            Storage::disk('minio')->put(
                                                $pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.'.$convertType,
                                                file_get_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.'.$convertType))
                                            );
                                            Storage::disk('ftp')->delete($newFileNameWithoutExtension.'.'.$convertType);
                                            Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                                        } else {
                                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                            $duration = $end->diff($startProc);
                                            appLogModel::where('groupId', '=', $batchId)
                                                ->update([
                                                    'errReason' => null,
                                                    'errStatus' => 'FTP Server Connection Failed !'
                                                ]);
                                            cnvModel::where('groupId', '=', $batchId)
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
                                                'cnvTo'.$convertType,
                                                'FTP Server Connection Failed !',
                                                'Aspose API v3.0 - '.$convertType
                                            );
                                            Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                                            return $this->returnDataMesage(
                                                400,
                                                'PDF Convert failed !',
                                                null,
                                                $batchId,
                                                null,
                                                'FTP Server Connection Failed !'
                                            );
                                        }
                                    } else {
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        appLogModel::where('groupId', '=', $batchId)
                                            ->update([
                                                'errReason' => $asposeAPI->body(),
                                                'errStatus' => 'Aspose API v3.0 - '.$convertType.' failure'
                                            ]);
                                        cnvModel::where('groupId', '=', $batchId)
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
                                            'cnvTo'.$convertType,
                                            'Aspose API v3.0 - '.$convertType.' failure',
                                            $asposeAPI->body()
                                        );
                                        Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                                        return $this->returnDataMesage(
                                            400,
                                            'PDF Convert failed !',
                                            $asposeAPI->body(),
                                            $batchId,
                                            null,
                                            'Aspose API v3.0 - '.$convertType.' failure'
                                        );
                                    }
                                } catch (\Exception $e) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => $e->getMessage(),
                                            'errStatus' => 'Guzzle HTTP failure'
                                        ]);
                                    cnvModel::where('groupId', '=', $batchId)
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
                                        'cnvTo'.$convertType,
                                        'Guzzle HTTP failure',
                                        $e->getMessage()
                                    );
                                    Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                                    return $this->returnDataMesage(
                                        400,
                                        'PDF Convert failed !',
                                        $e->getMessage(),
                                        $batchId,
                                        null,
                                        'Guzzle HTTP failure'
                                    );
                                }
                            } else {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => $message->getMessage(),
                                        'errStatus' => 'Failed to generated Aspose Token !'
                                    ]);
                                cnvModel::where('groupId', '=', $batchId)
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
                                    'cnvTo'.$convertType,
                                    'Failed to generated Aspose Token !',
                                    'Aspose API v3.0 - '.$convertType.' failure'
                                );
                                Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    null,
                                    $batchId,
                                    null,
                                    'Failed to generated Aspose Token !'
                                );
                            }
                        } else if ($convertType == 'docx') {
                            if ($loopCount <= 1) {
                                if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.docx')) {
                                    Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.docx');
                                }
                            }
                            $minioUpload = Storage::disk('minio')->get($pdfUpload_Location.'/'.$currentFileName);
                            file_put_contents(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName), $minioUpload);
                            $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName);
                            try {
                                $wordsApi = new WordsApi(env('ASPOSE_CLOUD_CLIENT_ID'), env('ASPOSE_CLOUD_TOKEN'));
                                $uploadFileRequest = new UploadFileRequest($newFilePath, $currentFileName);
                                $wordsApi->uploadFile($uploadFileRequest);
                                $requestSaveOptionsData = new DocxSaveOptionsData(array(
                                    "save_format" => "docx",
                                    "file_name" => $newFileNameWithoutExtension.".docx",
                                ));
                                $request = new SaveAsRequest(
                                    $currentFileName,
                                    $requestSaveOptionsData,
                                    NULL,
                                    NULL,
                                    NULL,
                                    NULL
                                );
                                $result = $wordsApi->saveAs($request);
                            } catch (\Exception $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => $e->getMessage(),
                                        'errStatus' => 'Aspose API Error !, CnvToDocx failure'
                                    ]);
                                cnvModel::where('groupId', '=', $batchId)
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
                                    'CnvToDOCX',
                                    'Aspose API Error !, CnvToDOCX failure',
                                    $e->getMessage()
                                );
                                Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    $e->getMessage(),
                                    $batchId,
                                    null,
                                    'Aspose API Error !, CnvToDOCX failure'
                                );
                            }
                            if (json_decode($result, true) !== NULL) {
                                if (Storage::disk('ftp')->exists($newFileNameWithoutExtension.".docx")) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    $procFile += 1;
                                    array_push($poolFiles, $newFileNameWithoutExtension);
                                    $minioDownload = Storage::disk('ftp')->get($newFileNameWithoutExtension.'.docx');
                                    file_put_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.docx'), $minioDownload);
                                    $newFilePath = Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.docx');
                                    Storage::disk('minio')->put(
                                        $pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.docx',
                                        file_get_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.docx'))
                                    );
                                    Storage::disk('ftp')->delete($newFileNameWithoutExtension.'.docx');
                                    Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                                } else {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => null,
                                            'errStatus' => 'FTP Server Connection Failed !'
                                        ]);
                                    cnvModel::where('groupId', '=', $batchId)
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
                                        'cnvToDocx',
                                        'FTP Server Connection Failed !',
                                        null
                                    );
                                    Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                                    return $this->returnDataMesage(
                                        400,
                                        'PDF Convert failed !',
                                        $e->getMessage(),
                                        $batchId,
                                        null,
                                        'FTP Server Connection Failed',
                                    );
                                }
                            } else {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => null,
                                        'errStatus' => 'Aspose API has fail while process, Please look on Aspose Dashboard !'
                                    ]);
                                cnvModel::where('groupId', '=', $batchId)
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
                                    'cnvToDocx',
                                    'Aspose Clouds API Error !',
                                    null
                                );
                                Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    $e->getMessage(),
                                    $batchId,
                                    null,
                                    'Aspose Clouds API Error !'
                                );
                            }
                        } else if ($convertType == 'jpg') {
                            if ($loopCount <= 1) {
                                if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg')) {
                                    Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg');
                                } else if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-0001.jpg')) {
                                    Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-0001.jpg');
                                }
                            }
                            $minioUpload = Storage::disk('minio')->get($pdfUpload_Location.'/'.$currentFileName);
                            file_put_contents(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName), $minioUpload);
                            $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName);
                            try {
                                $pdf = new Pdf($newFilePath);
                                $pdfTotalPages = $pdf->pageCount();
                                Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                            } catch (\Exception $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => $e->getMessage(),
                                        'errStatus' => 'Failed to count total PDF pages'
                                    ]);
                                cnvModel::where('groupId', '=', $batchId)
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
                                    'cnvToImg',
                                    'Failed to count total PDF pages',
                                    $e->getMessage()
                                );
                                Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    $e->getMessage(),
                                    $batchId,
                                    null,
                                    'Failed to count total PDF pages'
                                );
                            }
                            try {
                                $ilovepdfTask = new PdfjpgTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                $ilovepdfTask->setFileEncryption($pdfEncKey);
                                $ilovepdfTask->setEncryptKey($pdfEncKey);
                                $ilovepdfTask->setEncryption(true);
                                $pdfTempUrl =  Storage::disk('minio')->temporaryUrl(
                                    $pdfUpload_Location.'/'.$trimPhase1,
                                    now()->addSeconds(30)
                                );
                                $pdfFile = $ilovepdfTask->addFileFromUrl($pdfTempUrl);
                                $ilovepdfTask->setMode($imageModes);
                                $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
                                $ilovepdfTask->setPackagedFilename($newFileNameWithoutExtension);
                                $ilovepdfTask->execute();
                                $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                            } catch (\Exception $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => $e->getMessage(),
                                        'errStatus' => 'iLovePDF API Error !, Catch on Exception'
                                    ]);
                                cnvModel::where('groupId', '=', $batchId)
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
                                    'cnvToImg',
                                    'iLovePDF API Error !, Catch on Exception',
                                    $e->getMessage()
                                );
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    $e->getMessage(),
                                    $batchId,
                                    null,
                                    'iLovePDF API Error !, Catch on Exception'
                                );
                            }
                            if ($pdfTotalPages == 1 && $extMode) {
                                foreach (glob(Storage::disk('local')->path('public/'.$pdfExtImage_Location).'/*.jpg') as $filename) {
                                    rename($filename, Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg'));
                                }
                            }
                            if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.zip'))) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $procFile += 1;
                                array_push($poolFiles, $newFileNameWithoutExtension);
                                $pdfImageTrueName = $newFileNameWithoutExtension.'.zip';
                                Storage::disk('minio')->put(
                                    $pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.zip',
                                    file_get_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.zip'))
                                );
                            } else {
                                if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg'))) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    $procFile += 1;
                                    array_push($poolFiles, $newFileNameWithoutExtension);
                                    $pdfImageTrueName = $newFileNameWithoutExtension.'.jpg';
                                    Storage::disk('minio')->put(
                                        $pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg',
                                        file_get_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg'))
                                    );
                                } else if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-0001.jpg'))) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    $procFile += 1;
                                    array_push($poolFiles, $newFileNameWithoutExtension);
                                    $pdfImageTrueName = $newFileNameWithoutExtension.'-0001.jpg';
                                    Storage::disk('minio')->put(
                                        $pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-0001.jpg',
                                        file_get_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-0001.jpg'))
                                    );
                                }
                            }
                        } else if ($convertType == 'pdf') {
                            if ($loopCount <= 1) {
                                if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf')) {
                                    Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf');
                                }
                            }
                            Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                            try {
                                if ($pdfNameWithExtension == "jpg" || $pdfNameWithExtension == "jpeg" || $pdfNameWithExtension == "png" || $pdfNameWithExtension == "tiff") {
                                    $ilovepdfTask = new ImagepdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setFileEncryption($pdfEncKey);
                                    $ilovepdfTask->setEncryptKey($pdfEncKey);
                                    $ilovepdfTask->setEncryption(true);
                                    $pdfTempUrl =  Storage::disk('minio')->temporaryUrl(
                                        $pdfUpload_Location.'/'.$trimPhase1,
                                        now()->addSeconds(30)
                                    );
                                    $pdfFile = $ilovepdfTask->addFileFromUrl($pdfTempUrl);
                                    $ilovepdfTask->setPageSize('fit');
                                    $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
                                    $ilovepdfTask->setPackagedFilename($newFileNameWithoutExtension);
                                    $ilovepdfTask->execute();
                                    $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                                } else {
                                    $ilovepdfTask = new OfficepdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                                    $pdfTempUrl =  Storage::disk('minio')->temporaryUrl(
                                        $pdfUpload_Location.'/'.$trimPhase1,
                                        now()->addSeconds(30)
                                    );
                                    $pdfFile = $ilovepdfTask->addFileFromUrl($pdfTempUrl);
                                    $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
                                    $ilovepdfTask->execute();
                                    $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                                }
                            } catch (\Exception $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => $e->getMessage(),
                                        'errStatus' => 'iLovePDF API Error !, Catch on Exception'
                                    ]);
                                cnvModel::where('groupId', '=', $batchId)
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
                                    'imgToPDF',
                                    'iLovePDF API Error !, Catch on Exception',
                                    $e->getMessage()
                                );
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    $e->getMessage(),
                                    $batchId,
                                    null,
                                    'iLovePDF API Error !, Catch on Exception'
                                );
                            }
                            if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'))) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $procFile += 1;
                                array_push($poolFiles, $newFileNameWithoutExtension);
                                Storage::disk('minio')->put(
                                    $pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf',
                                    file_get_contents(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'))
                                );
                            } else {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => null,
                                        'errStatus' => 'Failed to download file from iLovePDF API !'
                                    ]);
                                cnvModel::where('groupId', '=', $batchId)
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
                                    'pdfToImg',
                                    'Failed to download file from iLovePDF API !',
                                    null
                                );
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
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
                            'errReason' => 'File not found on our end, please try again',
                            'errStatus' => 'File not found on the server'
                        ]);
                    convertModel::where('groupId', '=', $batchId)
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
                        'convert',
                        'File not found on the server',
                        'File not found on our end, please try again'
                    );
                    return $this->returnDataMesage(
                        400,
                        'PDF Convert failed !',
                        null,
                        $batchId,
                        null,
                        'File not found on our end, please try again'
                    );
                }
                if ($loopCount == $procFile) {
                    if ($loopCount == 1) {
                        appLogModel::where('groupId', '=', $batchId)
                            ->update([
                                'errReason' => null,
                                'errStatus' => null
                            ]);
                        cnvModel::where('groupId', '=', $batchId)
                            ->update([
                                'result' => true,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' => $duration->s.' seconds'
                            ]);
                        if ($convertType == 'jpg') {
                            Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$pdfImageTrueName);
                            return $this->returnCoreMessage(
                                200,
                                'OK',
                                $pdfImageTrueName,
                                Storage::disk('minio')->temporaryUrl(
                                    $pdfDownload_Location.'/'.$pdfImageTrueName,
                                    now()->addMinutes(5)
                                ),
                                'convert',
                                $uuid,
                                $newFileSize,
                                null,
                                null,
                                null
                            );
                        } else {
                            Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.'.$convertType);
                            return $this->returnCoreMessage(
                                200,
                                'OK',
                                $newFileNameWithoutExtension.'.'.$convertType,
                                Storage::disk('minio')->temporaryUrl(
                                    $pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.'.$convertType,
                                    now()->addMinutes(5)
                                ),
                                'convert',
                                $uuid,
                                $newFileSize,
                                null,
                                null,
                                null
                            );
                        }
                    } else {
                        $folderPath = Storage::disk('local')->path('public/'.$pdfDownload_Location);
                        $zipFilePath = Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip');
                        $zip = new ZipArchive();
                        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
                            if ($convertType == 'jpg') {
                                foreach ($poolFiles as $file) {
                                    $filePath = $folderPath.DIRECTORY_SEPARATOR.$file.'.zip';
                                    $fileAltPath = $folderPath.DIRECTORY_SEPARATOR.$file.'-0001.jpg';
                                    if (file_exists($filePath)) {
                                        $relativePath = $file.'.zip';
                                        $zip->addFile($filePath, $relativePath);
                                    } else if (file_exists($fileAltPath)) {
                                        $relativePath = $file.'.zip';
                                        $zip->addFile($fileAltPath, $relativePath);
                                    } else {
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        appLogModel::where('groupId', '=', $batchId)
                                            ->update([
                                                'errReason' => 'File '. $filePath . ' was not found',
                                                'errStatus' => 'Failed convert PDF file !'
                                            ]);
                                        cnvModel::where('groupId', '=', $batchId)
                                            ->update([
                                                'result' => false,
                                                'procDuration' => $duration->s.' seconds'
                                            ]);
                                        NotificationHelper::Instance()->sendErrNotify(
                                            $currentFileName,
                                            $newFileSize,
                                            $batchId,
                                            'FAIL',
                                            'convert',
                                            'Failed convert PDF file !',
                                            'File '. $filePath . ' was not found'
                                        );
                                        return $this->returnDataMesage(
                                            400,
                                            'PDF Convert failed !',
                                            null,
                                            $batchId,
                                            null,
                                            'Failed convert PDF file !', 'File '. $filePath . ' was not found'
                                        );
                                    }
                                }
                                $zip->close();
                            } else {
                                foreach ($poolFiles as $file) {
                                    $filePath = $folderPath.DIRECTORY_SEPARATOR.$file.'.'.$convertType;
                                    if (file_exists($filePath)) {
                                        $relativePath = $file.'.'.$convertType;
                                        $zip->addFile($filePath, $relativePath);
                                    }
                                }
                                $zip->close();
                            }
                            foreach ($poolFiles as $file) {
                                Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$file.'.zip');
                                Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$file.'-0001.jpg');
                                Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$file.'.'.$convertType);
                            }
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            appLogModel::where('groupId', '=', $batchId)
                                ->update([
                                    'errReason' => null,
                                    'errStatus' => null
                                ]);
                            cnvModel::where('groupId', '=', $batchId)
                                ->update([
                                    'result' => true,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' => $duration->s.' seconds'
                                ]);
                            Storage::disk('minio')->put(
                                $pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip',
                                file_get_contents(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip'))
                            );
                            Storage::disk('local')->delete('public/'.$pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip');
                            return $this->returnCoreMessage(
                                200,
                                'OK',
                                $randomizePdfFileName.'.zip',
                                Storage::disk('minio')->temporaryUrl(
                                    $pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip',
                                    now()->addMinutes(5)
                                ),
                                'convert',
                                $uuid,
                                null,
                                $newFileSize,
                                $convertType,
                                null
                            );
                        } else {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            appLogModel::where('groupId', '=', $batchId)
                                ->update([
                                    'errReason' => null,
                                    'errStatus' => 'Failed archiving PDF files !'
                                ]);
                            cnvModel::where('groupId', '=', $batchId)
                                ->update([
                                    'result' => false,
                                    'procDuration' => $duration->s.' seconds'
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
                            foreach ($poolFiles as $file) {
                                $currentFileName = basename($file);
                                Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$file.'.'.$convertType);
                            }
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
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    appLogModel::where('groupId', '=', $batchId)
                        ->update([
                            'errReason' => 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount,
                            'errStatus' => 'PDF convert failed'
                        ]);
                    cnvModel::where('groupId', '=', $batchId)
                        ->update([
                            'result' => false,
                            'procDuration' => $duration->s.' seconds'
                        ]);
                    foreach ($poolFiles as $file) {
                        $currentFileName = basename($file);
                        Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$file.'.'.$convertType);
                    }
                    return $this->returnDataMesage(
                        400,
                        'PDF convert failed !',
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
                    'processId' => $procUuid,
                    'groupId' => $batchId,
                    'errReason' =>  'PDF failed to upload',
                    'errStatus' => 'PDF Convert failed !'
                ]);
                cnvModel::create([
                    'fileName' => null,
                    'fileSize' => null,
                    'container' => null,
                    'imgExtract' => false,
                    'result' => false,
                    'isBatch' => null,
                    'batchName' => null,
                    'groupId' => $batchId,
                    'processId' => $procUuid,
                    'procDuration' => $duration->s.' seconds'
                ]);
                return $this->returnDataMesage(
                    400,
                    'PDF Convert failed !',
                    null,
                    $batchId,
                    null,
                    'PDF failed to upload'
                );
            }
        }
	}
}
