<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\pdf_jpg;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\PdfjpgTask;
use Spatie\PdfToImage\Pdf;

class pdftojpgController extends Controller
{
    public function image(){
		return view('pdftojpg');
	}

    public function pdf_image(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25000',
            'fileAlt' => ''
		]);

        if($validator->fails()) {
            return redirect('pdftojpg')->withErrors($validator->messages())->withInput();
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
								return redirect('pdftojpg')->with('upload','/'.'thumbnail/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect('pdftojpg')->withError('error',' has failed to upload !')->withInput();
							}
						} else {
							return redirect('pdftojpg')->withError('error',' has failed to upload !')->withInput();
						}
					} else {
						return redirect('pdftojpg')->withError('error',' FILE NOT FOUND !')->withInput();
					}
				} else if ($request->post('formAction') == "convert") {
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
							return redirect('pdftojpg')->with('success',$download_pdf);
						} else {
							$download_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'-0001.jpg';
							if (file_exists($download_pdf)) {
								return redirect('pdftojpg')->with('success','temp'.'/'.$pdfNameWithoutExtension.'-0001.jpg');
							} else {
								return redirect('pdftojpg')->withError('error',' has failed to convert !')->withInput();
							}
						}
					} else {
						return redirect('pdftojpg')->withError('error',' REQUEST NOT FOUND !')->withInput();
					}
				} else {
					return redirect('pdftojpg')->withError('error',' FILE NOT FOUND !')->withInput();
				}
			} else {
				return redirect('pdftojpg')->withError('error',' REQUEST NOT FOUND !')->withInput();
			}
        }
    }
}