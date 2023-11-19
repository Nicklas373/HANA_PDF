<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\pdf_convert;
use Aspose\Words\WordsApi;
use Aspose\Words\Model\Requests\{SaveAsRequest, UploadFileRequest};
use Aspose\Words\Model\{DocxSaveOptionsData};
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\Exceptions;
use Ilovepdf\OfficepdfTask;
use Ilovepdf\PdfjpgTask;
use Spatie\PdfToImage\Pdf;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;

class convertController extends Controller
{
	public function pdf_init(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf,pptx,docx,xlsx|max:25600',
			'fileAlt' => ''
		]);

        $uuid = AppHelper::Instance()->get_guid();

		if($validator->fails()) {
			return redirect()->back()->withErrors(['error'=>$validator->messages(), 'processId'=>$uuid])->withInput();
		} else {
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
                        $str = rand();
						$pdfUpload_Location = env('PDF_UPLOAD');
						$file = $request->file('file');
                        $randomizePdfFileName = md5($str);
                        $randomizePdfExtension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                        $randomizePdfPath = $pdfUpload_Location.'/'.$randomizePdfFileName.'.'.$randomizePdfExtension;
						$pdfFileName = $file->getClientOriginalName();
                        $file->storeAs('public/upload-pdf', $randomizePdfFileName.'.'.$randomizePdfExtension);
						if (Storage::disk('local')->exists('public/'.$randomizePdfPath)) {
							return redirect()->back()->with([
                                'status' => true,
                                'pdfRndmName' => Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$randomizePdfFileName.'.'.$randomizePdfExtension),
                                'pdfOriName' => $pdfFileName,
                            ]);
						} else {
							return redirect()->back()->withErrors(['error'=>'PDF file not found on the server !', 'processId'=>$uuid])->withInput();
						}
					} else {
						return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
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
                                                'python3',
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
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'PDF Conversion running out of time !',
                                        'err_api_reason' => $message->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    //throw new ProcessFailedException($asposeAPI);

                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion running out of time !', 'processId'=>$uuid])->withInput();
                                } catch (ProcessFailedException $message) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'Symfony runtime process fail exception !',
                                        'err_api_reason' => $message->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    //throw new ProcessFailedException($asposeAPI);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                }
                                if (!$asposeAPI->isSuccessful()) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'Python process fail !',
                                        'err_api_reason' => $asposeAPI->getOutput(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    //throw new ProcessFailedException($asposeAPI);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } else {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xlsx'), $pdfNameWithoutExtension.".xlsx") == true) {
                                        $download_excel = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xlsx');
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'result' => true,
                                            'err_reason' => null,
                                            'err_api_reason' => $asposeAPI->getOutput(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->with(["stats" => "scs", "res"=>$download_excel]);
                                    } else {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'result' => false,
                                            'err_reason' => 'Converted file not found on the server !',
                                            'err_api_reason' => $asposeAPI->getOutput(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'Converted file not found on the server !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                            } else {
                                return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
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
                                                'python3',
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
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'Symfony runtime process out of time !',
                                        'err_api_reason' => $message->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    //throw new ProcessFailedException($asposeAPI);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion running out of time !', 'processId'=>$uuid])->withInput();
                                } catch (ProcessFailedException $message) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'Symfony runtime process fail exception !',
                                        'err_api_reason' => $message->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    //throw new ProcessFailedException($asposeAPI);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                }
                                if (!$asposeAPI->isSuccessful()) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'Python process fail !',
                                        'err_api_reason' => $asposeAPI->getOutput(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    //throw new ProcessFailedException($asposeAPI);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } else {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pptx'), $pdfNameWithoutExtension.".pptx") == true) {
                                        $download_excel = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pptx');
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'result' => true,
                                            'err_reason' => null,
                                            'err_api_reason' => $asposeAPI->getOutput(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->with(["stats" => "scs", "res"=>$download_excel]);
                                    } else {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'result' => false,
                                            'err_reason' => 'Converted file not found on the server !',
                                            'err_api_reason' => $asposeAPI->getOutput(),
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'Converted file not found on the server !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                            } else {
                                return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
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
                                } catch (Exception $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'Aspose PDF API Error !',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                }
                                if (file_exists($pdfNewPath)) {
                                    unlink($pdfNewPath);
                                }
                                if (json_decode($result, true) !== NULL) {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.".docx"), $pdfNameWithoutExtension.".docx") == true) {
                                        $download_word = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.".docx");
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'result' => true,
                                            'err_reason' => null,
                                            'err_api_reason' => null,
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->with([
                                            "stats" => "scs",
                                            "res"=>$download_word
                                        ]);
                                    } else {
                                        DB::table('pdf_convert')->insert([
                                            'processId' => $uuid,
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'container' => $convertType,
                                            'result' => false,
                                            'err_reason' => 'FTP Server Connection Failed !',
                                            'err_api_reason' => null,
                                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'FTP Server Connection Failed !', 'processId'=>$uuid])->withInput();
                                    }
                                } else {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'Aspose Clouds API has fail while process, Please look on Aspose Dashboard !',
                                        'err_api_reason' => null,
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed', 'processId'=>$uuid])->withInput();
                                }
                            } else {
                                return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
                            }
                        } else if ($convertType == 'jpg') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = env('PDF_UPLOAD');
                                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                                $file = $request->post('fileAlt');
                                $pdfName = basename($file);
                                $pdfNameWithoutExtension = basename($pdfName, ".pdf");
                                $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
						        $fileSize = filesize($pdfNewPath);
                                $hostName = AppHelper::instance()->getUserIpAddr();
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                                try {
                                    $ilovepdfTask = new PdfjpgTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                                    $pdfFile = $ilovepdfTask->addFile($pdfNewPath);
                                    $ilovepdfTask->setMode('pages');
                                    $ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
                                    $ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
                                    $ilovepdfTask->execute();
                                    $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
                                } catch (\Ilovepdf\Exceptions\StartException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\AuthException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\UploadException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\TaskException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\PathException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Exception $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                }
                                if (file_exists($pdfNewPath)) {
                                    unlink($pdfNewPath);
                                }
                                if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip'))) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => true,
                                        'err_reason' => null,
                                        'err_api_reason' => null,
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->with([
                                        "stats" => "scs",
                                        "res"=>Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip')
                                    ]);
                                } else if (Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'-0001.jpg')) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
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
                                } else {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'Failed to download converted file from iLovePDF API !',
                                        'err_api_reason' => null,
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                }
                            } else {
                                return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
                            }
                        }  else if ($convertType == 'pdf') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = env('PDF_UPLOAD');
                                $pdfProcessed_Location = env('PDF_DOWNLOAD');
                                $file = $request->post('fileAlt');
                                $pdfName = basename($file);
                                $pdfNameWithoutExtension = pathinfo($pdfName, PATHINFO_FILENAME);
                                $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
						        $fileSize = filesize($pdfNewPath);
                                $hostName = AppHelper::instance()->getUserIpAddr();
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                                try {
                                    $ilovepdfTask = new OfficepdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                                    $pdfFile = $ilovepdfTask->addFile($pdfNewPath);
                                    $ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
                                    $ilovepdfTask->execute();
                                    $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
                                } catch (\Ilovepdf\Exceptions\StartException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\AuthException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\UploadException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\TaskException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\PathException $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                } catch (\Exception $e) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Conversion failed !', 'processId'=>$uuid])->withInput();
                                }
                                if (file_exists($pdfNewPath)) {
                                    unlink($pdfNewPath);
                                }
                                if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pdf'))) {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => true,
                                        'err_reason' => null,
                                        'err_api_reason' => null,
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->with([
                                        "stats" => "scs",
                                        "res"=>Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pdf')
                                    ]);
                                } else {
                                    DB::table('pdf_convert')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'container' => $convertType,
                                        'result' => false,
                                        'err_reason' => 'Failed to download converted file from iLovePDF API !',
                                        'err_api_reason' => null,
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'Failed to download converted file from iLovePDF API !', 'processId'=>$uuid])->withInput();
                                }
                            } else {
                                return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
                            }
                        } else {
                            return redirect()->back()->withErrors(['error'=>'REQUEST_ERROR_OUT_OF_BOUND !', 'processId'=>$uuid])->withInput();
                        }
                    } else {
                        return redirect()->back()->withErrors(['error'=>'REQUEST_TYPE_NOT_FOUND !', 'processId'=>$uuid])->withInput();
                    }
				} else {
                    return redirect()->back()->withErrors(['error'=>'000', 'processId'=>$uuid])->withInput();
				}
			} else {
                return redirect()->back()->withErrors(['error'=>'0x0', 'processId'=>$uuid])->withInput();
			}
		}
	}
}
