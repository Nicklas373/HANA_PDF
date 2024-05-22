<?php

namespace App\Http\Controllers\Api\Core;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
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
use Ilovepdf\Exceptions\StartException;
use Ilovepdf\Exceptions\AuthException;
use Ilovepdf\Exceptions\UploadException;
use Ilovepdf\Exceptions\ProcessException;
use Ilovepdf\Exceptions\DownloadException;
use Ilovepdf\Exceptions\TaskException;
use Ilovepdf\Exceptions\PathException;
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

        $uuid = AppHelper::Instance()->get_guid();

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

        if ($validator->fails()) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => $validator->messages(),
                    'errStatus' => null
                ]);
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'FAIL', 'convert', 'PDF Conversion failed !',$validator->messages());
                return $this->returnCoreMessage(
                    200,
                    'PDF Conversion failed !',
                    null,
                    null,
                    'convert',
                    $uuid,
                    null,
                    null,
                    null,
                    $validator->errors()->all()
                );
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'FAIL', 'convert', 'Database connection error !',$ex->getMessage());
                return $this->returnCoreMessage(
                    200,
                    'Database connection error !',
                    null,
                    null,
                    'convert',
                    $uuid,
                    null,
                    null,
                    null,
                    $ex->getMessage()
                );
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'convert', 'Eloquent transaction error !', $e->getMessage());
                return $this->returnCoreMessage(
                    200,
                    'Eloquent transaction error !',
                    null,
                    null,
                    'convert',
                    $uuid,
                    null,
                    null,
                    null,
                    $ex->getMessage()
                );
            }
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
                    $batchId = $uuid;
                } else {
                    $batchValue = false;
                    $batchId = null;
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
                    $Nuuid = AppHelper::Instance()->get_guid();
                    $currentFileName = basename($file);
                    $trimPhase1 = str_replace(' ', '_', $currentFileName);
                    $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                    array_push($poolFiles, $newFileNameWithoutExtension);
                    $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                    $fileSize = filesize($newFilePath);
                    $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                    $pdfTotalPages = AppHelper::instance()->count($newFilePath);
                    $pdfNameWithExtension = pathinfo($currentFileName, PATHINFO_EXTENSION);
                    if ($convertType == 'xlsx') {
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
                            ini_set('max_execution_time', 600);
                            $asposeAPI->setTimeout(600);
                            $asposeAPI->run();
                        } catch (RuntimeException $message) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => 'Symfony runtime process out of time exception !',
                                    'errStatus' => $message->getMessage(),
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Symfony runtime process out of time exception !', $message->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'HANA PDF process timeout !',
                                    $currentFileName,
                                    null,
                                    'cnvToXls',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $message->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToXls',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToXls',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            }
                        } catch (ProcessFailedException $message) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => 'Symfony runtime process fail exception !',
                                    'errStatus' => $message->getMessage(),
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Symfony runtime process fail exception !', $message->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'HANA PDF process timeout !',
                                    $currentFileName,
                                    null,
                                    'cnvToXls',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $message->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToXls',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToXls',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            }
                        }
                        if (!$asposeAPI->isSuccessful()) {
                            if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.xlsx'), $newFileNameWithoutExtension.".xlsx") == true) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.xlsx'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $procFile += 1;
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToXls',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToXls',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'Converted file not found on the server !',
                                        'errStatus' => $asposeAPI->getErrorOutput(),
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Converted file not found on the server !', $asposeAPI->getErrorOutput());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion failed !',
                                        $currentFileName,
                                        null,
                                        'cnvToXls',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $asposeAPI->getErrorOutput()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToXls',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToXls',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                }
                            }
                        } else {
                            if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.xlsx'), $newFileNameWithoutExtension.".xlsx") == true) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.xlsx'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $procFile += 1;
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToXls',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToXls',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'Converted file not found on the server !',
                                        'errStatus' => $asposeAPI->getErrorOutput(),
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Converted file not found on the server !', $asposeAPI->getErrorOutput());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion failed !',
                                        $currentFileName,
                                        null,
                                        'cnvToXls',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $asposeAPI->getErrorOutput()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToXls',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToXls', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToXls',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                }
                            }
                        }
                    } else if ($convertType == 'pptx') {
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
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => 'Symfony runtime process out of time exception !',
                                    'errStatus' => $message->getMessage(),
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Symfony runtime process out of time exception !', $message->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'HANA PDF process timeout !',
                                    $currentFileName,
                                    null,
                                    'cnvToPptx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $message->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToPptx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToPptx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            }
                        } catch (ProcessFailedException $message) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => 'Symfony runtime process fail exception !',
                                    'errStatus' => $message->getMessage(),
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Symfony runtime process fail exception !', $message->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'HANA PDF process timeout !',
                                    $currentFileName,
                                    null,
                                    'cnvToPptx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $message->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToPptx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToPptx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            }
                        }
                        if (!$asposeAPI->isSuccessful()) {
                            if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pptx'), $newFileNameWithoutExtension.".pptx") == true) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pptx'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $procFile += 1;
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToPptx',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToPptx',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'Converted file not found on the server !',
                                        'errStatus' => $asposeAPI->getErrorOutput(),
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Converted file not found on the server !', $asposeAPI->getErrorOutput());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion failed !',
                                        $currentFileName,
                                        null,
                                        'cnvToPptx',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $asposeAPI->getErrorOutput()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToPptx',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToPptx',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                }
                            }
                        } else {
                            if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pptx'), $newFileNameWithoutExtension.".pptx") == true) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pptx'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $procFile += 1;
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToPptx',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToPptx',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'Converted file not found on the server !',
                                        'errStatus' => $asposeAPI->getErrorOutput(),
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Converted file not found on the server !', $asposeAPI->getErrorOutput());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion failed !',
                                        $currentFileName,
                                        null,
                                        'cnvToPptx',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $asposeAPI->getErrorOutput()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToPptx',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToPptx', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToPptx',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                }
                            }
                        }
                    } else if ($convertType == 'docx') {
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
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => 'Aspose Clouds API Error !',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToDocx', 'Aspose Clouds API Error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Error while connecting to external API !',
                                    $currentFileName,
                                    null,
                                    'cnvToDocx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToDocx', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToDocx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToDocx', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToDocx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            }
                        }
                        if (json_decode($result, true) !== NULL) {
                            if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.".docx"), $newFileNameWithoutExtension.".docx") == true) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.docx'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                $procFile += 1;
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToDocx', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToDocx',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToDocx', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToDocx',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'FTP Server Connection Failed !',
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToDocx', 'Aspose Clouds API Error !', null);
                                    return $this->returnCoreMessage(
                                        200,
                                        'Error while connecting to external API !',
                                        $currentFileName,
                                        null,
                                        'cnvToDocx',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        null
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToDocx', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToDocx',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToDocx', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToDocx',
                                        $Nuuid,
                                        $newFileSize,
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
                                    'processId' => $Nuuid,
                                    'errReason' => 'Aspose Clouds API has fail while process, Please look on Aspose Dashboard !',
                                    'errStatus' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToDocx', 'Aspose Clouds API Error !', null);
                                return $this->returnCoreMessage(
                                    200,
                                    'Error while connecting to external API !',
                                    $currentFileName,
                                    null,
                                    'cnvToDocx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    null
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToDocx', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToDocx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToDocx', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToDocx',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            }
                        }
                    } else if ($convertType == 'jpg') {
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
                        } catch (StartException $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF Conversion Failed',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
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
                                    'processId' => $Nuuid,
                                    'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF Conversion Failed',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
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
                                    'processId' => $Nuuid,
                                    'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF Conversion Failed',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
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
                                    'processId' => $Nuuid,
                                    'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF Conversion Failed',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
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
                                    'processId' => $Nuuid,
                                    'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF Conversion Failed',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
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
                                    'processId' => $Nuuid,
                                    'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF Conversion Failed',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
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
                                    'processId' => $Nuuid,
                                    'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF Conversion Failed',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
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
                                    'processId' => $Nuuid,
                                    'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                    'errStatus' => $e->getMessage()
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF Conversion Failed',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            }
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
                            $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.zip'));
                            $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errStatus' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileProcSize,
                                    'container' => $convertType,
                                    'imgExtract' => $extMode,
                                    'result' => true,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'cnvToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            }
                        } else {
                            if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg'))) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $procFile += 1;
                                $pdfImageTrueName = $newFileNameWithoutExtension.'.jpg';
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.jpg'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => $extMode,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToImg',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToImg',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                }
                            } else if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-0001.jpg'))) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                $procFile += 1;
                                $pdfImageTrueName = $newFileNameWithoutExtension.'-0001.jpg';
                                $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'-0001.jpg'));
                                $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => null,
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileProcSize,
                                        'container' => $convertType,
                                        'imgExtract' => $extMode,
                                        'result' => true,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'cnvToImg',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'cnvToImg', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'cnvToImg',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                }
                            }
                        }
                    } else if ($convertType == 'pdf') {
                        if ($pdfNameWithExtension == "jpg" || $pdfNameWithExtension == "jpeg" || $pdfNameWithExtension == "png" || $pdfNameWithExtension == "tiff") {
                            try {
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
                            } catch (StartException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'imgToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'imgToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                }
                            }
                        } else {
                            try {
                                $ilovepdfTask = new OfficepdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                                $pdfFile = $ilovepdfTask->addFile($newFilePath);
                                $ilovepdfTask->setOutputFileName($newFileNameWithoutExtension);
                                $ilovepdfTask->execute();
                                $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                            } catch (StartException $e) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
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
                                        'processId' => $Nuuid,
                                        'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                        'errStatus' => $e->getMessage()
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $Nuuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'PDF Conversion Failed',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'docToPDF', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'docToPDF',
                                        $Nuuid,
                                        $newFileSize,
                                        null,
                                        null,
                                        $e->getMessage()
                                    );
                                }
                            }
                        }
                        if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'))) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            $fileProcSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'));
                            $newFileProcSize = AppHelper::instance()->convert($fileProcSize, "MB");
                            $procFile += 1;
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $Nuuid,
                                    'errReason' => null,
                                    'errStatus' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileProcSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => true,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'pdfToImg', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'pdfToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'pdfToImg', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'pdfToImg',
                                    $Nuuid,
                                    $newFileSize,
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
                                    'processId' => $Nuuid,
                                    'errReason' => 'Failed to download converted file from iLovePDF API !',
                                    'errStatus' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $currentFileName,
                                    'fileSize' => $newFileSize,
                                    'container' => $convertType,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'isBatch' => $batchValue,
                                    'processId' => $Nuuid,
                                    'batchId' => $batchId,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'pdfToImg', 'Failed to download converted file from iLovePDF API !', null);
                                return $this->returnCoreMessage(
                                    200,
                                    'PDF Conversion failed !',
                                    $currentFileName,
                                    null,
                                    'pdfToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    null,
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'pdfToImg', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'pdfToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'pdfToImg', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'pdfToImg',
                                    $Nuuid,
                                    $newFileSize,
                                    null,
                                    null,
                                    $e->getMessage()
                                );
                            }
                        }
                    }
                }
                if ($loopCount == $procFile) {
                    if ($loopCount == 1) {
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
                        try {
                            if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
                                if ($convertType == 'jpg') {
                                    foreach ($poolFiles as $file) {
                                        $filePath = $folderPath.DIRECTORY_SEPARATOR.$file.'.zip';
                                        if (file_exists($filePath)) {
                                            $relativePath = $file.'.zip';
                                            $zip->addFile($filePath, $relativePath);
                                        } else {
                                            try {
                                                DB::table('appLogs')->insert([
                                                    'processId' => $uuid,
                                                    'errReason' => 'Failed convert PDF file !',
                                                    'errStatus' => 'File '. $filePath . ' was not found'
                                                ]);
                                                DB::table('pdfConvert')->insert([
                                                    'fileName' => $currentFileName,
                                                    'fileSize' => $newFileSize,
                                                    'container' => $convertType,
                                                    'imgExtract' => false,
                                                    'result' => false,
                                                    'isBatch' => $batchValue,
                                                    'processId' => $uuid,
                                                    'batchId' => $batchId,
                                                    'procStartAt' => $startProc,
                                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                    'procDuration' =>  $duration->s.' seconds'
                                                ]);
                                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'convert', 'Failed convert PDF file !', 'File '. $filePath . ' was not found');
                                                return $this->returnCoreMessage(
                                                    200,
                                                    'Failed convert PDF file !',
                                                    $currentFileName,
                                                    null,
                                                    'convert',
                                                    $uuid,
                                                    null,
                                                    $newFileSize,
                                                    $convertType,
                                                    'File '. $filePath . ' was not found'
                                                );
                                            } catch (QueryException $ex) {
                                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $uuid, 'FAIL', 'convert', 'Database connection error !', $ex->getMessage());
                                                return $this->returnCoreMessage(
                                                    200,
                                                    'Database connection error !',
                                                    $currentFileName,
                                                    null,
                                                    'convert',
                                                    $uuid,
                                                    null,
                                                    $newFileSize,
                                                    $convertType,
                                                    $ex->getMessage()
                                                );
                                            } catch (\Exception $e) {
                                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $uuid, 'FAIL', 'convert', 'Eloquent transaction error !', $e->getMessage());
                                                return $this->returnCoreMessage(
                                                    200,
                                                    'Eloquent transaction error !',
                                                    $currentFileName,
                                                    null,
                                                    'convert',
                                                    $uuid,
                                                    null,
                                                    $newFileSize,
                                                    $convertType,
                                                    $e->getMessage()
                                                );
                                            }
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
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
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
                                        Storage::disk('local')->url($pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip'),
                                        'convert',
                                        $uuid,
                                        null,
                                        $newFileSize,
                                        $convertType,
                                        null
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $uuid, 'FAIL', 'convert', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'convert',
                                        $uuid,
                                        null,
                                        $newFileSize,
                                        $convertType,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $uuid, 'FAIL', 'convert', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'convert',
                                        $uuid,
                                        null,
                                        $newFileSize,
                                        $convertType,
                                        $e->getMessage()
                                    );
                                }
                            } else {
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => 'Failed convert PDF file !',
                                        'errStatus' => 'Archive processing failure'
                                    ]);
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => $currentFileName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'convert', 'Failed convert PDF file !',  'Archive processing failure');
                                    return $this->returnCoreMessage(
                                        200,
                                        'Failed archiving PDF files !',
                                        $currentFileName,
                                        null,
                                        'convert',
                                        $uuid,
                                        $newFileSize,
                                        null,
                                        $convertType,
                                        'Archive processing failure'
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $uuid, 'FAIL', 'convert', 'Database connection error !', $ex->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $currentFileName,
                                        null,
                                        'convert',
                                        $uuid,
                                        null,
                                        $newFileSize,
                                        $convertType,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $uuid, 'FAIL', 'convert', 'Eloquent transaction error !', $e->getMessage());
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $currentFileName,
                                        null,
                                        'convert',
                                        $uuid,
                                        null,
                                        $newFileSize,
                                        $convertType,
                                        $e->getMessage()
                                    );
                                }
                            }
                        } catch (\Exception $e) {
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => 'Archive processing failure',
                                    'errStatus' => $e->getMessage()
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.zip', null, $uuid, 'FAIL', 'convert', 'Failed convert PDF file!', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Failed archiving PDF files !',
                                    $currentFileName,
                                    null,
                                    'convert',
                                    $uuid,
                                    null,
                                    $newFileSize,
                                    $convertType,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'convert', 'Database connection error !', $ex->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    $currentFileName,
                                    null,
                                    'convert',
                                    $uuid,
                                    null,
                                    $newFileSize,
                                    $convertType,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'convert', 'Eloquent transaction error !', $e->getMessage());
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    $currentFileName,
                                    null,
                                    'convert',
                                    $uuid,
                                    null,
                                    $newFileSize,
                                    $convertType,
                                    $e->getMessage()
                                );
                            }
                        }
                    }
                } else {
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'PDF convert failed',
                            'errStatus' => 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.zip', null, $uuid, 'FAIL', 'convert', 'PDF Compress failed !', 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount);
                        return $this->returnCoreMessage(
                            200,
                            'PDF Conversion failed !',
                            $currentFileName,
                            null,
                            'convert',
                            $uuid,
                            null,
                            $newFileSize,
                            $convertType,
                            'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'convert', 'Database connection error !', $ex->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            $currentFileName,
                            null,
                            'convert',
                            $uuid,
                            null,
                            $newFileSize,
                            $convertType,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $uuid, 'FAIL', 'convert', 'Eloquent transaction error !', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            $currentFileName,
                            null,
                            'convert',
                            $uuid,
                            null,
                            $newFileSize,
                            $convertType,
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
                        'errReason' => 'PDF failed to upload !',
                        'errStatus' => $e->getMessage()
                    ]);
                    DB::table('pdfConvert')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'container' => null,
                        'imgExtract' => false,
                        'result' => false,
                        'isBatch' => false,
                        'processId' => $uuid,
                        'batchId' => null,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'convert', 'PDF failed to upload !', null);
                    return $this->returnCoreMessage(
                        200,
                        'PDF failed to upload !',
                        null,
                        null,
                        'convert',
                        $uuid,
                        null,
                        null,
                        null,
                        null
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'convert', 'Database connection error !', $ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        null,
                        null,
                        'convert',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'convert', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        null,
                        null,
                        'convert',
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
