<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\compression_pdf;
use App\Models\init_pdf;
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
	public function pdf_init(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25600',
			'fileAlt' => ''
		]);

        $uuid = AppHelper::Instance()->get_guid();

		if($validator->fails()) {
            try {
                DB::table('pdf_init')->insert([
                    'processId' => $uuid,
                    'err_reason' => $validator->messages(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'File validation failed !', 'processId'=>$uuid])->withInput();
            } catch (QueryException $ex) {
                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
            }
		} else {
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
                        $str = rand(1000,10000000);
						$pdfUpload_Location = env('PDF_UPLOAD');
                        $file = $request->file('file');
                        $randomizePdfFileName = 'pdf_compress_'.substr(md5(uniqid($str)), 0, 8);
                        $randomizePdfPath = $pdfUpload_Location.'/'.$randomizePdfFileName.'.pdf';
						$pdfFileName = $file->getClientOriginalName();
                        $fileSize = filesize($file);
                        $file->storeAs('public/upload-pdf', $randomizePdfFileName.'.pdf');
						if (Storage::disk('local')->exists('public/'.$randomizePdfPath)) {
							return redirect()->back()->with([
                                'status' => true,
                                'pdfRndmName' => Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$randomizePdfFileName.'.pdf'),
                                'pdfOriName' => $pdfFileName,
                            ]);
						} else {
                            try {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $randomizePdfFileName.'.pdf',
                                    'fileSize' => $fileSize,
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'PDF file not found on the server !',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF file not found on the server !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            }
						}
					} else {
                        try {
                            DB::table('pdf_compress')->insert([
                                'processId' => $uuid,
                                'fileName' => 'null',
                                'fileSize' => 'null',
                                'compFileSize' => 'null',
                                'compMethod' => 'null',
                                'result' => false,
                                'err_reason' => 'PDF failed to upload !',
                                'err_api_reason' => 'null',
                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
                        } catch (QueryException $ex) {
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
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
                            try {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => 'null',
                                    'compMethod' => $compMethod,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (AuthException $e) {
                            try {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => 'null',
                                    'compMethod' => $compMethod,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (UploadException $e) {
                            try {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => 'null',
                                    'compMethod' => $compMethod,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (ProcessException $e) {
                            try {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => 'null',
                                    'compMethod' => $compMethod,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (DownloadException $e) {
                            try {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => 'null',
                                    'compMethod' => $compMethod,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (TaskException $e) {
                            try {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => 'null',
                                    'compMethod' => $compMethod,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (PathException $e) {
                            try {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => 'null',
                                    'compMethod' => $compMethod,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Exception $e) {
                            try {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => 'null',
                                    'compMethod' => $compMethod,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            }
                        }

                        if (file_exists($pdfNewPath)) {
                            unlink($pdfNewPath);
                        }

                        if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfName))) {
                            $compFileSize = filesize(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfName));
                            $newCompFileSize = AppHelper::instance()->convert($compFileSize, "MB");
                            try {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => $newCompFileSize,
                                    'compMethod' => $compMethod,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res"=>Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfName),
                                    "curFileSize"=>$newFileSize,
                                    "newFileSize"=>$newCompFileSize,
                                    "compMethod"=>$compMethod
                                ]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            }
                        } else {
                            try {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'compFileSize' => 'null',
                                    'compMethod' => $compMethod,
                                    'result' => false,
                                    'err_reason' => 'Failed to download file from iLovePDF API !',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_compress')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'compFileSize' => 'null',
                                    'compMethod' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Compression failed !', 'processId'=>$uuid])->withInput();
                            }
                        }
					} else {
                        try {
                            DB::table('pdf_compress')->insert([
                                'processId' => $uuid,
                                'fileName' => 'null',
                                'fileSize' => 'null',
                                'compFileSize' => 'null',
                                'compMethod' => 'null',
                                'result' => false,
                                'err_reason' => 'PDF failed to upload !',
                                'err_api_reason' => 'null',
                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
                        } catch (QueryException $ex) {
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                        }
					}
				} else {
                    try {
                        DB::table('pdf_compress')->insert([
                            'processId' => $uuid,
                            'fileName' => 'null',
                            'fileSize' => 'null',
                            'compFileSize' => 'null',
                            'compMethod' => 'null',
                            'result' => false,
                            'err_reason' => 'INVALID_REQUEST_ERROR !',
                            'err_api_reason' => 'null',
                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                        ]);
                        return redirect()->back()->withErrors(['error'=>'PDF process unknown error !', 'processId'=>$uuid])->withInput();
                    } catch (QueryException $ex) {
                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                    }
				}
			} else {
                try {
                    DB::table('pdf_compress')->insert([
                        'processId' => $uuid,
                        'fileName' => 'null',
                        'fileSize' => 'null',
                        'compFileSize' => 'null',
                        'compMethod' => 'null',
                        'result' => false,
                        'err_reason' => 'OUT_OF_BOUND_ERROR !',
                        'err_api_reason' => 'null',
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'PDF process unknown error !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                }
			}
		}
	}
}
