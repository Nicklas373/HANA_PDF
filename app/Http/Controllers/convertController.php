<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\pdf_excel;
use App\Models\pdf_jpg;
use App\Models\pdf_word;
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
use Ilovepdf\PdfjpgTask;
use Spatie\PdfToImage\Pdf;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class convertController extends Controller
{
	public function pdf_init(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25000',
			'fileAlt' => ''
		]);

        $uuid = AppHelper::Instance()->get_guid();

		if($validator->fails()) {
			return redirect()->back()->withErrors(['error'=>$validator->messages(), 'uuid'=>$uuid])->withInput();
		} else {
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
                        $str = rand();
						$pdfUpload_Location = env('PDF_UPLOAD');
						$file = $request->file('file');
                        $randomizePdfFileName = md5($str);
                        $randomizePdfPath = $pdfUpload_Location.'/'.$randomizePdfFileName.'.pdf';
						$pdfFileName = $file->getClientOriginalName();
                        $file->storeAs('public/upload-pdf', $randomizePdfFileName.'.pdf');
						if (Storage::disk('local')->exists('public/'.$randomizePdfPath)) {
							$pdf = new Pdf(Storage::disk('local')->path('public/'.$randomizePdfPath));
							$pdf->setPage(1)
								->setOutputFormat('png')
								->width(400)
								->saveImage(Storage::disk('local')->path('public/'.env('PDF_THUMBNAIL')));
							if (Storage::disk('local')->exists('public/'.env('PDF_THUMBNAIL').'/1.png')) {
                                Storage::disk('local')->move('public/'.env('PDF_THUMBNAIL').'/1.png', 'public/'.env('PDF_THUMBNAIL').'/'.$randomizePdfFileName.'.png');
                                return redirect()->back()->with([
                                    'status' => true,
                                    'pdfRndmName' => Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$randomizePdfFileName.'.pdf'),
                                    'pdfThumbName' => Storage::disk('local')->url(env('PDF_THUMBNAIL').'/'.$randomizePdfFileName.'.png'),
                                    'pdfOriName' => $pdfFileName,
                                ]);
							} else {
								return redirect()->back()->withErrors(['error'=>'Thumbnail file not found !', 'uuid'=>$uuid])->withInput();
							}
						} else {
							return redirect()->back()->withErrors(['error'=>'Thumbnail failed to generated !', 'uuid'=>$uuid])->withInput();
						}
					} else {
						return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'uuid'=>$uuid])->withInput();
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
                                $hostName = AppHelper::instance()->getUserIpAddr();
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
				                rename(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName), Storage::disk('local')->path('public/'.$pdfUpload_Location.'/convert_xlsx.pdf'));
                                $process = new Process([
                                    'python3',
                                    public_path().'/ext-python/pdftoxlsx.py'],
                                    null,
                                    [
                                        'SYSTEMROOT' => getenv('SYSTEMROOT'),
                                        'PATH' => getenv("PATH")
                                    ]
                                );
                                $process->run();

                                if (!$process->isSuccessful()) {
				                    //throw new ProcessFailedException($process); -> For debugging only
                                    DB::table('pdf_excels')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'Python process fail !',
                                        'err_api_reason' => null,
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'Python process fail !', 'uuid'=>$uuid])->withInput();
                                } else {
                                    if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/converted.xlsx'))) {
                                        $download_excel = Storage::disk('local')->url($pdfProcessed_Location.'/converted.xlsx');
                                        DB::table('pdf_excels')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'hostName' => $hostName,
                                            'result' => true,
                                            'err_reason' => null,
                                            'err_api_reason' => null,
                                            'uuid' => $uuid,
                                            'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->with(["stats" => "scs", "res"=>$download_excel]);
                                    } else {
                                        DB::table('pdf_excels')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'hostName' => $hostName,
                                            'result' => false,
                                            'err_reason' => 'Converted file not found on the server !',
                                            'err_api_reason' => null,
                                            'uuid' => $uuid,
                                            'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'Converted file not found on the server !', 'uuid'=>$uuid])->withInput();
                                    }
                                }
                            } else {
                                return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'uuid'=>$uuid])->withInput();
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
                                    DB::table('pdf_words')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'Aspose PDF API Error !',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'Aspose PDF API Error !', 'uuid'=>$uuid])->withInput();
                                }

                                if (json_decode($result, true) !== NULL) {
                                    if (AppHelper::instance()->getFtpResponse(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.".docx"), $pdfNameWithoutExtension.".docx") == true) {
                                        $download_word = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.".docx");
                                        DB::table('pdf_words')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'hostName' => $hostName,
                                            'result' => true,
                                            'err_reason' => null,
                                            'err_api_reason' => null,
                                            'uuid' => $uuid,
                                            'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->with([
                                            "stats" => "scs",
                                            "res"=>$download_word
                                        ]);
                                    } else {
                                        DB::table('pdf_words')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'hostName' => $hostName,
                                            'result' => false,
                                            'err_reason' => 'FTP Server Connection Failed !',
                                            'err_api_reason' => null,
                                            'uuid' => $uuid,
                                            'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'FTP Server Connection Failed !', 'uuid'=>$uuid])->withInput();
                                    }
                                } else {
                                    DB::table('pdf_words')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'Aspose Clouds API has fail while process, Please look on Aspose Dashboard !',
                                        'err_api_reason' => null,
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'Aspose Clouds API has fail while process, Please look on Aspose Dashboard !', 'uuid'=>$uuid])->withInput();
                                }
                            } else {
                                return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'uuid'=>$uuid])->withInput();
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
                                    DB::table('pdf_jpgs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\AuthException $e) {
                                    DB::table('pdf_jpgs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\UploadException $e) {
                                    DB::table('pdf_jpgs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                                    DB::table('pdf_jpgs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                                    DB::table('pdf_jpgs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\TaskException $e) {
                                    DB::table('pdf_jpgs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\PathException $e) {
                                    DB::table('pdf_jpgs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Exception $e) {
                                    DB::table('pdf_jpgs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                }

                                if (file_exists($pdfNewPath)) {
                                    unlink($pdfNewPath);
                                }

                                if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip'))) {
                                    DB::table('pdf_jpgs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => true,
                                        'err_reason' => null,
                                        'err_api_reason' => null,
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->with([
                                        "stats" => "scs",
                                        "res"=>Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip')
                                    ]);
                                } else if (Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'-0001.jpg')) {
                                    DB::table('pdf_jpgs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => true,
                                        'err_reason' => null,
                                        'err_api_reason' => null,
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->with([
                                        "stats" => "scs",
                                        "res"=>Storage::disk('local')->url('temp/'.$pdfNameWithoutExtension.'-0001.jpg'),
                                        'uuid'=>$uuid
                                    ])->withInput();
                                } else {
                                    DB::table('pdf_jpgs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'result' => false,
                                        'err_reason' => 'Failed to download converted file from iLovePDF API !',
                                        'err_api_reason' => null,
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'Failed to download converted file from iLovePDF API !', 'uuid'=>$uuid])->withInput();
                                }
                            } else {
                                return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'uuid'=>$uuid])->withInput();
                            }
                        } else {
                            return redirect()->back()->withErrors(['error'=>'REQUEST_ERROR_OUT_OF_BOUND !', 'uuid'=>$uuid])->withInput();
                        }
                    } else {
                        return redirect()->back()->withErrors(['error'=>'REQUEST_TYPE_NOT_FOUND !', 'uuid'=>$uuid])->withInput();
                    }
				} else {
                    return redirect()->back()->withErrors(['error'=>'000', 'uuid'=>$uuid])->withInput();
				}
			} else {
                return redirect()->back()->withErrors(['error'=>'0x0', 'uuid'=>$uuid])->withInput();
			}
		}
	}
}
