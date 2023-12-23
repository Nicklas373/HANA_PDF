<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\init_pdf;
use App\Models\pdf_convert;
use Aspose\Words\WordsApi;
use Aspose\Words\Model\Requests\{SaveAsRequest, UploadFileRequest};
use Aspose\Words\Model\{DocxSaveOptionsData};
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
	public function pdf_init(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf,pptx,docx,xlsx,jpg,png,jpeg,tiff|max:25600',
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
                        $randomizePdfFileName = 'pdf_convert_'.substr(md5(uniqid($str)), 0, 8);
                        $randomizePdfExtension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                        $randomizePdfPath = $pdfUpload_Location.'/'.$randomizePdfFileName.'.'.$randomizePdfExtension;
						$pdfFileName = $file->getClientOriginalName();
                        $fileSize = filesize($file);
                        $file->storeAs('public/upload-pdf', $randomizePdfFileName.'.'.$randomizePdfExtension);
						if (Storage::disk('local')->exists('public/'.$randomizePdfPath)) {
							return redirect()->back()->with([
                                'status' => true,
                                'pdfRndmName' => Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$randomizePdfFileName.'.'.$randomizePdfExtension),
                                'pdfOriName' => $pdfFileName,
                            ]);
						} else {
                            try {
                                DB::table('pdf_convert')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $randomizePdfFileName.'.pdf',
                                    'fileSize' => $fileSize,
                                    'container' => 'null',
                                    'img_extract' => false,
                                    'result' => false,
                                    'err_reason' => 'PDF file not found on the server !',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF file not found on the server !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_convert')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'container' => 'null',
                                    'img_extract' => false,
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                            }
						}
					} else {
                        try {
                            DB::table('pdf_convert')->insert([
                                'processId' => $uuid,
                                'fileName' => 'null',
                                'fileSize' => 'null',
                                'container' => 'null',
                                'img_extract' => false,
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
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'PDF Conversion running out of time !',
                                            'err_api_reason' => $message->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion running out of time !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (ProcessFailedException $message) {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Symfony runtime process fail exception !',
                                            'err_api_reason' => $message->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                                if (!$asposeAPI->isSuccessful()) {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xslx'), $pdfNameWithoutExtension.".xlsx") == true) {
                                        $download_pptx = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xlsx');
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => true,
                                                'err_reason' => 'null',
                                                'err_api_reason' => $asposeAPI->getOutput(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->with(["stats" => "scs", "res"=>$download_pptx]);
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } else {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Python process fail !',
                                                'err_api_reason' => $asposeAPI->getOutput(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
                                } else {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xlsx'), $pdfNameWithoutExtension.".xlsx") == true) {
                                        $download_excel = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xlsx');
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => true,
                                                'err_reason' => 'null',
                                                'err_api_reason' => $asposeAPI->getOutput(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->with(["stats" => "scs", "res"=>$download_excel]);
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } else {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Converted file not found on the server !',
                                                'err_api_reason' => $asposeAPI->getOutput(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'Converted file not found on the server !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
                                }
                            } else {
                                try {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => 'null',
                                        'fileSize' => 'null',
                                        'container' => 'null',
                                        'img_extract' => false,
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
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Symfony runtime process out of time !',
                                            'err_api_reason' => $message->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion running out of time !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (ProcessFailedException $message) {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Symfony runtime process fail exception !',
                                            'err_api_reason' => $message->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                                if (!$asposeAPI->isSuccessful()) {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pptx'), $pdfNameWithoutExtension.".pptx") == true) {
                                        $download_pptx = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pptx');
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => true,
                                                'err_reason' => 'null',
                                                'err_api_reason' => $asposeAPI->getOutput(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->with(["stats" => "scs", "res"=>$download_pptx]);
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } else {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Python process fail !',
                                                'err_api_reason' => $asposeAPI->getOutput(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
                                } else {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pptx'), $pdfNameWithoutExtension.".pptx") == true) {
                                        $download_excel = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pptx');
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => true,
                                                'err_reason' => 'null',
                                                'err_api_reason' => $asposeAPI->getOutput(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->with(["stats" => "scs", "res"=>$download_excel]);
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } else {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Converted file not found on the server !',
                                                'err_api_reason' => $asposeAPI->getOutput(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'Converted file not found on the server !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
                                }
                            } else {
                                try {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => 'null',
                                        'fileSize' => 'null',
                                        'container' => 'null',
                                        'img_extract' => false,
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
                                $hostName = AppHelper::instance()->getUserIpAddr();
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
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Aspose PDF API Error !',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                                if (file_exists($pdfNewPath)) {
                                    unlink($pdfNewPath);
                                }
                                if (json_decode($result, true) !== NULL) {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.".docx"), $pdfNameWithoutExtension.".docx") == true) {
                                        $download_word = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.".docx");
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => true,
                                                'err_reason' => 'null',
                                                'err_api_reason' => 'null',
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->with([
                                                "stats" => "scs",
                                                "res"=>$download_word
                                            ]);
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } else {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'FTP Server Connection Failed !',
                                                'err_api_reason' => 'null',
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'FTP Server Connection Failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
                                } else {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Aspose Clouds API has fail while process, Please look on Aspose Dashboard !',
                                            'err_api_reason' => 'null',
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                            } else {
                                try {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => 'null',
                                        'fileSize' => 'null',
                                        'container' => 'null',
                                        'img_extract' => false,
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
                                $hostName = AppHelper::instance()->getUserIpAddr();
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
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => $extMode,
                                            'result' => false,
                                            'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (AuthException $e) {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => $extMode,
                                            'result' => false,
                                            'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (UploadException $e) {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => $extMode,
                                            'result' => false,
                                            'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (ProcessException $e) {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => $extMode,
                                            'result' => false,
                                            'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (DownloadException $e) {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => $extMode,
                                            'result' => false,
                                            'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (TaskException $e) {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => $extMode,
                                            'result' => false,
                                            'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (PathException $e) {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => $extMode,
                                            'result' => false,
                                            'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (\Exception $e) {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => $extMode,
                                            'result' => false,
                                            'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
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
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => $extMode,
                                            'result' => true,
                                            'err_reason' => null,
                                            'err_api_reason' => null,
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->with([
                                            "stats" => "scs",
                                            "res"=>Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip')
                                        ]);
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } else {
                                    if ($pdfTotalPages = 1 && $extMode) {
                                        if (Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.jpg')) {
                                            try {
                                                DB::table('pdf_convert')->insert([
                                                    'processId' => $uuid,
                                                    'fileName' => $pdfName,
                                                    'fileSize' => $newFileSize,
                                                    'container' => $convertType,
                                                    'img_extract' => $extMode,
                                                    'result' => true,
                                                    'err_reason' => null,
                                                    'err_api_reason' => null,
                                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                                ]);
                                                return redirect()->back()->with([
                                                    "stats" => "scs",
                                                    "res"=>Storage::disk('local')->url('temp/'.$pdfNameWithoutExtension.'.jpg'),
                                                    'processId'=>$uuid
                                                ])->withInput();
                                            } catch (QueryException $ex) {
                                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                            } catch (\Exception $e) {
                                                DB::table('pdf_convert')->insert([
                                                    'processId' => $uuid,
                                                    'fileName' => 'null',
                                                    'fileSize' => 'null',
                                                    'container' => 'null',
                                                    'img_extract' => false,
                                                    'result' => false,
                                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                    'err_api_reason' => $e->getMessage(),
                                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                                ]);
                                                return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                            }
                                        }
                                    } else if ($pdfTotalPages = 1 && !$extMode) {
                                        if (Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'-0001.jpg')) {
                                            try {
                                                DB::table('pdf_convert')->insert([
                                                    'processId' => $uuid,
                                                    'fileName' => $pdfName,
                                                    'fileSize' => $newFileSize,
                                                    'container' => $convertType,
                                                    'img_extract' => $extMode,
                                                    'result' => true,
                                                    'err_reason' => null,
                                                    'err_api_reason' => null,
                                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                                ]);
                                                return redirect()->back()->with([
                                                    "stats" => "scs",
                                                    "res"=>Storage::disk('local')->url('temp/'.$pdfNameWithoutExtension.'-0001.jpg'),
                                                    'processId'=>$uuid
                                                ])->withInput();
                                            } catch (QueryException $ex) {
                                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                            } catch (\Exception $e) {
                                                DB::table('pdf_convert')->insert([
                                                    'processId' => $uuid,
                                                    'fileName' => 'null',
                                                    'fileSize' => 'null',
                                                    'container' => 'null',
                                                    'img_extract' => false,
                                                    'result' => false,
                                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                    'err_api_reason' => $e->getMessage(),
                                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                                ]);
                                                return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                            }
                                        }
                                    } else {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => $extMode,
                                                'result' => false,
                                                'err_reason' => 'Failed to download converted file from iLovePDF API !',
                                                'err_api_reason' => null,
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
                                }
                            } else {
                                try {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => 'null',
                                        'fileSize' => 'null',
                                        'container' => 'null',
                                        'img_extract' => false,
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
                        }  else if ($convertType == 'pdf') {
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
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (AuthException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (UploadException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (ProcessException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (DownloadException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (TaskException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (PathException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (\Exception $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        }
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
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
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (AuthException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (UploadException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (ProcessException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (DownloadException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (TaskException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (PathException $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    } catch (\Exception $e) {
                                        try {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => $pdfName,
                                                'fileSize' => $newFileSize,
                                                'container' => $convertType,
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        } catch (QueryException $ex) {
                                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                        } catch (\Exception $e) {
                                            DB::table('pdf_convert')->insert([
                                                'processId' => $uuid,
                                                'fileName' => 'null',
                                                'fileSize' => 'null',
                                                'container' => 'null',
                                                'img_extract' => false,
                                                'result' => false,
                                                'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                                'err_api_reason' => $e->getMessage(),
                                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                            ]);
                                            return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                        }
                                    }
                                }
                                if (file_exists($pdfNewPath)) {
                                    unlink($pdfNewPath);
                                }
                                if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pdf'))) {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => false,
                                            'result' => true,
                                            'err_reason' => 'null',
                                            'err_api_reason' => 'null',
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->with([
                                            "stats" => "scs",
                                            "res"=>Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pdf')
                                        ]);
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } else {
                                    try {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Failed to download converted file from iLovePDF API !',
                                            'err_api_reason' => 'null',
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'Failed to download converted file from iLovePDF API !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => 'null',
                                            'fileSize' => 'null',
                                            'container' => 'null',
                                            'img_extract' => false,
                                            'result' => false,
                                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                            'err_api_reason' => $e->getMessage(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                            } else {
                                try {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => 'null',
                                        'fileSize' => 'null',
                                        'container' => 'null',
                                        'img_extract' => false,
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
                                DB::table('pdf_convert')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'container' => 'null',
                                    'img_extract' => false,
                                    'result' => false,
                                    'err_reason' => 'REQUEST_ERROR_OUT_OF_BOUND !',
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
                            DB::table('pdf_convert')->insert([
                                'processId' => $uuid,
                                'fileName' => 'null',
                                'fileSize' => 'null',
                                'container' => 'null',
                                'img_extract' => false,
                                'result' => false,
                                'err_reason' => 'REQUEST_TYPE_NOT_FOUND !',
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
                        DB::table('pdf_convert')->insert([
                            'processId' => $uuid,
                            'fileName' => 'null',
                            'fileSize' => 'null',
                            'container' => 'null',
                            'img_extract' => false,
                            'result' => false,
                            'err_reason' => '000',
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
                    DB::table('pdf_convert')->insert([
                        'processId' => $uuid,
                        'fileName' => 'null',
                        'fileSize' => 'null',
                        'container' => 'null',
                        'img_extract' => false,
                        'result' => false,
                        'err_reason' => '0x0',
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
