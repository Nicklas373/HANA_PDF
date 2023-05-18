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
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
            if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if ($request->hasfile('file')) { 
                        foreach ($request->file('file') as $file) {
                            $filename = $file->getClientOriginalName();
						    $pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');
                            $file->move(public_path('temp-merge'), $filename);
                            $pdf = new Pdf(public_path('temp-merge').'/'.$filename);
							$pdf->setPage(1)
								->setOutputFormat('png')
								->width(400)
								->saveImage(env('pdf_thumbnail'));
							if (file_exists(env('pdf_thumbnail').'/1.png')) {
								$thumbnail = file(env('pdf_thumbnail').'/1.png');
								rename(env('pdf_thumbnail').'/1.png', env('pdf_thumbnail').'/'.$pdfNameWithoutExtension.'.png');
                                $pdfResponse[] = 'temp-merge/'.$pdfNameWithoutExtension.'.pdf';
							}
                        }
                        
                        return redirect()->back()->with('upload', implode(',',$pdfResponse));
                    } else {
                        return redirect()->back()->withError('error',' has failed to merged !')->withInput();
                    }
                } else if ($request->post('formAction') == "merge") {
					if(isset($_POST['fileAlt'])) {
                        if(isset($_POST['dropFile']))
						{
							$dropFile = array($request->post('dropFile'));
						} else {
							$dropFile = array();
						}
						$fileNameArray = $request->post('fileAlt');
                        $fileSizeArray = AppHelper::instance()->folderSize(public_path('temp-merge'));
                        $fileSizeInMB = AppHelper::instance()->convert($fileSizeArray, "MB");
                        $hostName = AppHelper::instansce()->getUserIpAddr();
                        $pdfArray = scandir(public_path('temp-merge'));
                        $pdfStartPages = 1;
                        $pdfPreProcessed_Location = 'temp-merge';
                        $pdfProcessed_Location = 'temp';
            
                        merge_pdf::create([
                            'fileName' => $fileNameArray,
                            'fileSize' => $fileSizeInMB,
                            'hostName' => $hostName
                        ]);
			
                        $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
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
                        $download_pdf = $pdfProcessed_Location.'/merged.pdf';
            
                        $tempPDFfiles = glob($pdfPreProcessed_Location . '/*');
                        foreach($tempPDFfiles as $file){
                            if(is_file($file)) {
                                unlink($file);
                            }
                        }
                        
                        if (file_exists($download_pdf)) {
                            return redirect()->back()->with('success',$download_pdf);
                        } else {
                            return redirect()->back()->withError('error',' has failed to merged !')->withInput();
                        }
					} else {
						return redirect()->back()->withError('error',' REQUEST NOT FOUND !')->withInput();
					}
				} else {
					return redirect()->back()->withError('error',' FILE NOT FOUND !')->withInput();
				}
			} else {
				return redirect()->back()->withError('error',' REQUEST NOT FOUND !')->withInput();
			}
        }
    }
}