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
			return redirect('convert')->withErrors($validator->messages())->withInput();
		} else {
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
						$pdfUpload_Location = public_path('upload-pdf');
						$file = $request->file('file');
						$file->move($pdfUpload_Location,$file->getClientOriginalName());
						$pdfFileName = $pdfUpload_Location.'/'.$file->getClientOriginalName();
						$pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');

						if (file_exists($pdfFileName)) {
							$pdf = new Pdf($pdfFileName);
							$pdf->setPage(1)
								->setOutputFormat('png')
								->width(400)
								->saveImage(public_path('thumbnail'));
							if (file_exists(public_path('thumbnail').'/1.png')) {
								$thumbnail = file(public_path('thumbnail').'/1.png');
								rename(public_path('thumbnail').'/1.png', public_path('thumbnail').'/'.$pdfNameWithoutExtension.'.png');
								return redirect('convert')->with('upload','thumbnail/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect('convert')->withError('error',' has failed to upload !')->withInput();
							}
						} else {
							return redirect('convert')->withError('error',' has failed to upload !')->withInput();
						}
					} else {
						return redirect('convert')->withError('error',' FILE NOT FOUND !')->withInput();
					}
				} else if ($request->post('formAction') == "convert") {
                    if(isset($_POST['convertType']))
                    {
                        $convertType = $request->post('convertType');

                        if ($convertType == 'excel') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = public_path('upload-pdf');
                                $file = 'public/'.$request->post('fileAlt');
                                $pdfProcessed_Location = public_path('temp');
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
                    
                                $c = curl_init();

                                $cfile = curl_file_create($pdfUpload_Location.'/'.$file->getClientOriginalName(), 'application/pdf');

                                $apikey = 'dgxqu0tl0w06';
                                curl_setopt($c, CURLOPT_URL, "https://pdftables.com/api?key=$apikey&format=xlsx-single");
                                curl_setopt($c, CURLOPT_POSTFIELDS, array('file' => $cfile));
                                curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($c, CURLOPT_FAILONERROR,true);
                                curl_setopt($c, CURLOPT_ENCODING, "gzip,deflate");

                                $result = curl_exec($c);

                                if (curl_errno($c) > 0) {
                                    return redirect('convert')->withError('error',' has failed to convert !')->withInput();
                                    curl_close($c);
                                } else {
                                    file_put_contents ($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xls', $result);
                                    curl_close($c);
                                    if (file_exists($pdfProcessed_Location.$pdfNameWithoutExtension.'.xls')) {
                                        $download_excel = $pdfProcessed_Location.$pdfNameWithoutExtension.'.xls';
                                        return redirect('convert')->with('success','temp'.'/'.$pdfNameWithoutExtension.'.xls');
                                    } else {
                                        return redirect('convert')->withError('error',' has failed to convert !')->withInput();
                                    }
                                }
                            } else {
                                return redirect('convert')->withError('error',' REQUEST NOT FOUND !')->withInput();
                            }
                        } else if ($convertType == 'docx') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = public_path('upload-pdf');
                                $file = 'public/'.$request->post('fileAlt');
                                $pdfProcessed_Location = public_path('temp');
                                $pdfName = basename($file);
                                $pdfNameWithoutExtension = basename($pdfName, ".pdf");
                                $fileSize = filesize($file);
                                $hostName = AppHelper::instance()->getUserIpAddr();
                                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        
                                pdf_word::create([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName
                                ]);
                                
                                ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now
                                $wordsApi = new WordsApi('73751f49-388b-4366-aeb4-d76587d5123e', '1792ea481716ff7788b276c8c88df6b8');
                                ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now
                                $uploadFileRequest = new UploadFileRequest($file, $pdfName);
                                ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now
                                $wordsApi->uploadFile($uploadFileRequest);
                                ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now
                                $requestSaveOptionsData = new DocxSaveOptionsData(array(
                                    "save_format" => "docx",
                                    "file_name" => 'EMSITPRO-PDFTools/Completed/'.$pdfNameWithoutExtension.".docx",
                                ));
        
                                $request = new SaveAsRequest(
                                    $pdfName,
                                    $requestSaveOptionsData,
                                    NULL,
                                    NULL,
                                    NULL,
                                    NULL
                                );
                                ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now
                                $result = $wordsApi->saveAs($request);
                                ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now
        
                                if (json_decode($result, true) !== NULL) {
                                    $download_word = 'https://drive.google.com/drive/folders/1D3YicPoJDk595tVw01NUyx_Osf3Q2Ca8?usp=sharing';
                                    return redirect('convert')->with('success',$download_word);
                                } else {
                                    return redirect('convert')->withError('error',' has failed to convert !')->withInput();
                                }
                            } else {
                                return redirect('convert')->withError('error',' REQUEST NOT FOUND !')->withInput();
                            }
                        } else if ($convertType == 'jpg') {
                            if(isset($_POST['fileAlt'])) {
                                $pdfUpload_Location = public_path('upload-pdf');
                                $file = 'public/'.$request->post('fileAlt');
                                $pdfProcessed_Location = public_path('temp');
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
                    
                                $ilovepdfTask = new PdfjpgTask('project_public_0ba8067b84cb4d4582b8eac3aa0591b2_XwmRS824bc5681a3ca4955a992dde44da6ac1','secret_key_937ea5acab5e22f54c6c7601fd7866dc_jT3DA5ed31082177f48cd792801dcf664c41b');
                                $ilovepdfTask->setFileEncryption('XrPiOcvugxyGrJnX');
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
                                    return redirect('convert')->with('success',$download_pdf);
                                } else {
                                    $download_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'-0001.jpg';
                                    if (file_exists($download_pdf)) {
                                        return redirect('convert')->with('success','temp'.'/'.$pdfNameWithoutExtension.'-0001.jpg');
                                    } else {
                                        return redirect('convert')->withError('error',' has failed to convert !')->withInput();
                                    }
                                }
                            } else {
                                return redirect('convert')->withError('error',' REQUEST NOT FOUND !')->withInput();
                            }
                        } else {
                            return redirect('convert')->withError('error','INVALID OPTION !')->withInput();
                        }
                    } else {
                        return redirect('convert')->withError('error','MISSING VALUE !')->withInput();
                    }
				} else {
					return redirect('convert')->withError('error',' FILE NOT FOUND !')->withInput();
				}
			} else {
				return redirect('convert')->withError('error',' REQUEST NOT FOUND !')->withInput();
			}
		}
	}
}