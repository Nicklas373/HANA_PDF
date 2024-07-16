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
                    'errStatus' => $validator->messages()
                ]);
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'FAIL','compress','PDF Compression failed !',$validator->messages(), true);
                return $this->returnCoreMessage(
                    200,
                    'PDF Compression failed !',
                    null,
                    null,
                    'compress',
                    $uuid,
                    null,
                    null,
                    null,
                    $validator->errors()->all()
                );
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'FAIL','compress','Database connection error !',$ex->getMessage(), false);
                return $this->returnCoreMessage(
                    200,
                    'Database connection error !',
                    null,
                    null,
                    'compress',
                    $uuid,
                    null,
                    null,
                    null,
                    $ex->getMessage()
                );
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL','compress','Eloquent transaction error !', $e->getMessage(), false);
                return $this->returnCoreMessage(
                    200,
                    'Eloquent transaction error !',
                    null,
                    null,
                    'compress',
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
                $str = rand(1000,10000000);
                $randomizePdfFileName = 'pdfCompress_'.substr(md5(uniqid($str)), 0, 8);
                foreach ($files as $file) {
                    $Nuuid = AppHelper::Instance()->get_guid();
                    $currentFileName = basename($file);
                    $trimPhase1 = str_replace(' ', '_', $currentFileName);
                    $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                    array_push($poolFiles, $newFileNameWithoutExtension);
                    $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                    $fileSize = filesize($newFilePath);
                    $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
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
                    } catch (StartException $e) {
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $Nuuid,
                                'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                'errStatus' => $e->getMessage()
                            ]);
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'iLovePDF API Error !, Catch on StartException', $e->getMessage(), true);
                            return $this->returnCoreMessage(
                                200,
                                'PDF Compression failed !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $e->getMessage()
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Database connection error !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Eloquent transaction error !',
                                $currentFileName,
                                null,
                                'compress',
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage(), true);
                            return $this->returnCoreMessage(
                                200,
                                'PDF Compression failed !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $e->getMessage()
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Database connection error !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Eloquent transaction error !',
                                $currentFileName,
                                null,
                                'compress',
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage(), true);
                            return $this->returnCoreMessage(
                                200,
                                'PDF Compression failed !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $e->getMessage()
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Database connection error !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Eloquent transaction error !',
                                $currentFileName,
                                null,
                                'compress',
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage(), true);
                            return $this->returnCoreMessage(
                                200,
                                'PDF Compression failed !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $e->getMessage()
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Database connection error !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Eloquent transaction error !',
                                $currentFileName,
                                null,
                                'compress',
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage(), true);
                            return $this->returnCoreMessage(
                                200,
                                'PDF Compression failed !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $e->getMessage()
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Database connection error !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Eloquent transaction error !',
                                $currentFileName,
                                null,
                                'compress',
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage(), true);
                            return $this->returnCoreMessage(
                                200,
                                'PDF Compression failed !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $e->getMessage()
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Database connection error !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Eloquent transaction error !',
                                $currentFileName,
                                null,
                                'compress',
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'iLovePDF API Error !, Catch on PathException', $e->getMessage(), true);
                            return $this->returnCoreMessage(
                                200,
                                'PDF Compression failed !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $e->getMessage()
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Database connection error !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Eloquent transaction error !',
                                $currentFileName,
                                null,
                                'compress',
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
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $fileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'iLovePDF API Error !, Catch on Exception', $e->getMessage(), true);
                            return $this->returnCoreMessage(
                                200,
                                'PDF Compression failed !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $e->getMessage()
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Database connection error !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $Nuuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Eloquent transaction error !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $e->getMessage()
                            );
                        }
                    }
                    if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'))) {
                        $compFileSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'));
                        $newCompFileSize = AppHelper::instance()->convert($compFileSize, "MB");
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        $procFile += 1;
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $Nuuid,
                                'errReason' => null,
                                'errStatus' => null
                            ]);
                            DB::table('pdfCompress')->insert([
                                'fileName' => $newFileNameWithoutExtension.'.pdf',
                                'fileSize' => $newFileSize,
                                'compFileSize' => $newCompFileSize,
                                'compMethod' => $compMethod,
                                'result' => true,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.pdf', $newFileSize, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), true);
                            return $this->returnCoreMessage(
                                200,
                                'Database connection error !',
                                $newFileNameWithoutExtension.'.pdf',
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($newFileNameWithoutExtension.'.pdf', $newFileSize, $Nuuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Eloquent transaction error !',
                                $newFileNameWithoutExtension.'.pdf',
                                null,
                                'compress',
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
                                'errReason' => 'Failed to download file from iLovePDF API !',
                                'errStatus' => null
                            ]);
                            DB::table('pdfCompress')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $Nuuid,
                                'batchId' => $batchId,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'compress', 'Failed to download file from iLovePDF API !', null, true);
                            return $this->returnCoreMessage(
                                200,
                                'PDF Compression failed !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                null
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Database connection error !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $newFileSize, $Nuuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnCoreMessage(
                                200,
                                'Eloquent transaction error !',
                                $currentFileName,
                                null,
                                'compress',
                                $Nuuid,
                                $newFileSize,
                                null,
                                null,
                                $e->getMessage()
                            );
                        }
                    }
                }
                if ($loopCount == $procFile) {
                    if ($loopCount == 1) {
                        return $this->returnCoreMessage(
                            200,
                            'OK',
                            $newFileNameWithoutExtension.'.pdf',
                            Storage::disk('local')->url($pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf'),
                            'compress',
                            $uuid,
                            $newFileSize,
                            $newCompFileSize,
                            $compMethod,
                            null
                        );
                    } else {
                        $folderPath = Storage::disk('local')->path('public/'.$pdfDownload_Location);
                        $zipFilePath = Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$randomizePdfFileName.'.zip');
                        $zip = new ZipArchive();
                        try {
                            if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
                                foreach ($poolFiles as $file) {
                                    $filePath = $folderPath.DIRECTORY_SEPARATOR.$file.'.pdf';
                                    if (file_exists($filePath)) {
                                        $relativePath = $file.'.pdf';
                                        $zip->addFile($filePath, $relativePath);
                                    } else {
                                        try {
                                            DB::table('appLogs')->insert([
                                                'processId' => $uuid,
                                                'errReason' => 'Failed Compress PDF file !',
                                                'errStatus' => 'File '. $filePath . ' was not found'
                                            ]);
                                            DB::table('pdfCompress')->insert([
                                                'fileName' => $randomizePdfFileName.'zip.',
                                                'fileSize' => null,
                                                'compFileSize' => null,
                                                'compMethod' => $compMethod,
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
                                                'Failed Compress PDF file !',
                                                $randomizePdfFileName.'zip.',
                                                null,
                                                'compress',
                                                $uuid,
                                                null,
                                                null,
                                                null,
                                                'File '. $filePath . ' was not found'
                                            );
                                            NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $uuid, 'FAIL', 'compress', 'Failed Compress PDF file !', 'File '. $filePath . ' was not found', true);
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                                            return $this->returnCoreMessage(
                                                200,
                                                'Database connection error !',
                                                $randomizePdfFileName.'zip.',
                                                null,
                                                'compress',
                                                $Nuuid,
                                                null,
                                                null,
                                                null,
                                                $ex->getMessage()
                                            );
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $uuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                                            return $this->returnCoreMessage(
                                                200,
                                                'Eloquent transaction error !',
                                                $randomizePdfFileName.'zip.',
                                                null,
                                                'compress',
                                                $uuid,
                                                null,
                                                null,
                                                null,
                                                $e->getMessage()
                                            );
                                        }
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
                                }
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfCompress')->insert([
                                        'fileName' => $randomizePdfFileName.'zip.',
                                        'fileSize' => $newZipFileSize,
                                        'compFileSize' => null,
                                        'compMethod' => $compMethod,
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
                                        'compress',
                                        $uuid,
                                        null,
                                        $newZipFileSize,
                                        $compMethod,
                                        null
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $randomizePdfFileName.'zip.',
                                        null,
                                        'compress',
                                        $Nuuid,
                                        null,
                                        $newZipFileSize,
                                        $compMethod,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $uuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $randomizePdfFileName.'zip.',
                                        null,
                                        'compress',
                                        $uuid,
                                        null,
                                        $newZipFileSize,
                                        $compMethod,
                                        $e->getMessage()
                                    );
                                }
                            } else {
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => 'Failed archiving PDF files !',
                                        'errStatus' => null
                                    ]);
                                    DB::table('pdfCompress')->insert([
                                        'fileName' => $randomizePdfFileName.'zip.',
                                        'fileSize' => null,
                                        'compFileSize' => null,
                                        'compMethod' => $compMethod,
                                        'result' => false,
                                        'isBatch' => $batchValue,
                                        'processId' => $uuid,
                                        'batchId' => $batchId,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.zip', null, $uuid, 'FAIL', 'compress', 'Failed archiving PDF files !', null, true);
                                    return $this->returnCoreMessage(
                                        200,
                                        'Failed archiving PDF files !',
                                        $randomizePdfFileName.'.zip',
                                        null,
                                        'compress',
                                        $uuid,
                                        null,
                                        $compMethod,
                                        null,
                                        'Archive processing failure'
                                    );
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $uuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                                    return $this->returnCoreMessage(
                                        200,
                                        'Database connection error !',
                                        $randomizePdfFileName.'zip.',
                                        null,
                                        'compress',
                                        $uuid,
                                        null,
                                        null,
                                        $compMethod,
                                        $ex->getMessage()
                                    );
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'zip.', null, $uuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                                    return $this->returnCoreMessage(
                                        200,
                                        'Eloquent transaction error !',
                                        $randomizePdfFileName.'zip.',
                                        null,
                                        'compress',
                                        $uuid,
                                        null,
                                        null,
                                        $compMethod,
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
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.zip', null, $uuid, 'FAIL', 'compress', 'Failed archiving PDF files !', $e->getMessage(), true);
                                return $this->returnCoreMessage(
                                    200,
                                    'Failed archiving PDF files !',
                                    $currentFileName,
                                    null,
                                    'compress',
                                    $uuid,
                                    null,
                                    null,
                                    $compMethod,
                                    $e->getMessage()
                                );
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.zip', null, $uuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                                return $this->returnCoreMessage(
                                    200,
                                    'Database connection error !',
                                    null,
                                    null,
                                    'compress',
                                    $uuid,
                                    null,
                                    null,
                                    $compMethod,
                                    $ex->getMessage()
                                );
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.zip', null, $uuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                                return $this->returnCoreMessage(
                                    200,
                                    'Eloquent transaction error !',
                                    null,
                                    null,
                                    'compress',
                                    $uuid,
                                    null,
                                    null,
                                    $compMethod,
                                    $e->getMessage()
                                );
                            }
                        }
                    }
                } else {
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'PDF Compress failed',
                            'errStatus' => 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.zip', null, $uuid, 'FAIL', 'compress', 'PDF Compress failed !', 'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount, true);
                        return $this->returnCoreMessage(
                            200,
                            'PDF Compression failed',
                            null,
                            null,
                            'compress',
                            $uuid,
                            null,
                            null,
                            $compMethod,
                            'Processed file are not same with total file, processed: '.$procFile.' totalFile: '.$loopCount
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            null,
                            null,
                            'compress',
                            $uuid,
                            null,
                            null,
                            $compMethod,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            null,
                            null,
                            'compress',
                            $uuid,
                            null,
                            null,
                            $compMethod,
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
                        'errStatus' => null
                    ]);
                    DB::table('pdfCompress')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'compFileSize' => null,
                        'compMethod' => null,
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'compress', 'PDF failed to upload !', null, true);
                    return $this->returnCoreMessage(
                        200,
                        'PDF failed to upload !',
                        null,
                        null,
                        'compress',
                        $uuid,
                        null,
                        null,
                        null,
                        null
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $Nuuid, 'FAIL', 'compress', 'Database connection error !', $ex->getMessage(), false);
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        null,
                        null,
                        'compress',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'compress', 'Eloquent transaction error !', $e->getMessage(), false);
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        null,
                        null,
                        'compress',
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
