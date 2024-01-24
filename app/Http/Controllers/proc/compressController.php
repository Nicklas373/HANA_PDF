<?php

namespace App\Http\Controllers\proc;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\Exceptions\StartException;
use Ilovepdf\Exceptions\AuthException;
use Ilovepdf\Exceptions\UploadException;
use Ilovepdf\Exceptions\ProcessException;
use Ilovepdf\Exceptions\DownloadException;
use Ilovepdf\Exceptions\TaskException;
use Ilovepdf\Exceptions\PathException;

class compressController extends Controller
{
 	public function compress(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25600',
			'fileAlt' => ''
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
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','PDF Compression failed !',$validator->messages());
                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','Database connection error !',$ex->getMessage());
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
                        $randomizePdfFileName = 'pdfCompress_'.substr(md5(uniqid($str)), 0, 8);
                        $randomizePdfPath = $pdfUpload_Location.'/'.$randomizePdfFileName.'.pdf';
						$pdfFileName = $file->getClientOriginalName();
                        $fileSize = filesize($file);
                        $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        $file->storeAs('public/upload-pdf', $randomizePdfFileName.'.pdf');
						if (Storage::disk('local')->exists('public/'.$randomizePdfPath)) {
							return redirect()->back()->with([
                                'status' => true,
                                'pdfRndmName' => Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$randomizePdfFileName.'.pdf'),
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
                                DB::table('pdfCompress')->insert([
                                    'fileName' => $randomizePdfFileName.'.pdf',
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => null,
                                    'compMethod' => null,
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
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
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
                            DB::table('appLogs')
                                ->where('processId', '=', $uuid)
                                ->update([
                                    'processId' => $uuid,
                                    'errReason' => 'PDF failed to upload !',
                                    'errApiReason' => null
                            ]);
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'PDF failed to upload !', 'null');
                            return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                        }
					}
				} else if ($request->post('formAction') == "compress") {
					if(isset($_POST['fileAlt'])) {
						if(isset($_POST['compMethod']))
						{
							$compMethod = $request->post('compMethod');
						} else {
							$compMethod = "recommended";
						}
						$file = $request->post('fileAlt');
                        $pdfUpload_Location = env('PDF_UPLOAD');
                        $pdfProcessed_Location = env('PDF_DOWNLOAD');
                        $pdfEncKey = bin2hex(random_bytes(16));
						$pdfName = basename($file);
                        $pdfNameWithoutExtension = basename($pdfName, '.pdf');
                        $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
						$fileSize = filesize($pdfNewPath);
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        try {
                            $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                            $ilovepdfTask = $ilovepdf->newTask('compress');
                            $ilovepdfTask->setFileEncryption($pdfEncKey);
                            $ilovepdfTask->setEncryptKey($pdfEncKey);
                            $ilovepdfTask->setEncryption(true);
                            $pdfFile = $ilovepdfTask->addFile($pdfNewPath);
                            $pdfFile->setPassword($pdfEncKey);
                            $ilovepdfTask->setCompressionLevel($compMethod);
                            $ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
                            $ilovepdfTask->execute();
                            $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
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
                                DB::table('pdfCompress')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $fileSize,
                                    'compFileSize' => null,
                                    'compMethod' => $compMethod,
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
                                        'errApiReason' => $e->getMessage()
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfCompress')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $fileSize,
                                    'compFileSize' => null,
                                    'compMethod' => $compMethod,
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
                                        'errApiReason' => $e->getMessage()
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfCompress')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $fileSize,
                                    'compFileSize' => null,
                                    'compMethod' => $compMethod,
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
                                        'errApiReason' => $e->getMessage()
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfCompress')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $fileSize,
                                    'compFileSize' => null,
                                    'compMethod' => $compMethod,
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
                                        'errApiReason' => $e->getMessage()
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfCompress')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $fileSize,
                                    'compFileSize' => null,
                                    'compMethod' => $compMethod,
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
                                        'errApiReason' => $e->getMessage()
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfCompress')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $fileSize,
                                    'compFileSize' => null,
                                    'compMethod' => $compMethod,
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
                                        'errApiReason' => $e->getMessage()
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfCompress')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $fileSize,
                                    'compFileSize' => null,
                                    'compMethod' => $compMethod,
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
                                        'errApiReason' => $e->getMessage()
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfCompress')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $fileSize,
                                    'compFileSize' => null,
                                    'compMethod' => $compMethod,
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
                                        'errApiReason' => $e->getMessage()
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                            }
                        }

                        if (file_exists($pdfNewPath)) {
                            unlink($pdfNewPath);
                        }

                        if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfName))) {
                            $compFileSize = filesize(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfName));
                            $newCompFileSize = AppHelper::instance()->convert($compFileSize, "MB");
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfCompress')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => $newCompFileSize,
                                    'compMethod' => $compMethod,
                                    'result' => true,
                                    'processId' => $uuid,
                                    'procStartAt' => $startProc,
                                    'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                                    'procDuration' =>  $duration->s.' seconds'
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res" => Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfName),
                                    "curFileSize" => $newFileSize,
                                    "newFileSize" => $newCompFileSize,
                                    "compMethod" => $compMethod
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                            }
                        } else {
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfCompress')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => null,
                                    'compMethod' => $compMethod,
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
                                        'errReason' => 'Failed to download file from iLovePDF API !',
                                        'errApiReason' => null
                                ]);
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Failed to download file from iLovePDF API !', 'null');
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
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
                            DB::table('appLogs')->insert([
                                'processId' => $uuid,
                                'errReason' => null,
                                'errApiReason' => null
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
                            DB::table('appLogs')
                                ->where('processId', '=', $uuid)
                                ->update([
                                    'processId' => $uuid,
                                    'errReason' => 'PDF failed to upload !',
                                    'errApiReason' => null
                            ]);
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'PDF failed to upload !', 'null');
                            return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
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
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'processId' => $uuid,
                                'errReason' => 'PDF process unknown error !',
                                'errApiReason' => null
                        ]);
                        NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'PDF process unknown error !', 'null');
                        return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
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
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'OUT_OF_BOUND_ERROR !',
                            'errApiReason' => null
                    ]);
                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'OUT_OF_BOUND_ERROR !', 'null');
                    return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
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
