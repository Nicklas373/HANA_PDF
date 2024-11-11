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
                'errReason' => 'Validation Failed!',
                'errStatus' => $validator->messages()->first()
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
                        $procUuid = AppHelper::Instance()->generateUniqueUuid(cnvModel::class, 'processId');
                        $pdfNameWithExtension = pathinfo($currentFileName, PATHINFO_EXTENSION);
                        if ($pdfNameWithExtension == "pdf") {
                            $pdf = new Pdf($newFilePath);
                            $pdfTotalPages = $pdf->pageCount();
                        }
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
                        if ($convertType == 'xlsx') {
                            if ($loopCount <= 1) {
                                if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.xlsx')) {
                                    Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.xlsx');
                                }
                            }
                            $asposeAPI = new Process([
                                'python3',
                                public_path().'/ext-python/asposeAPI.py',
                                env('ASPOSE_CLOUD_CLIENT_ID'),
                                env('ASPOSE_CLOUD_TOKEN'),
                                "xlsx",
                                $newFilePath,
                                $newFileNameWithoutExtension.".xlsx"
                            ],
                            null,
                            [
                                'SYSTEMROOT' => getenv('SYSTEMROOT'),
                                'PATH' => getenv("PATH")
                            ]);
                            try {
                                ini_set('max_execution_time', 300);
                                $asposeAPI->setTimeout(300);
                                $asposeAPI->run();
                            } catch (RuntimeException $message) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => 'Symfony runtime process out of time exception !',
                                        'errStatus' => $message->getMessage()
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
                                    'cnvToXls',
                                    'Symfony runtime process out of time exception !',
                                    $message->getMessage()
                                );
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    $message->getMessage(),
                                    $batchId,
                                    null,
                                    'Symfony runtime process out of time exception !'
                                );
                            } catch (ProcessFailedException $message) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => 'Symfony runtime process fail exception !',
                                        'errStatus' => $message->getMessage()
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
                                    'cnvToXls',
                                    'Symfony runtime process fail exception !',
                                    $message->getMessage()
                                );
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    $message->getMessage(),
                                    $batchId,
                                    null,
                                    'Symfony runtime process fail exception !'
                                );
                            }
                            if ($asposeAPI->isSuccessful()) {
                                if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.xlsx'), $newFileNameWithoutExtension.".xlsx") == true) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    $procFile += 1;
                                } else {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => 'FTP Server Connection Failed !',
                                            'errStatus' => null,
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
                                        'cnvToXls',
                                        'FTP Server Connection Failed !',
                                        null
                                    );
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
                                        'errReason' => 'Aspose API Error !, CnvToXLS failure',
                                        'errStatus' => $asposeAPI->getErrorOutput(),
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
                                    'cnvToXls',
                                    'Aspose API Error !, CnvToXLS failure',
                                    $asposeAPI->getErrorOutput()
                                );
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    $asposeAPI->getErrorOutput(),
                                    $batchId,
                                    null,
                                    'Aspose API Error !, CnvToXLS failure'
                                );
                            }
                        } else if ($convertType == 'pptx') {
                            if ($loopCount <= 1) {
                                if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pptx')) {
                                    Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pptx');
                                }
                            }
                            $asposeAPI = new Process([
                                'python3',
                                public_path().'/ext-python/asposeAPI.py',
                                env('ASPOSE_CLOUD_CLIENT_ID'),
                                env('ASPOSE_CLOUD_TOKEN'),
                                "pptx",
                                $newFilePath,
                                $newFileNameWithoutExtension.".pptx"
                            ],
                            null,
                            [
                                'SYSTEMROOT' => getenv('SYSTEMROOT'),
                                'PATH' => getenv("PATH")
                            ]);
                            try {
                                ini_set('max_execution_time', 600);
                                $asposeAPI->setTimeout(600);
                                $asposeAPI->run();
                            } catch (RuntimeException $message) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => 'Symfony runtime process out of time exception !',
                                        'errStatus' => $message->getMessage(),
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
                                    'cnvToPptx',
                                    'Symfony runtime process out of time exception !',
                                    $message->getMessage()
                                );
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    $message->getMessage(),
                                    $batchId,
                                    null,
                                    'Symfony runtime process out of time exception !'
                                );
                            } catch (ProcessFailedException $message) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => 'Symfony runtime process fail exception !',
                                        'errStatus' => $message->getMessage(),
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
                                    'cnvToPptx',
                                    'Symfony runtime process fail exception !',
                                    $message->getMessage()
                                );
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    $message->getMessage(),
                                    $batchId,
                                    null,
                                    'Symfony runtime process fail exception !'
                                );
                            }
                            if ($asposeAPI->isSuccessful()) {
                                if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pptx'), $newFileNameWithoutExtension.".pptx") == true) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    $procFile += 1;
                                } else {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => 'FTP Server Connection Failed !',
                                            'errStatus' => null,
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
                                        'cnvToPptx',
                                        'FTP Server Connection Failed !',
                                        null
                                    );
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
                                        'errReason' => 'Aspose API Error !, cnvToPptx failure',
                                        'errStatus' => $asposeAPI->getErrorOutput(),
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
                                    'cnvToPptx',
                                    'Aspose API Error !,
                                    cnvToPptx failure',
                                    $asposeAPI->getErrorOutput()
                                );
                                return $this->returnDataMesage(
                                    400,
                                    'PDF Convert failed !',
                                    $asposeAPI->getErrorOutput(),
                                    $batchId,
                                    null,
                                    'Aspose API Error !, cnvToPptx failure'
                                );
                            }
                        } else if ($convertType == 'docx') {
                            if ($loopCount <= 1) {
                                if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.docx')) {
                                    Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.docx');
                                }
                            }
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
                                        'errReason' => 'Aspose API Error !, CnvToDocx failure',
                                        'errStatus' => $e->getMessage()
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
                                if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.".docx"), $newFileNameWithoutExtension.".docx") == true) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    $procFile += 1;
                                } else {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    appLogModel::where('groupId', '=', $batchId)
                                        ->update([
                                            'errReason' => 'FTP Server Connection Failed !',
                                            'errStatus' => null
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
                                        'errReason' => 'Aspose API has fail while process, Please look on Aspose Dashboard !',
                                        'errStatus' => null
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
                            try {
                                $ilovepdfTask = new PdfjpgTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                $ilovepdfTask->setFileEncryption($pdfEncKey);
                                $ilovepdfTask->setEncryptKey($pdfEncKey);
                                $ilovepdfTask->setEncryption(true);
                                $pdfFile = $ilovepdfTask->addFile($newFilePath);
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
                                        'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                        'errStatus' => $e->getMessage()
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
                                $pdfImageTrueName = $newFileNameWithoutExtension.'.zip';
                            } else {
                                if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg'))) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    $procFile += 1;
                                    $pdfImageTrueName = $newFileNameWithoutExtension.'.jpg';
                                } else if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-0001.jpg'))) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    $procFile += 1;
                                    $pdfImageTrueName = $newFileNameWithoutExtension.'-0001.jpg';
                                }
                            }
                        } else if ($convertType == 'pdf') {
                            if ($loopCount <= 1) {
                                if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf')) {
                                    Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf');
                                }
                            }
                            try {
                                if ($pdfNameWithExtension == "jpg" || $pdfNameWithExtension == "jpeg" || $pdfNameWithExtension == "png" || $pdfNameWithExtension == "tiff") {
                                    $ilovepdfTask = new ImagepdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setFileEncryption($pdfEncKey);
                                    $ilovepdfTask->setEncryptKey($pdfEncKey);
                                    $ilovepdfTask->setEncryption(true);
                                    $pdfFile = $ilovepdfTask->addFile($newFilePath);
                                    $ilovepdfTask->setPageSize('fit');
                                    $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
                                    $ilovepdfTask->setPackagedFilename($newFileNameWithoutExtension);
                                    $ilovepdfTask->execute();
                                    $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                                } else {
                                    $ilovepdfTask = new OfficepdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                                    $pdfFile = $ilovepdfTask->addFile($newFilePath);
                                    $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
                                    $ilovepdfTask->execute();
                                    $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                                }
                            } catch (\Exception $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                        'errStatus' => $e->getMessage()
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
                            } else {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                appLogModel::where('groupId', '=', $batchId)
                                    ->update([
                                        'errReason' => 'Failed to download file from iLovePDF API !',
                                        'errStatus' => null
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
                            return $this->returnCoreMessage(
                                200,
                                'OK',
                                $pdfImageTrueName,
                                Storage::disk('local')->url($pdfDownload_Location.'/'.$pdfImageTrueName),
                                'convert',
                                $uuid,
                                $newFileSize,
                                null,
                                null,
                                null
                            );
                        } else {
                            return $this->returnCoreMessage(
                                200,
                                'OK',
                                $newFileNameWithoutExtension.'.'.$convertType,
                                Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.'.$convertType),
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
                                    if (file_exists($filePath)) {
                                        $relativePath = $file.'.zip';
                                        $zip->addFile($filePath, $relativePath);
                                    } else {
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        appLogModel::where('groupId', '=', $batchId)
                                            ->update([
                                                'errReason' => 'Failed convert PDF file !',
                                                'errStatus' => 'Failed convert PDF file !', 'File '. $filePath . ' was not found'
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
                            $tempPDFfiles = glob(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/*'));
                            foreach($tempPDFfiles as $file){
                                if(is_file($file)) {
                                   unlink($file);
                                }
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
                            return $this->returnCoreMessage(
                                200,
                                'OK',
                                $randomizePdfFileName.'.zip',
                                Storage::disk('local')->url($pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip'),
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
                                    'errReason' => 'Failed archiving PDF files !',
                                    'errStatus' => null
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
                            'errReason' => 'PDF convert failed',
                            'errStatus' => 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount
                        ]);
                    cnvModel::where('groupId', '=', $batchId)
                        ->update([
                            'result' => false,
                            'procDuration' => $duration->s.' seconds'
                        ]);
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
                    'errReason' =>  'PDF Convert failed !',
                    'errStatus' => 'PDF failed to upload'
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
