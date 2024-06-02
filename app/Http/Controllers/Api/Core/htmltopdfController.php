<?php

namespace App\Http\Controllers\Api\Core;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\HtmlpdfTask;
use Ilovepdf\Exceptions\StartException;
use Ilovepdf\Exceptions\AuthException;
use Ilovepdf\Exceptions\UploadException;
use Ilovepdf\Exceptions\ProcessException;
use Ilovepdf\Exceptions\DownloadException;
use Ilovepdf\Exceptions\TaskException;
use Ilovepdf\Exceptions\PathException;

class htmltopdfController extends Controller
{
    public function html(Request $request) {
        $validator = Validator::make($request->all(),[
		    'urlToPDF' => 'required',
            'urlMarginValue' => ['required', 'numeric'],
            'urlSizeValue' => ['required', 'in:A3,A4,A5,Letter'],
            'urlPageOrientationValue' => ['required', 'in:landscape,portrait'],
            'urlSinglePage' => ['required', 'in:true,false']
	    ]);

        $uuid = AppHelper::Instance()->get_guid();

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

        if($validator->fails()) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => $validator->messages(),
                    'errStatus' => null
                ]);
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL', 'htmltopdf', 'Failed to convert HTML to PDF !',$validator->messages());
                return $this->returnCoreMessage(
                    200,
                    'Failed to convert HTML to PDF !',
                    null,
                    null,
                    'htmltopdf',
                    $uuid,
                    null,
                    null,
                    null,
                    $validator->errors()->all()
                );
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL', 'htmltopdf', 'Database connection error !',$ex->getMessage());
                return $this->returnCoreMessage(
                    200,
                    'Database connection error !',
                    null,
                    null,
                    'htmltopdf',
                    $uuid,
                    null,
                    null,
                    null,
                    $ex->getMessage()
                );
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                return $this->returnCoreMessage(
                    200,
                    'Eloquent transaction error !',
                    null,
                    null,
                    'htmltopdf',
                    $uuid,
                    null,
                    null,
                    null,
                    $e->getMessage()
                );
            }
        } else {
            $start = Carbon::parse($startProc);
            $str = rand(1000,10000000);
            $pdfEncKey = bin2hex(random_bytes(16));
            $pdfDefaultFileName ='pdf_convert_'.substr(md5(uniqid($str)), 0, 8);
            $pdfProcessed_Location = env('PDF_DOWNLOAD');
            $pdfUpload_Location = env('PDF_UPLOAD');
            $pdfUrl = $request->post('urlToPDF');
            $pdfOrientation = $request->post('urlPageOrientationValue');
            $pdfMargin = $request->post('urlMarginValue');
            $pdfSize = $request->post('urlSizeValue');
            $pdfSinglePage = $request->post('urlSinglePage');
            $newUrl = '';
            if (AppHelper::Instance()->checkWebAvailable($pdfUrl)) {
                $newUrl = $pdfUrl;
            } else {
                if (AppHelper::Instance()->checkWebAvailable('https://'.$pdfUrl)) {
                    $newUrl = 'https://'.$pdfUrl;
                } else if (AppHelper::Instance()->checkWebAvailable('http://'.$pdfUrl)) {
                    $newUrl = 'http://'.$pdfUrl;
                } else if (AppHelper::Instance()->checkWebAvailable('www.'.$pdfUrl)) {
                    $newUrl = 'www.'.$pdfUrl;
                } else {
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => '404',
                            'errStatus' => 'Webpage are not available or not valid'
                        ]);
                        DB::table('pdfHtml')->insert([
                            'urlName' => $request->post('urlToPDF'),
                            'urlMargin' => $pdfMargin,
                            'urlOrientation' => $pdfOrientation,
                            'urlSinglePage' => $pdfSinglePage,
                            'urlSize' => $pdfSize,
                            'result' => false,
                            'processId' => $uuid,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'HTML To PDF Conversion Failed !', 'Webpage are not available or not valid');
                        return $this->returnCoreMessage(
                            200,
                            'HTML To PDF Conversion Failed !',
                            $pdfUrl,
                            null,
                            'htmltopdf',
                            $uuid,
                            null,
                            null,
                            null,
                            'Webpage are not available or not valid'
                        );
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Database connection error !', $ex->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Database connection error !',
                            $pdfUrl,
                            null,
                            'htmltopdf',
                            $uuid,
                            null,
                            null,
                            null,
                            $ex->getMessage()
                        );
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                        return $this->returnCoreMessage(
                            200,
                            'Eloquent transaction error !',
                            $pdfUrl,
                            null,
                            'htmltopdf',
                            $uuid,
                            null,
                            null,
                            null,
                            $e->getMessage()
                        );
                    }
                }
            }
            try {
                $ilovepdfTask = new HtmlpdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                $ilovepdfTask->setEncryptKey($pdfEncKey);
                $ilovepdfTask->setEncryption(true);
                $pdfFile = $ilovepdfTask->addUrl($newUrl);
                $ilovepdfTask->setPageOrientation($pdfOrientation);
                $ilovepdfTask->setPageMargin($pdfMargin);
                $ilovepdfTask->setPageSize($pdfSize);
                if ($pdfSinglePage == 'true') {
                    $ilovepdfTask->setSinglePage(true);
                } else {
                    $ilovepdfTask->setSinglePage(false);
                }
                $ilovepdfTask->setOutputFileName($pdfDefaultFileName);
                $ilovepdfTask->execute();
                $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
            } catch (StartException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => 'iLovePDF API Error !, Catch on StartException',
                        'errStatus' => $e->getMessage()
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'urlMargin' => $pdfMargin,
                        'urlOrientation' => $pdfOrientation,
                        'urlSinglePage' => $pdfSinglePage,
                        'urlSize' => $pdfSize,
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Failed to convert HTML to PDF !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Database connection error !', $ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
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
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'urlMargin' => $pdfMargin,
                        'urlOrientation' => $pdfOrientation,
                        'urlSinglePage' => $pdfSinglePage,
                        'urlSize' => $pdfSize,
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Failed to convert HTML to PDF !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Database connection error !', $ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
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
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'urlMargin' => $pdfMargin,
                        'urlOrientation' => $pdfOrientation,
                        'urlSinglePage' => $pdfSinglePage,
                        'urlSize' => $pdfSize,
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Failed to convert HTML to PDF !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Database connection error !', $ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
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
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'urlMargin' => $pdfMargin,
                        'urlOrientation' => $pdfOrientation,
                        'urlSinglePage' => $pdfSinglePage,
                        'urlSize' => $pdfSize,
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Failed to convert HTML to PDF !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Database connection error !', $ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
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
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'urlMargin' => $pdfMargin,
                        'urlOrientation' => $pdfOrientation,
                        'urlSinglePage' => $pdfSinglePage,
                        'urlSize' => $pdfSize,
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Failed to convert HTML to PDF !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Database connection error !', $ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
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
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'urlMargin' => $pdfMargin,
                        'urlOrientation' => $pdfOrientation,
                        'urlSinglePage' => $pdfSinglePage,
                        'urlSize' => $pdfSize,
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Failed to convert HTML to PDF !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Database connection error !', $ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
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
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'urlMargin' => $pdfMargin,
                        'urlOrientation' => $pdfOrientation,
                        'urlSinglePage' => $pdfSinglePage,
                        'urlSize' => $pdfSize,
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Failed to convert HTML to PDF !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Database connection error !', $ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
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
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'urlMargin' => $pdfMargin,
                        'urlOrientation' => $pdfOrientation,
                        'urlSinglePage' => $pdfSinglePage,
                        'urlSize' => $pdfSize,
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Failed to convert HTML to PDF !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Database connection error !', $ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $e->getMessage()
                    );
                }
            }
            if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfDefaultFileName.'.pdf'))) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errStatus' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'urlMargin' => $pdfMargin,
                        'urlOrientation' => $pdfOrientation,
                        'urlSinglePage' => $pdfSinglePage,
                        'urlSize' => $pdfSize,
                        'result' => true,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    return $this->returnCoreMessage(
                        200,
                        'OK',
                        $pdfUrl,
                        Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfDefaultFileName.'.pdf'),
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        null
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Database connection error !', $ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
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
                        'errReason' => 'Failed to download converted file from iLovePDF API !',
                        'errStatus' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'urlMargin' => $pdfMargin,
                        'urlOrientation' => $pdfOrientation,
                        'urlSinglePage' => $pdfSinglePage,
                        'urlSize' => $pdfSize,
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    NotificationHelper::Instance()->sendErrNotify(null, null, $uuid, 'FAIL', 'HTML To PDF Conversion Failed !', null);
                    return $this->returnCoreMessage(
                        200,
                        'HTML To PDF Conversion Failed !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        null
                    );
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Database connection error !', $ex->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Database connection error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
                        $uuid,
                        null,
                        null,
                        null,
                        $ex->getMessage()
                    );
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($pdfUrl, null, $uuid, 'FAIL', 'htmltopdf', 'Eloquent transaction error !', $e->getMessage());
                    return $this->returnCoreMessage(
                        200,
                        'Eloquent transaction error !',
                        $pdfUrl,
                        null,
                        'htmltopdf',
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
