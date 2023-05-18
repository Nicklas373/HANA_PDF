<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\watermark_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\WatermarkTask;
use Spatie\PdfToImage\Pdf;

class watermarkController extends Controller
{
	public function watermark(){
		return view('watermark');
	}

	public function pdf_watermark(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25000',
			'fileAlt' => '',
			'wmfile' => 'mimes:jpg,png,jpeg|max:5000',
			'watermarkText' => '',
			'watermarkPage' => '',
			'wmType' => '',
		]);
 
		if($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
						$pdfUpload_Location = env('pdf_upload');
						$file = $request->file('file');
						$file->move($pdfUpload_Location,$file->getClientOriginalName());
						$pdfFileName = $pdfUpload_Location.'/'.$file->getClientOriginalName();
						$pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');

						if (file_exists($pdfFileName)) {
							$pdf = new Pdf($pdfFileName);
							$pdf->setPage(1)
								->setOutputFormat('png')
								->width(400)
								->saveImage(env('pdf_thumbnail'));
							if (file_exists(env('pdf_thumbnail').'/1.png')) {
								$thumbnail = file(env('pdf_thumbnail').'/1.png');
								rename(env('pdf_thumbnail').'/1.png', env('pdf_thumbnail').'/'.$pdfNameWithoutExtension.'.png');
								return redirect()->back()->with('upload','/'.env('pdf_thumbnail').'/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect()->back()->withError('error',' has failed to upload !')->withInput();
							}
						} else {
							return redirect()->back()->withError('error',' has failed to upload !')->withInput();
						}
					} else {
						return redirect()->back()->withError('error',' FILE NOT FOUND !')->withInput();
					}
				} else if ($request->post('formAction') == "watermark") {
					if(isset($_POST['fileAlt'])) {
						if(isset($_POST['wmType']))
						{
							$watermarkStyle = $request->post('wmType');
						} else {
							$watermarkStyle = '';
						}
						
						if(isset($_POST['watermarkFontFamily']))
						{
							$watermarkFontFamily = $request->post('watermarkFontFamily');
						} else {
							$watermarkFontFamily = '';
						}

						if(isset($_POST['watermarkFontStyle']))
						{
							$watermarkFontStyle = $request->post('watermarkFontStyle');
						} else {
							$watermarkFontStyle = '';
						}

						if(isset($_POST['watermarkFontSize']))
						{
							$watermarkFontSize = $request->post('watermarkFontSize');
						} else {
							$watermarkFontSize = '';
						}

						if(isset($_POST['watermarkFontTransparency']))
						{
							$watermarkFontTransparency = $request->post('watermarkFontTransparency');
						} else {
							$watermarkFontTransparency = '';
						}

						if(isset($_POST['watermarkLayoutStyle']))
						{
							$watermarkLayoutStyle = $request->post('watermarkLayoutStyle');
						} else {
							$watermarkLayoutStyle = '';
						}

						$pdfUpload_Location = env('pdf_upload');
						$file = $request->post('fileAlt');
						$watermarkText = $request->post('watermarkText');
						$watermarkPage = $request->post('watermarkPage');
						$pdfProcessed_Location = 'temp';
						$pdfName = basename($file);
						$pdfNameWithoutExtension = basename($file, ".pdf");
						$fileSize = filesize($file);
						$hostName = AppHelper::instance()->getUserIpAddr();
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
			
						watermark_pdf::create([
							'fileName' => basename($file),
							'fileSize' => $newFileSize,
							'hostName' => $hostName,
							'watermarkText' => $watermarkText,
							'watermarkPage' => $watermarkPage,
							'watermarkFontStyle' => $watermarkFontStyle,
							'watermarkFontSize' => $watermarkFontSize,
							'watermarkFontTransparency' => $watermarkFontTransparency
						]);

						if($watermarkStyle == "image") {
							if($request->hasfile('wmfile')) {
								$watermarkImage = $request->file('wmfile');
								$watermarkImage->move($pdfUpload_Location,$watermarkImage->getClientOriginalName());

								$ilovepdfTask = new WatermarkTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
								$ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
								$pdfFile = $ilovepdfTask->addFile($request->post('fileAlt'));
								$wmImage = $ilovepdfTask->addElementFile($pdfUpload_Location.'/'.$watermarkImage->getClientOriginalName());
								$ilovepdfTask->setMode("image");
								$ilovepdfTask->setImageFile($wmImage);
								$ilovepdfTask->setTransparency($watermarkFontTransparency);
								$ilovepdfTask->setLayer($watermarkLayoutStyle);
								$ilovepdfTask->execute();
								$ilovepdfTask->download($pdfProcessed_Location);
							} else {
								return redirect()->back()->withError('error',' FILE NOT FOUND !')->withInput();
							}
						} else if ($watermarkStyle == "text") {
							$ilovepdfTask = new WatermarkTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
							$ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
							$pdfFile = $ilovepdfTask->addFile($request->post('fileAlt'));
							$ilovepdfTask->setMode("text");
							$ilovepdfTask->setText($watermarkText);
							$ilovepdfTask->setPages($watermarkPage);
							$ilovepdfTask->setVerticalPosition("middle");
							$ilovepdfTask->setRotation(45);
							$ilovepdfTask->setFontColor('#ffffff');
							$ilovepdfTask->setFontFamily($watermarkFontFamily);
							$ilovepdfTask->setFontStyle($watermarkFontStyle);
							$ilovepdfTask->setFontSize($watermarkFontSize);
							$ilovepdfTask->setTransparency($watermarkFontTransparency);
							$ilovepdfTask->setLayer($watermarkLayoutStyle);
							$ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
							$ilovepdfTask->execute();
							$ilovepdfTask->download($pdfProcessed_Location);
						}

						$download_pdf = $pdfProcessed_Location.'/'.$pdfName;
						
						if(is_file($request->post('fileAlt'))) {
							unlink($request->post('fileAlt'));
						}
			
						if (file_exists($download_pdf)) {
							return redirect()->back()->with('success',$download_pdf);
						} else {
							return redirect()->back()->withError('error',' has failed to watermark !')->withInput();
						}
					} else {
						return redirect()->back()->withError('error',' FILE NOT FOUND !')->withInput();
					}
				} else {
					return redirect()->back()->withError('error',' REQUEST NOT FOUND !')->withInput();
				}
			} else {
				return redirect()->back()->withError('error',' REQUEST NOT FOUND !')->withInput();
			}
		}
    }
}