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

class mergeController extends Controller
{
    public function merge(Request $request) {
        $validator = Validator::make($request->all(),[
            'batch' => ['required', 'in:true,false'],
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
                    'errReason' => $validator->messages(),
                    'errApiReason' => null
                ]);
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'merge', 'FAIL','PDF Merged failed !',$validator->messages());
                return $this->returnCoreMessage(
                    200,
                    'PDF Merged failed !',
                    null,
                    null,
                    'merge',
                    $uuid,
                    null,
                    null,
                    null,
                    $validator->errors()->all()
                );
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'FAIL', 'merge', 'Database connection error !',$ex->getMessage());
                return $this->returnCoreMessage(
                    200,
                    'Database connection error !',
                    null,
                    null,
                    'merge',
                    $uuid,
                    null,
                    null,
                    null,
                    $ex->getMessage()
                );
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'merge', 'Eloquent transaction error !', $e->getMessage());
                return $this->returnCoreMessage(
                    200,
                    'Eloquent transaction error !',
                    null,
                    null,
                    'merge',
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
                $batch = $request->post('batch');
                $pdfEncKey = bin2hex(random_bytes(16));
                $pdfUpload_Location = env('PDF_UPLOAD');
                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                $pdfPool_Location = env('PDF_POOL');
                if ($batch == "true") {
                    $batchValue = true;
                    $batchId = $uuid;
                } else {
                    $batchValue = false;
                    $batchId = null;
                }
                $pdfDownload_Location = $pdfPool_Location;
                $str = rand(1000,10000000);
                $randomizePdfFileName = 'pdfMerged_'.substr(md5(uniqid($str)), 0, 8);
                try {
                    $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                    $ilovepdfTask = $ilovepdf->newTask('merge');
                    $ilovepdfTask->setFileEncryption($pdfEncKey);
                    $ilovepdfTask->setEncryptKey($pdfEncKey);
                    $ilovepdfTask->setEncryption(true);
                    foreach ($files as $file) {
                        $currentFileName = basename($file);
                        $trimPhase1 = str_replace(' ', '_', $currentFileName);
                        $firstTrim = basename($currentFileName, '.pdf');
                        $newFileNameWithoutExtension = str_replace('.', '_', $firstTrim);
                        $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                        $fileSize = filesize($newFilePath);
                        $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        $pdfName = $ilovepdfTask->addFile($newFilePath);
                    }
                    $pdfName->setPassword($pdfEncKey);
                    $ilovepdfTask->setOutputFileName($randomizePdfFileName);
                    $ilovepdfTask->execute();
                    $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                    $ilovepdfTask->delete();
                } catch (StartException $e) {
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                'errApiReason' => $e->getMessage()
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'PDF Merged failed !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $e->getMessage()
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Database connection error !',$ex->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Eloquent transaction error !', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                'errApiReason' => $e->getMessage()
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'PDF Merged failed !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $e->getMessage()
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Database connection error !',$ex->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Eloquent transaction error !', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                'errApiReason' => $e->getMessage()
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'PDF Merged failed !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $e->getMessage()
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Database connection error !',$ex->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Eloquent transaction error !', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                'errApiReason' => $e->getMessage()
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'PDF Merged failed !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $e->getMessage()
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Database connection error !',$ex->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Eloquent transaction error !', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                'errApiReason' => $e->getMessage()
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'PDF Merged failed !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $e->getMessage()
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Database connection error !',$ex->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Eloquent transaction error !', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                'errApiReason' => $e->getMessage()
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'PDF Merged failed !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $e->getMessage()
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Database connection error !',$ex->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Eloquent transaction error !', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                'errApiReason' => $e->getMessage()
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'PDF Merged failed !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $e->getMessage()
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Database connection error !',$ex->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Eloquent transaction error !', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
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
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => null,
                            'result' => false,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                'errApiReason' => $e->getMessage()
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'PDF Merged failed !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $e->getMessage()
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Database connection error !',$ex->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Eloquent transaction error !', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            $currentFileName,
                            null,
                            'merge',
                            $uuid,
                            $newFileSize,
                            null,
                            null,
                            $e->getMessage()
                        );
                    }
                }
                if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'))) {
                    $mergedFileSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'));
                    $newMergedFileSize = AppHelper::instance()->convert($mergedFileSize, "MB");
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfMerge')->insert([
                            'fileName' => $randomizePdfFileName.'.pdf',
                            'fileSize' => $newMergedFileSize,
                            'result' => true,
                            'isBatch' => false,
                            'processId' => $uuid,
                            'batchId' => null,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        return $this->returnCoreMessage(
                            200,
                            'OK',
                            $randomizePdfFileName.'.pdf',
                            Storage::disk('local')->url($pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'),
                            'merge',
                            $uuid,
                            $newMergedFileSize,
                            null,
                            null,
                            null
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.pdf', $mergedFileSize, $uuid, 'FAIL', 'merge', 'Database connection error !',$ex->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            $randomizePdfFileName.'.pdf',
                            null,
                            'merge',
                            $uuid,
                            $mergedFileSize,
                            null,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.pdf', $mergedFileSize, $uuid, 'FAIL', 'merge', 'Eloquent transaction error !', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            $randomizePdfFileName.'.pdf',
                            null,
                            'merge',
                            $uuid,
                            $mergedFileSize,
                            null,
                            null,
                            $e->getMessage()
                        );
                    }
                } else {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'merge', 'Failed to download file from iLovePDF API !', 'null');
                    return $this->returnCoreMessage(
                        200,
                        'PDF Merge failed !',
                        $currentFileName,
                        null,
                        'merge',
                        $uuid,
                        $newFileSize,
                        null,
                        null,
                        null
                    );
                }
            } else {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfMerge')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'result' => false,
                        'isBatch' => false,
                        'processId' => $uuid,
                        'batchId' => null,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'PDF failed to upload !',
                            'errApiReason' => null
                    ]);
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'merge', 'PDF failed to upload !', null);
                    return $this->returnCoreMessage(
                        200,
                        'PDF failed to upload !',
                        null,
                        null,
                        'merge',
                        $uuid,
                        null,
                        null,
                        null,
                        null
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'merge', 'Database connection error !',$ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        null,
                        null,
                        'merge',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'merge', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        null,
                        null,
                        'merge',
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
