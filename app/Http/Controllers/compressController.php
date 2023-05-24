<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\compression_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Spatie\PdfToImage\Pdf;

class compressController extends Controller
{
	public function compress(){
		return view('compress');
	}

	public function pdf_init(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25000',
			'fileAlt' => ''
		]);
 
		if($validator->fails()) {
			return redirect('compress')->withErrors($validator->messages())->withInput();
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
							if (file_exists(public_path('thumbnail'))) {
								$thumbnail = file(public_path('thumbnail').'/1.png');
								rename(public_path('thumbnail').'/1.png', public_path('thumbnail').'/'.$pdfNameWithoutExtension.'.png');
								return redirect('compress')->with('upload','thumbnail/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect('compress')->withError('error',' has failed to upload !')->withInput();
							}
						} else {
							return redirect('compress')->withError('error',' has failed to upload !')->withInput();
						}
					} else {
						return redirect('compress')->withError('error',' FILE NOT FOUND !')->withInput();
					}
				} else if ($request->post('formAction') == "compress") {
					if(isset($_POST['fileAlt'])) {
						if(isset($_POST['compMethod']))
						{
							$compMethod = $request->post('compMethod');
						} else {
							$compMethod = "recommended";
						}
			
			            $pdfFileLoc = 'public/'.$request->post('fileAlt');
						$pdfProcessed_Location = public_path('temp');
						$pdfName = basename($pdfFileLoc);
						$pdfNameWithoutExtension = basename($pdfFileLoc);
						$fileSize = filesize($pdfFileLoc);
						$hostName = AppHelper::instance()->getUserIpAddr();
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
			
						compression_pdf::create([
							'fileName' => $pdfName,
							'fileSize' => $newFileSize,
							'compMethod' => $compMethod,
							'hostName' => $hostName
						]);
			
						$ilovepdf = new Ilovepdf('project_public_0ba8067b84cb4d4582b8eac3aa0591b2_XwmRS824bc5681a3ca4955a992dde44da6ac1','secret_key_937ea5acab5e22f54c6c7601fd7866dc_jT3DA5ed31082177f48cd792801dcf664c41b');
						$ilovepdfTask = $ilovepdf->newTask('compress');
						$ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
						$pdfFile = $ilovepdfTask->addFile($pdfFileLoc);
						$ilovepdfTask->setOutputFileName($pdfName);
						$ilovepdfTask->setCompressionLevel($compMethod);
						$ilovepdfTask->execute();
						$ilovepdfTask->download($pdfProcessed_Location);
						
						$download_pdf = $pdfProcessed_Location.'/'.$pdfName;
						
						if(is_file($pdfFileLoc)) {
							unlink($pdfFileLoc);
						}
			
						if (file_exists($download_pdf)) {
							return redirect('compress')->with('success','temp/'.$pdfName);
						} else {
							return redirect('compress')->withError('error',' has failed to compress !')->withInput();
						}
					} else {
						return redirect('compress')->withError('error',' REQUEST NOT FOUND !')->withInput();
					}
				} else {
					return redirect('compress')->withError('error',' FILE NOT FOUND !')->withInput();
				}
			} else {
				return redirect('compress')->withError('error',' REQUEST NOT FOUND !')->withInput();
			}
		}
	}
}