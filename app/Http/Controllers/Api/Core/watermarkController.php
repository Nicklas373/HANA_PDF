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

class watermarkController extends Controller
{
    public function watermark(Request $request) {
        $validator = Validator::make($request->all(),[
            'file' => '',
            'imgFile' => '',
            'action' => ['required', 'in:img,txt'],
            'wmFontColor' => ['nullable','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'wmFontSize' => ['nullable', 'numeric'],
            'wmFontStyle' => ['required', 'in:Regular,Bold,Italic'],
            'wmFontFamily' => ['required', 'in:Arial,Arial Unicode MS,Comic Sans MS,Courier,Times New Roman,Verdana'],
            'wmLayoutStyle' => ['required', 'in:above,below'],
            'wmRotation' => ['nullable', 'numeric'],
            'wmPage' => ['nullable', 'regex:/^[0-9a-zA-Z,-]+$/'],
            'wmText' => ['nullable','string'],
            'wmTransparency' => ['nullable', 'numeric'],
            'wmMosaic' => ['required', 'in:true,false']
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
                    'errStatus' => $validator->messages()->first()
                ]);
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'FAIL','watermark','Validation failed',$validator->messages()->first(), true);
                return $this->returnDataMesage(
                    401,
                    'Validation failed',
                    null,
                    null,
                    $validator->messages()->first()
                );
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify(null,null, $uuid, 'FAIL','watermark','Database connection error !',$ex->getMessage(), false);
                return $this->returnDataMesage(
                    500,
                    'Database connection error !',
                    null,
                    null,
                    $ex->getMessage()
                );
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL','watermark','Eloquent transaction error !', $e->getMessage(), false);
                return $this->returnDataMesage(
                    500,
                    'Eloquent transaction error !',
                    null,
                    null,
                    $ex->getMessage()
                );
            }
		} else {
            if ($request->has('file')) {
                $files = $request->post('file');
                $pdfEncKey = bin2hex(random_bytes(16));
                $pdfUpload_Location = env('PDF_UPLOAD');
                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                $pdfDownload_Location = $pdfProcessed_Location;
                $batchValue = false;
                $batchId = null;
                $str = rand(1000,10000000);
                foreach ($files as $file) {
                    $currentFileName = basename($file);
                    $trimPhase1 = str_replace(' ', '_', $currentFileName);
                    $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                    $randomizePdfFileName = 'pdfWatermark_'.substr(md5(uniqid($str)), 0, 8);
                    $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$trimPhase1);
                    $fileSize = filesize($newFilePath);
                    $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                    $watermarkAction = $request->post('action');
                    if (Storage::disk('local')->exists('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf')) {
                        Storage::disk('local')->delete('public/'.$pdfDownload_Location.'/'.$newFileNameWithoutExtension.'.pdf');
                    }
                    if ($watermarkAction == 'img') {
                        $watermarkImage = $request->file('imgFile');
                        $currentImageName = basename($watermarkImage);
                        $trimPhase1 = str_replace(' ', '_', $currentImageName);
                        $randomizeImageExtension = pathinfo($watermarkImage->getClientOriginalName(), PATHINFO_EXTENSION);
                        $wmImageName = $newFileNameWithoutExtension.'.'.$randomizeImageExtension;
                        $watermarkImage->storeAs('public/upload', $wmImageName);
                    } else if ($watermarkAction == 'txt') {
                        $wmImageName = '';
                    } else {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'Invalid request action !',
                            'errStatus' => 'Current request: '.$watermarkAction
                        ]);
                        DB::table('pdfWatermark')->insert([
                            'fileName' => $currentFileName,
                            'fileSize' => $newFileSize,
                            'watermarkFontFamily' => null,
                            'watermarkFontStyle' => null,
                            'watermarkFontSize' => null,
                            'watermarkFontTransparency' => null,
                            'watermarkImage' => null,
                            'watermarkLayout' => null,
                            'watermarkMosaic' => null,
                            'watermarkRotation' => null,
                            'watermarkStyle' => null,
                            'watermarkText' => null,
                            'watermarkPage' => null,
                            'result' => false,
                            'isBatch' => $batchValue,
                            'processId' => $uuid,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'Invalid request action !', 'Current request: '.$watermarkAction);
                        return $this->returnDataMesage(
                            400,
                            'PDF Watermark failed !',
                            null,
                            null,
                            'Invalid request action !'.',Current request: '.$watermarkAction
                        );
                    }
                    $tempPDF = $request->post('wmMosaic');
                    if ($tempPDF == 'true') {
                        $isMosaicDB = "true";
                        $isMosaic = true;
                    } else {
                        $isMosaicDB = "false";
                        $isMosaic = false;
                    }
                    $watermarkFontColorTemp = $request->post('wmFontColor');
                    if ($watermarkFontColorTemp == '') {
                        $watermarkFontColor = '#000000';
                    } else {
                        $watermarkFontColor = $watermarkFontColorTemp;
                    }
                    $watermarkFontFamily = $request->post('wmFontFamily');
                    $watermarkFontSizeTemp = $request->post('wmFontSize');
                    if ($watermarkFontSizeTemp == '') {
                        $watermarkFontSize = '12';
                    } else {
                        $watermarkFontSize = $watermarkFontSizeTemp;
                    }
                    $watermarkFontStyle = $request->post('wmFontStyle');
                    $watermarkLayout = $request->post('wmLayoutStyle');
                    $watermarkTempPage = $request->post('wmPage');
                    if (is_string($watermarkTempPage)) {
                        $watermarkPage = strtolower($watermarkTempPage);
                    } else {
                        $watermarkPage = $watermarkTempPage;
                    }
                    $watermarkRotation = $request->post('wmRotation');
                    $watermarkTransparency = $request->post('wmTransparency');
                    $watermarkText = $request->post('wmText');
                    try {
                        $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                        $ilovepdfTask = $ilovepdf->newTask('watermark');
                        if ($watermarkAction == 'img') {
                            $ilovepdfTask->setEncryption(true);
                            $wmImage = $ilovepdfTask->addElementFile(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$wmImageName));
                            $pdfFile = $ilovepdfTask->addFile($newFilePath);
                            $ilovepdfTask->setMode("image");
                            $ilovepdfTask->setImageFile($wmImage);
                            $ilovepdfTask->setTransparency($watermarkTransparency);
                            $ilovepdfTask->setRotation($watermarkRotation);
                            $ilovepdfTask->setLayer($watermarkLayout);
                            $ilovepdfTask->setPages($watermarkPage);
                            $ilovepdfTask->setMosaic($isMosaic);
                            $ilovepdfTask->setVerticalPosition("middle");
                        } else if ($watermarkAction == 'txt') {
                            $ilovepdfTask->setFileEncryption($pdfEncKey);
                            $ilovepdfTask->setEncryptKey($pdfEncKey);
                            $ilovepdfTask->setEncryption(true);
                            $pdfFile = $ilovepdfTask->addFile($newFilePath);
                            $ilovepdfTask->setMode("text");
                            $ilovepdfTask->setText($watermarkText);
                            $ilovepdfTask->setPages($watermarkPage);
                            $ilovepdfTask->setVerticalPosition("middle");
                            $ilovepdfTask->setRotation($watermarkRotation);
                            $ilovepdfTask->setFontColor($watermarkFontColor);
                            $ilovepdfTask->setFontFamily($watermarkFontFamily);
                            $ilovepdfTask->setFontStyle($watermarkFontStyle);
                            $ilovepdfTask->setFontSize($watermarkFontSize);
                            $ilovepdfTask->setTransparency($watermarkTransparency);
                            $ilovepdfTask->setLayer($watermarkLayout);
                            $ilovepdfTask->setMosaic($isMosaic);
                        }
                        $ilovepdfTask->setOutputFileName($randomizePdfFileName);
                        $ilovepdfTask->execute();
                        $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfDownload_Location));
                        $ilovepdfTask->delete();
                    }
                    catch (StartException $e) {
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $uuid,
                                'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                'errStatus' => $e->getMessage()
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'iLovePDF API Error !, Catch on StartException', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                400,
                                'PDF Watermark failed !',
                                $e->getMessage(),
                                null,
                                'iLovePDF API Error !, Catch on StartException'
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Database connection error !',$ex->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Eloquent transaction error !',
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
                                'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                'errStatus' => $e->getMessage()
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage(), true);
                            return $this->returnDataMesage(
                                400,
                                'PDF Watermark failed !',
                                $e->getMessage(),
                                null,
                                'iLovePDF API Error !, Catch on AuthException'
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Database connection error !',$ex->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Eloquent transaction error !',
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
                                'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                'errStatus' => $e->getMessage()
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                400,
                                'PDF Watermark failed !',
                                $e->getMessage(),
                                null,
                                'iLovePDF API Error !, Catch on UploadException'
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Database connection error !',$ex->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Eloquent transaction error !',
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
                                'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                'errStatus' => $e->getMessage()
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage(), true);
                            return $this->returnDataMesage(
                                400,
                                'PDF Watermark failed !',
                                $e->getMessage(),
                                null,
                                'iLovePDF API Error !, Catch on ProcessException'
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Database connection error !',$ex->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Eloquent transaction error !',
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
                                'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                'errStatus' => $e->getMessage()
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage(), true);
                            return $this->returnDataMesage(
                                400,
                                'PDF Watermark failed !',
                                $e->getMessage(),
                                null,
                                'iLovePDF API Error !, Catch on DownloadException'
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Database connection error !',$ex->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Eloquent transaction error !',
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
                                'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                'errStatus' => $e->getMessage()
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage(), true);
                            return $this->returnDataMesage(
                                400,
                                'PDF Watermark failed !',
                                $e->getMessage(),
                                null,
                                'iLovePDF API Error !, Catch on TaskException'
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Database connection error !',$ex->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Eloquent transaction error !',
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
                                'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                'errStatus' => $e->getMessage()
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'iLovePDF API Error !, Catch on PathException', $e->getMessage(), true);
                            return $this->returnDataMesage(
                                400,
                                'PDF Watermark failed !',
                                $e->getMessage(),
                                null,
                                'iLovePDF API Error !, Catch on PathException'
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Database connection error !',$ex->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Eloquent transaction error !',
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
                                'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                'errStatus' => $e->getMessage()
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $currentFileName,
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'iLovePDF API Error !, Catch on Exception', $e->getMessage(), true);
                            return $this->returnDataMesage(
                                400,
                                'PDF Watermark failed !',
                                $e->getMessage(),
                                null,
                                'iLovePDF API Error !, Catch on Exception'
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Database connection error !',$ex->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Eloquent transaction error !',
                                null,
                                null,
                                $e->getMessage()
                            );
                        }
                    }
                    if (file_exists(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'))) {
                        $procFileSize = filesize(Storage::disk('local')->path('public/'.$pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'));
                        $newProcFileSize = AppHelper::instance()->convert($procFileSize, "MB");
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $uuid,
                                'errReason' => null,
                                'errStatus' => null
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $randomizePdfFileName.'.pdf',
                                'fileSize' => $newProcFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => true,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            return $this->returnCoreMessage(
                                200,
                                'OK',
                                $randomizePdfFileName.'.pdf',
                                Storage::disk('local')->url($pdfDownload_Location.'/'.$randomizePdfFileName.'.pdf'),
                                'watermark',
                                $uuid,
                                $newFileSize,
                                null,
                                null,
                                null
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.pdf', $procFileSize, $uuid, 'FAIL', 'watermark', 'Database connection error !',$ex->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.pdf', $procFileSize, $uuid, 'FAIL', 'watermark', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Eloquent transaction error !',
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
                                'processId' => $uuid,
                                'errReason' => 'Failed to download file from iLovePDF API !',
                                'errStatus' => null
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $newFileNameWithoutExtension.'.pdf',
                                'fileSize' => $newFileSize,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkTransparency,
                                'watermarkImage' => $wmImageName,
                                'watermarkLayout' => $watermarkLayout,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkAction,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => false,
                                'isBatch' => $batchValue,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            return $this->returnDataMesage(
                                400,
                                'PDF Watermark failed !',
                                null,
                                null,
                                'Failed to download file from iLovePDF API !'
                            );
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Database connection error !',$ex->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Database connection error !',
                                null,
                                null,
                                $ex->getMessage()
                            );
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Eloquent transaction error !', $e->getMessage(), false);
                            return $this->returnDataMesage(
                                500,
                                'Eloquent transaction error !',
                                null,
                                null,
                                $e->getMessage()
                            );
                        }
                    }
                }
            } else {
                try {
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => 'PDF failed to upload !',
                        'errStatus' => null
                    ]);
                    DB::table('pdfWatermark')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'watermarkFontFamily' => null,
                        'watermarkFontStyle' => null,
                        'watermarkFontSize' => null,
                        'watermarkFontTransparency' => null,
                        'watermarkImage' => null,
                        'watermarkLayout' => null,
                        'watermarkMosaic' => null,
                        'watermarkRotation' => null,
                        'watermarkStyle' => null,
                        'watermarkText' => null,
                        'watermarkPage' => null,
                        'result' => false,
                        'isBatch' => null,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'watermark', 'PDF failed to upload !', null, true);
                    return $this->returnDataMesage(
                        400,
                        'PDF Watermark failed !',
                        null,
                        null,
                        'PDF failed to upload !'
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Database connection error !',$ex->getMessage(), false);
                    return $this->returnDataMesage(
                        500,
                        'Database connection error !',
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($currentFileName, $fileSize, $uuid, 'FAIL', 'watermark', 'Eloquent transaction error !', $e->getMessage(), false);
                    return $this->returnDataMesage(
                        500,
                        'Eloquent transaction error !',
                        null,
                        null,
                        $e->getMessage()
                    );
                }
            }
        }
    }
}
