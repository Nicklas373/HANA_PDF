<?php

namespace App\Http\Controllers\proc;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Aspose\Words\WordsApi;
use Aspose\Words\Model\Requests\{SaveAsRequest, UploadFileRequest};
use Aspose\Words\Model\{DocxSaveOptionsData};
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
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

class convertController extends Controller
{
	public function convert(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf,pptx,docx,xlsx,jpg,png,jpeg,tiff|max:25600',
			'fileAlt' => ''
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
                    'errApiReason' => null
                ]);
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','PDF Conversion failed !',$validator->messages());
                return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','Database connection error !',$ex->messages());
                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
            }
		} else {
            $start = Carbon::parse($startProc);
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
                        $str = rand(1000,10000000);
						$pdfUpload_Location = env('PDF_UPLOAD');
						$file = $request->file('file');
                        $randomizePdfFileName = 'pdf_convert_'.substr(md5(uniqid($str)), 0, 8);
                        $randomizePdfExtension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                        $randomizePdfPath = $pdfUpload_Location.'/'.$randomizePdfFileName.'.'.$randomizePdfExtension;
						$pdfFileName = $file->getClientOriginalName();
                        $fileSize = filesize($file);
                        $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        $file->storeAs('public/upload-pdf', $randomizePdfFileName.'.'.$randomizePdfExtension);
						if (Storage::disk('local')->exists('public/'.$randomizePdfPath)) {
							return redirect()->back()->with([
                                'status' => true,
                                'pdfRndmName' => Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$randomizePdfFileName.'.'.$randomizePdfExtension),
                                'pdfOriName' => $pdfFileName,
                            ]);
						} else {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfConvert')->insert([
                                    'fileName' => $randomizePdfFileName.'.pdf',
                                    'fileSize' => $newFileSize,
                                    'container' => null,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'processId' => $uuid,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $uuid)
                                    ->update([
                                        'processId' => $uuid,
                                        'errReason' => 'PDF file not found on the server !',
                                        'errApiReason' => null
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'PDF file not found on the server !', 'null');
                                return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.pdf', $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                            }
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
                            DB::table('pdfConvert')->insert([
                                'fileName' => null,
                                'fileSize' => null,
                                'container' => null,
                                'imgExtract' => false,
                                'result' => false,
                                'processId' => $uuid,
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
                            NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL', 'PDF failed to upload !', 'null');
                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                        }
					}
				} else if ($request->post('formAction') == "convert") {
                    if(isset($_POST['convertType']))
                    {
                        $convertType = $request->post('convertType');
                        if ($convertType == 'excel') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = env('PDF_UPLOAD');
                                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                                $file = $request->post('fileAlt');
                                $pdfName = basename($file);
                                $pdfNameWithoutExtension = basename($file, ".pdf");
                                $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
						        $fileSize = filesize($pdfNewPath);
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                                $asposeAPI = new Process([
                                                'python',
                                                public_path().'/ext-python/asposeAPI.py',
                                                env('ASPOSE_CLOUD_CLIENT_ID'), env('ASPOSE_CLOUD_TOKEN'),
                                                "xlsx"
                                                ,$file,
                                                $pdfNameWithoutExtension.".xlsx"
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
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => false,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'PDF Conversion running out of time !',
                                                'errApiReason' => $message->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'PDF Conversion running out of time !', $message->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (ProcessFailedException $message) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => false,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'Symfony runtime process fail exception !',
                                                'errApiReason' => $message->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Symfony runtime process fail exception !', $message->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                                if (!$asposeAPI->isSuccessful()) {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xslx'), $pdfNameWithoutExtension.".xlsx") == true) {
                                        $download_xlsx = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xlsx');
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        try {
                                            DB::table('appLogs')->insert([
                                                'processId' => $uuid,
                                                'errReason' => null,
                                                'errApiReason' => null
                                            ]);
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => true,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => null,
                                                    'errApiReason' => null
                                            ]);
                                            return redirect()->back()->with(["stats" => "scs", "res"=>$download_xlsx]);
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'Converted file not found on the server !',
                                                    'errApiReason' => $asposeAPI->getOutput(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Converted file not found on the server !', $asposeAPI->getOutput());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
                                } else {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xlsx'), $pdfNameWithoutExtension.".xlsx") == true) {
                                        $download_excel = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xlsx');
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        try {
                                            DB::table('appLogs')->insert([
                                                'processId' => $uuid,
                                                'errReason' => null,
                                                'errApiReason' => null
                                            ]);
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => true,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => null,
                                                    'errApiReason' => null
                                            ]);
                                            return redirect()->back()->with(["stats" => "scs", "res"=>$download_xlsx]);
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'Converted file not found on the server !',
                                                    'errApiReason' => $asposeAPI->getOutput()
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Converted file not found on the server !', $asposeAPI->getOutput());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
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
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => null,
                                        'fileSize' => null,
                                        'container' => null,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'processId' => $uuid,
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
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'PDF failed to upload !', 'null');
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                }
                            }
                        } else if ($convertType == 'pptx') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = env('PDF_UPLOAD');
                                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                                $file = $request->post('fileAlt');
                                $pdfName = basename($file);
                                $pdfNameWithoutExtension = basename($file, ".pdf");
                                $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
						        $fileSize = filesize($pdfNewPath);
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                                $asposeAPI = new Process([
                                                'python',
                                                public_path().'/ext-python/asposeAPI.py',
                                                env('ASPOSE_CLOUD_CLIENT_ID'), env('ASPOSE_CLOUD_TOKEN'),
                                                "pptx"
                                                ,$file,
                                                $pdfNameWithoutExtension.".pptx"
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
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfNameWithoutExtension.'.pdf',
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => false,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'Symfony runtime process out of time exception !',
                                                'errApiReason' => $message->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'PDF failed to upload !', $message->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (ProcessFailedException $message) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => false,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'Symfony runtime process fail exception !',
                                                'errApiReason' => $message->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Symfony runtime process fail exception !', $message->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                                if (!$asposeAPI->isSuccessful()) {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pptx'), $pdfNameWithoutExtension.".pptx") == true) {
                                        $download_pptx = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pptx');
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        try {
                                            DB::table('appLogs')->insert([
                                                'processId' => $uuid,
                                                'errReason' => null,
                                                'errApiReason' => null
                                            ]);
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => true,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => null,
                                                    'errApiReason' => null
                                            ]);
                                            return redirect()->back()->with(["stats" => "scs", "res"=>$download_pptx]);
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'Converted file not found on the server !',
                                                    'errApiReason' => $asposeAPI->getOutput(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Converted file not found on the server !', $asposeAPI->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
                                } else {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pptx'), $pdfNameWithoutExtension.".pptx") == true) {
                                        $download_pptx = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pptx');
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        try {
                                            DB::table('appLogs')->insert([
                                                'processId' => $uuid,
                                                'errReason' => null,
                                                'errApiReason' => null
                                            ]);
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => true,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => null,
                                                    'errApiReason' => null
                                            ]);
                                            return redirect()->back()->with(["stats" => "scs", "res"=>$download_pptx]);
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'Converted file not found on the server !',
                                                    'errApiReason' => null,
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Converted file not found on the server !', 'null');
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
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
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => null,
                                        'fileSize' => null,
                                        'container' => null,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'processId' => $uuid,
                                        'procStartAt' => $startProc,
                                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                        'procDuration' =>  $duration->s.' seconds'
                                    ]);
                                    DB::table('appLogs')
                                        ->where('processId', '=', $uuid)
                                        ->update([
                                            'processId' => $uuid,
                                            'errReason' => 'PDF failed to upload !',
                                            'errApiReason' => null,
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'PDF failed to upload !', 'null');
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                }
                            }
                        } else if ($convertType == 'docx') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = env('PDF_UPLOAD');
                                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                                $file = $request->post('fileAlt');
                                $pdfName = basename($file);
				                $pdfNameInfo = pathinfo($file);
                                $pdfNameWithoutExtension = $pdfNameInfo['filename'];
                                $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
						        $fileSize = filesize($pdfNewPath);
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                                try {
                                    $wordsApi = new WordsApi(env('ASPOSE_CLOUD_CLIENT_ID'), env('ASPOSE_CLOUD_TOKEN'));
                                    $uploadFileRequest = new UploadFileRequest($pdfNewPath, $pdfName);
                                    $wordsApi->uploadFile($uploadFileRequest);
                                    $requestSaveOptionsData = new DocxSaveOptionsData(array(
                                        "save_format" => "docx",
                                        "file_name" => $pdfNameWithoutExtension.".docx",
                                    ));
                                    $request = new SaveAsRequest(
                                        $pdfName,
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
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => false,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'Aspose PDF API Error !',
                                                'errApiReason' => $e->getMessage()
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Aspose PDF API Error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                                if (file_exists($pdfNewPath)) {
                                    unlink($pdfNewPath);
                                }
                                if (json_decode($result, true) !== NULL) {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.".docx"), $pdfNameWithoutExtension.".docx") == true) {
                                        $download_word = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.".docx");
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        try {
                                            DB::table('appLogs')->insert([
                                                'processId' => $uuid,
                                                'errReason' => null,
                                                'errApiReason' => null
                                            ]);
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => true,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => null,
                                                    'errApiReason' => null
                                            ]);
                                            return redirect()->back()->with(["stats" => "scs", "res"=>$download_word]);
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'FTP Server Connection Failed !',
                                                    'errApiReason' => null
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Aspose PDF API Error !', 'null');
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                        }
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
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => false,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'Aspose Clouds API has fail while process, Please look on Aspose Dashboard !',
                                                'errApiReason' => null
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Aspose Clouds API has fail while process, Please look on Aspose Dashboard !', 'null');
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
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
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => null,
                                        'fileSize' => null,
                                        'container' => null,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'processId' => $uuid,
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
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'PDF failed to upload !', 'null');
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                }
                            }
                        } else if ($convertType == 'jpg') {
                            if(isset($_POST['fileAlt'])) {
                                if(isset($_POST['extImage']))
                                {
                                    $extImage = $request->post('extImage');
                                    if ($extImage) {
                                        $imageModes = 'extract';
                                        $extMode = true;
                                    } else {
                                        $imageModes = 'pages';
                                        $extMode = false;
                                    }
                                } else {
                                    $imageModes = 'pages';
                                    $extMode = false;
                                }
                                $pdfUpload_Location = env('PDF_UPLOAD');
                                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                                $pdfExtImage_Location = env('ILOVEPDF_EXT_IMG_DIR');
                                $pdfEncKey = bin2hex(random_bytes(16));
                                $file = $request->post('fileAlt');
                                $pdfName = basename($file);
                                $pdfNameWithoutExtension = basename($pdfName, ".pdf");
                                $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
                                $pdfTotalPages = AppHelper::instance()->count($pdfNewPath);
						        $fileSize = filesize($pdfNewPath);
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                                if ($pdfTotalPages == 1 && $extMode) {
                                    $files = glob(Storage::disk('local')->path('public/'.$pdfExtImage_Location).'/*');
                                    foreach($files as $file) {
                                        if (is_file($file)){
                                            unlink($file);
                                        }
                                    }
                                }
                                try {
                                    $ilovepdfTask = new PdfjpgTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setFileEncryption($pdfEncKey);
                                    $ilovepdfTask->setEncryptKey($pdfEncKey);
                                    $ilovepdfTask->setEncryption(true);
                                    $pdfFile = $ilovepdfTask->addFile($pdfNewPath);
                                    $ilovepdfTask->setMode($imageModes);
                                    $ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
                                    $ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
                                    $ilovepdfTask->execute();
                                    if ($pdfTotalPages == 1 && $extMode) {
                                        $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfExtImage_Location));
                                    } else {
                                        $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
                                    }
                                } catch (StartException $e) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => $extMode,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                                'errApiReason' => $e->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => $extMode,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                                'errApiReason' => $e->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => $extMode,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                                'errApiReason' => $e->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => $extMode,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                                'errApiReason' => $e->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => $extMode,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                                'errApiReason' => $e->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => $extMode,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                                'errApiReason' => $e->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => $extMode,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                                'errApiReason' => $e->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => $extMode,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                                'errApiReason' => $e->getMessage(),
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                                if ($pdfTotalPages == 1 && $extMode) {
                                    foreach (glob(Storage::disk('local')->path('public/'.$pdfExtImage_Location).'/*.jpg') as $filename) {
                                        rename($filename, Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.jpg'));
                                    }
                                }
                                if (file_exists($pdfNewPath)) {
                                    unlink($pdfNewPath);
                                }
                                if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip'))) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => $extMode,
                                            'result' => true,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => null,
                                                'errApiReason' => null
                                        ]);
                                        return redirect()->back()->with([
                                            "stats" => "scs",
                                            "res"=>Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip')
                                        ]);
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } else {
                                    if ($pdfTotalPages = 1 && $extMode) {
                                        if (Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.jpg')) {
                                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                            $duration = $end->diff($startProc);
                                            try {
                                                DB::table('appLogs')->insert([
                                                    'processId' => $uuid,
                                                    'errReason' => null,
                                                    'errApiReason' => null
                                                ]);
                                                DB::table('pdfConvert')->insert([
                                                    'fileName' => $pdfName,
                                                    'fileSize' => $newFileSize,
                                                    'container' => $convertType,
                                                    'imgExtract' => $extMode,
                                                    'result' => true,
                                                    'processId' => $uuid,
                                                    'procStartAt' => $startProc,
                                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                    'procDuration' =>  $duration->s.' seconds'
                                                ]);
                                                DB::table('appLogs')
                                                    ->where('processId', '=', $uuid)
                                                    ->update([
                                                        'processId' => $uuid,
                                                        'errReason' => null,
                                                        'errApiReason' => null
                                                ]);
                                                return redirect()->back()->with([
                                                    "stats" => "scs",
                                                    "res"=>Storage::disk('local')->url('temp/'.$pdfNameWithoutExtension.'.jpg'),
                                                    'processId'=>$uuid
                                                ])->withInput();
                                            } catch (QueryException $ex) {
                                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                            } catch (\Exception $e) {
                                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                                return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                            }
                                        }
                                    } else if ($pdfTotalPages = 1 && !$extMode) {
                                        if (Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'-0001.jpg')) {
                                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                            $duration = $end->diff($startProc);
                                            try {
                                                DB::table('appLogs')->insert([
                                                    'processId' => $uuid,
                                                    'errReason' => null,
                                                    'errApiReason' => null
                                                ]);
                                                DB::table('pdfConvert')->insert([
                                                    'fileName' => $pdfName,
                                                    'fileSize' => $newFileSize,
                                                    'container' => $convertType,
                                                    'imgExtract' => $extMode,
                                                    'result' => true,
                                                    'processId' => $uuid,
                                                    'procStartAt' => $startProc,
                                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                    'procDuration' =>  $duration->s.' seconds'
                                                ]);
                                                DB::table('appLogs')
                                                    ->where('processId', '=', $uuid)
                                                    ->update([
                                                        'processId' => $uuid,
                                                        'errReason' => null,
                                                        'errApiReason' => null
                                                ]);
                                                return redirect()->back()->with([
                                                    "stats" => "scs",
                                                    "res"=>Storage::disk('local')->url('temp/'.$pdfNameWithoutExtension.'-0001.jpg'),
                                                    'processId'=>$uuid
                                                ])->withInput();
                                            } catch (QueryException $ex) {
                                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                            } catch (\Exception $e) {
                                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                                return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                            }
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $fileSize,
                                                'container' => $convertType,
                                                'imgExtract' => $extMode,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'Failed to download converted file from iLovePDF API !',
                                                    'errApiReason' => null
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Failed to download converted file from iLovePDF API !', 'null');
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
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
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => null,
                                        'fileSize' => null,
                                        'container' => null,
                                        'imgExtract' => null,
                                        'result' => false,
                                        'processId' => $uuid,
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
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'PDF failed to upload !', 'null');
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                }
                            }
                        } else if ($convertType == 'pdf') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = env('PDF_UPLOAD');
                                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                                $pdfEncKey = bin2hex(random_bytes(16));
                                $file = $request->post('fileAlt');
                                $pdfName = basename($file);
                                $pdfNameWithExtension = pathinfo($pdfName, PATHINFO_EXTENSION);
                                $pdfNameWithoutExtension = pathinfo($pdfName, PATHINFO_FILENAME);
                                $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
						        $fileSize = filesize($pdfNewPath);
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                                if ($pdfNameWithExtension == "jpg" || $pdfNameWithExtension == "jpeg" || $pdfNameWithExtension == "png" || $pdfNameWithExtension == "tiff") {
                                    try {
                                        $ilovepdfTask = new ImagepdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                        $ilovepdfTask->setFileEncryption($pdfEncKey);
                                        $ilovepdfTask->setEncryptKey($pdfEncKey);
                                        $ilovepdfTask->setEncryption(true);
                                        $pdfFile = $ilovepdfTask->addFile($pdfNewPath);
                                        $ilovepdfTask->setPageSize('fit');
                                        $ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
                                        $ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
                                        $ilovepdfTask->execute();
                                        $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
                                    } catch (StartException $e) {
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        try {
                                            DB::table('appLogs')->insert([
                                                'processId' => $uuid,
                                                'errReason' => null,
                                                'errApiReason' => null
                                            ]);
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
                                } else {
                                    try {
                                        $ilovepdfTask = new OfficepdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                        $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                                        $pdfFile = $ilovepdfTask->addFile($pdfNewPath);
                                        $ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
                                        $ilovepdfTask->execute();
                                        $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
                                    } catch (StartException $e) {
                                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                        $duration = $end->diff($startProc);
                                        try {
                                            DB::table('appLogs')->insert([
                                                'processId' => $uuid,
                                                'errReason' => null,
                                                'errApiReason' => null
                                            ]);
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on StartException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $fileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on PathException',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                            DB::table('pdfConvert')->insert([
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'imgExtract' => false,
                                                'result' => false,
                                                'processId' => $uuid,
                                                'procStartAt' => $startProc,
                                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                                'procDuration' =>  $duration->s.' seconds'
                                            ]);
                                            DB::table('appLogs')
                                                ->where('processId', '=', $uuid)
                                                ->update([
                                                    'processId' => $uuid,
                                                    'errReason' => 'iLovePDF API Error !, Catch on Exception',
                                                    'errApiReason' => $e->getMessage(),
                                            ]);
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                        } catch (\Exception $e) {
                                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
                                }
                                if (file_exists($pdfNewPath)) {
                                    unlink($pdfNewPath);
                                }
                                if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pdf'))) {
                                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => false,
                                            'result' => true,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => null,
                                                'errApiReason' => null
                                        ]);
                                        return redirect()->back()->with([
                                            "stats" => "scs",
                                            "res"=>Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pdf')
                                        ]);
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
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
                                        DB::table('pdfConvert')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'imgExtract' => false,
                                            'result' => false,
                                            'processId' => $uuid,
                                            'procStartAt' => $startProc,
                                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                            'procDuration' =>  $duration->s.' seconds'
                                        ]);
                                        DB::table('appLogs')
                                            ->where('processId', '=', $uuid)
                                            ->update([
                                                'processId' => $uuid,
                                                'errReason' => 'Failed to download converted file from iLovePDF API !',
                                                'errApiReason' => null
                                        ]);
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Failed to download converted file from iLovePDF API !', 'null');
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                    } catch (\Exception $e) {
                                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
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
                                    DB::table('pdfConvert')->insert([
                                        'fileName' => null,
                                        'fileSize' => null,
                                        'container' => null,
                                        'imgExtract' => false,
                                        'result' => false,
                                        'processId' => $uuid,
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
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'PDF failed to upload !', 'null');
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                }
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
                                DB::table('pdfConvert')->insert([
                                    'fileName' => null,
                                    'fileSize' => null,
                                    'container' => null,
                                    'imgExtract' => false,
                                    'result' => false,
                                    'processId' => $uuid,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                DB::table('appLogs')
                                    ->where('processId', '=', $uuid)
                                    ->update([
                                        'processId' => $uuid,
                                        'errReason' => 'REQUEST_ERROR_OUT_OF_BOUND !',
                                        'errApiReason' => null
                                ]);
                                NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'REQUEST_ERROR_OUT_OF_BOUND !', 'null');
                                return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                            }
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
                            DB::table('pdfConvert')->insert([
                                'fileName' => null,
                                'fileSize' => null,
                                'container' => null,
                                'imgExtract' => false,
                                'result' => false,
                                'processId' => $uuid,
                                'procStartAt' => $startProc,
                                'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                'procDuration' =>  $duration->s.' seconds'
                            ]);
                            DB::table('appLogs')
                                ->where('processId', '=', $uuid)
                                ->update([
                                    'processId' => $uuid,
                                    'errReason' => 'REQUEST_TYPE_NOT_FOUND !',
                                    'errApiReason' => null
                            ]);
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'REQUEST_TYPE_NOT_FOUND !', 'null');
                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                        }
                    }
				} else {
                    try {
                        DB::table('pdf_convert')->insert([
                            'processId' => $uuid,
                            'fileName' => null,
                            'fileSize' => null,
                            'container' => null,
                            'imgExtract' => false,
                            'result' => false,
                            'err_reason' => '000',
                            'err_api_reason' => null,
                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                        ]);
                        NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', '000', 'null');
                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                    }
				}
			} else {
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfConvert')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'container' => null,
                        'imgExtract' => false,
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => '0x0',
                            'errApiReason' => $e->getMessage(),
                    ]);
                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', '0x0', 'null');
                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
			}
		}
	}
}
