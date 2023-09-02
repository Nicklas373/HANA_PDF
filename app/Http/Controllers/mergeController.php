<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Helpers\AppHelper;
use App\Models\merge_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Spatie\PdfToImage\Pdf;

class mergeController extends Controller
{
    public function merge() {
        return view('merge');
    }

    public function pdf_merge(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
            'file' => 'max:25000',
			'fileAlt' => '',
            'dropFile' => ''
        ]);

        if($validator->fails()) {
            return redirect('merge')->withErrors($validator->messages())->withInput();
        } else {
            if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if ($request->hasfile('file')) {
                        foreach ($request->file('file') as $file) {
                            $filename = $file->getClientOriginalName();
						    $pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');
                            $file->move(env('PDF_MERGE_TEMP'), $filename);
                            $pdf = new Pdf(env('PDF_MERGE_TEMP').'/'.$filename);
							$pdf->setPage(1)
								->setOutputFormat('png')
								->width(400)
								->saveImage(public_path('thumbnail'));
							if (file_exists(public_path('thumbnail').'/1.png')) {
								$thumbnail = file(public_path('thumbnail').'/1.png');
								rename(public_path('thumbnail').'/1.png', public_path('thumbnail').'/'.$pdfNameWithoutExtension.'.png');
                                $pdfResponse[] = 'temp-merge/'.$pdfNameWithoutExtension.'.pdf';
							} else {
								return redirect()->back()->withErrors(['error'=>'Thumbnail failed to generated !'])->withInput();
							}
                        }
                        return redirect()->back()->with('upload', implode(',',$pdfResponse));
                    } else {
                        return redirect()->back()->withErrors(['error'=>'PDF failed to upload !'])->withInput();
                    }
                } else if ($request->post('formAction') == "merge") {
					if(isset($_POST['fileAlt'])) {
                        if(isset($_POST['dropFile']))
						{
							$dropFile = array($request->post('dropFile'));
						} else {
							$dropFile = array();
						}
						$fileNameArray = 'public/'.$request->post('fileAlt');
						$fileName = basename($request->post('fileAlt'));
                        $fileSizeArray = AppHelper::instance()->folderSize(public_path('temp-merge'));
                        $fileSizeInMB = AppHelper::instance()->convert($fileSizeArray, "MB");
                        $hostName = AppHelper::instance()->getUserIpAddr();
                        $pdfArray = scandir(env('PDF_MERGE_TEMP'));
                        $pdfStartPages = 1;
                        $pdfPreProcessed_Location = public_path('temp-merge');
                        $pdfProcessed_Location = public_path('temp');

                        merge_pdf::create([
                            'fileName' => $fileName,
                            'fileSize' => $fileSizeInMB,
                            'hostName' => $hostName
                        ]);

                        $ilovepdf = new Ilovepdf('project_public_0ba8067b84cb4d4582b8eac3aa0591b2_XwmRS824bc5681a3ca4955a992dde44da6ac1','secret_key_937ea5acab5e22f54c6c7601fd7866dc_jT3DA5ed31082177f48cd792801dcf664c41b');
                        $ilovepdfTask = $ilovepdf->newTask('merge');
                        $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                        foreach($pdfArray as $value) {
                            if (strlen($value) >= 4) {
                                $arrayCount = 1;
                                $arrayOrder = strval($arrayCount);
                                $pdfName = $ilovepdfTask->addFile($pdfPreProcessed_Location.'/'.$value);
                                $arrayCount += 1;
                            }
                        }
                        $ilovepdfTask->execute();
                        $ilovepdfTask->download($pdfProcessed_Location);
                        $download_pdf = $pdfProcessed_Location.'/'.'merged.pdf';

                        $tempPDFfiles = glob($pdfPreProcessed_Location . '/*');
                        foreach($tempPDFfiles as $file){
                            if(is_file($file)) {
                                unlink($file);
                            }
                        }

                        if (file_exists($download_pdf)) {
                            return redirect('merge')->with('success','temp/merged.pdf');
                        } else {
                            return redirect()->back()->withErrors(['error'=>'Merged process error !'])->withInput();
                        }
					} else {
						return redirect()->back()->withErrors(['error'=>'PDF failed to upload !'])->withInput();
					}
				} else {
					return redirect()->back()->withErrors(['error'=>'INVALID_REQUEST_ERROR !'])->withInput();
				}
			} else {
				return redirect()->back()->withErrors(['error'=>'REQUEST_ERROR_OUT_OF_BOUND !'])->withInput();
			}
        }
    }
}
