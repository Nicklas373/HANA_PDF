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
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\PdfjpgTask;
use Spatie\PdfToImage\Pdf;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class convertController extends Controller
{
	public function compress(){
		return view('convert');
	}

	public function pdf_init(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25000',
			'fileAlt' => ''
		]);

		if($validator->fails()) {
			return redirect()->back()->withErrors($validator->messages())->withInput();
		} else {
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
						$pdfUpload_Location = env('PDF_UPLOAD');
						$file = $request->file('file');
						$file->move($pdfUpload_Location,$file->getClientOriginalName());
						$pdfFileName = $pdfUpload_Location.'/'.$file->getClientOriginalName();
						$pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');

						if (file_exists($pdfFileName)) {
							$pdf = new Pdf($pdfFileName);
							$pdf->setPage(1)
								->setOutputFormat('png')
								->width(400)
								->saveImage(env('PDF_THUMBNAIL'));
							if (file_exists(env('PDF_THUMBNAIL').'/1.png')) {
								$thumbnail = file(env('PDF_THUMBNAIL').'/1.png');
								rename(env('PDF_THUMBNAIL').'/1.png', env('PDF_THUMBNAIL').'/'.$pdfNameWithoutExtension.'.png');
								return redirect()->back()->with('upload','/'.env('PDF_THUMBNAIL').'/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect()->back()->withErrors(['error'=>'Thumbnail file not found !'])->withInput();
							}
						} else {
							return redirect()->back()->withErrors(['error'=>'Thumbnail failed to generated !'])->withInput();
						}
					} else {
						return redirect()->back()->withErrors(['error'=>'PDF failed to upload !'])->withInput();
					}
				} else if ($request->post('formAction') == "convert") {
                    if(isset($_POST['convertType']))
                    {
                        $convertType = $request->post('convertType');

                        if ($convertType == 'excel') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = env('PDF_UPLOAD');
                                $file = $request->post('fileAlt');
                                $pdfProcessed_Location = 'temp';
                                $pdfName = basename($file);
                                $pdfNameWithoutExtension = basename($file, ".pdf");
                                $fileSize = filesize($file);
                                $hostName = AppHelper::instance()->getUserIpAddr();
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");

                                pdf_excel::create([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName
                                ]);

				                rename($pdfUpload_Location.'/'.$pdfName, $pdfUpload_Location.'/convert_xlsx.pdf');
                                $process = new Process(['python', public_path().'/ext-python/pdftoxlsx.py'], null, ['SYSTEMROOT' => getenv('SYSTEMROOT'), 'PATH' => getenv("PATH")]);
                                $process->run();

                                if (!$process->isSuccessful()) {
				                    //throw new ProcessFailedException($process); -> Debugging Only
                                    return redirect()->back()->withErrors(['error'=>'Convert process error !'])->withInput();
                                } else {
                                    if (file_exists($pdfProcessed_Location.'/converted.xlsx')) {
                                        $download_excel = $pdfProcessed_Location.'/converted.xlsx';
                                        return redirect()->back()->with('success',$download_excel);
                                    } else {
                                        return redirect()->back()->withErrors(['error'=>'Converted file not found !'])->withInput();
                                    }
                                }
                            } else {
                                return redirect()->back()->withErrors(['error'=>'INVALID_REQUEST_ERROR !'])->withInput();
                            }
                        } else if ($convertType == 'docx') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = env('PDF_UPLOAD');
                                $file = $request->post('fileAlt');
                                $pdfProcessed_Location = 'temp';
                                $pdfName = basename($file);
				                $pdfNameInfo = pathinfo($file);
                                $pdfNameWithoutExtension = $pdfNameInfo['filename'];
                                $fileSize = filesize($file);
                                $hostName = AppHelper::instance()->getUserIpAddr();
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");

                                pdf_word::create([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName
                                ]);

                                $wordsApi = new WordsApi(env('ASPOSE_CLOUD_CLIENT_ID'), env('ASPOSE_CLOUD_TOKEN'));
                                $uploadFileRequest = new UploadFileRequest($file, $pdfName);
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

                                if (json_decode($result, true) !== NULL) {
                                    if (AppHelper::instance()->getFtpResponse($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.".docx", $pdfNameWithoutExtension.".docx") == true) {
                                        $download_word = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.".docx";
                                        return redirect()->back()->with('success',$download_word);
                                    } else {
                                        return redirect()->back()->withErrors(['error'=>'FTP_CONNECTION_ERROR !'])->withInput();
                                    }
                                } else {
                                    return redirect()->back()->withErrors(['error'=>'Convert process error !'])->withInput();
                                }
                            } else {
                                return redirect()->back()->withErrors(['error'=>'INVALID_REQUEST_ERROR !'])->withInput();
                            }
                        } else if ($convertType == 'jpg') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = env('PDF_UPLOAD');
                                $file = $request->post('fileAlt');
                                $pdfProcessed_Location = 'temp';
                                $pdfName = basename($file);
                                $pdfNameWithoutExtension = basename($pdfName, ".pdf");
                                $fileSize = filesize($file);
                                $hostName = AppHelper::instance()->getUserIpAddr();
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");

                                pdf_jpg::create([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName
                                ]);

                                $ilovepdfTask = new PdfjpgTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                                $pdfFile = $ilovepdfTask->addFile($file);
                                $ilovepdfTask->setMode('pages');
                                $ilovepdfTask->setOutputFileName($pdfName);
                                $ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
                                $ilovepdfTask->execute();
                                $ilovepdfTask->download($pdfProcessed_Location);

                                if(is_file($file)) {
                                    unlink($file);
                                }

                                $download_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip';

                                if (file_exists($download_pdf)) {
                                    return redirect()->back()->with('success',$download_pdf);
                                } else {
                                    $download_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'-0001.jpg';
                                    if (file_exists($download_pdf)) {
                                        return redirect()->back()->with('success',$download_pdf);
                                    } else {
                                        return redirect()->back()->withErrors(['error'=>'Convert process error !'])->withInput();
                                    }
                                }
                            } else {
                                return redirect()->back()->withErrors(['error'=>'INVALID_REQUEST_ERROR !'])->withInput();
                            }
                        } else {
                            return redirect()->back()->withErrors(['error'=>'REQUEST_ERROR_OUT_OF_BOUND !'])->withInput();
                        }
                    } else {
                        return redirect()->back()->withErrors(['error'=>'REQUEST_TYPE_NOT_FOUND !'])->withInput();
                    }
				} else {
                    return redirect()->back()->withErrors(['error'=>'000'])->withInput();
				}
			} else {
                return redirect()->back()->withErrors(['error'=>'0x0'])->withInput();
			}
		}
	}
}
