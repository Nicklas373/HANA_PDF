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
            return redirect('watermark')->withErrors($validator->messages())->withInput();
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
								rename(public_path('thumbnail').'/1.png', env('pdf_thumbnail').'/'.$pdfNameWithoutExtension.'.png');
								return redirect('watermark')->with('upload','thumbnail/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect('watermark')->withError('error',' has failed to upload !')->withInput();
							}
						} else {
							return redirect('watermark')->withError('error',' has failed to upload !')->withInput();
						}
					} else {
						return redirect('watermark')->withError('error',' FILE NOT FOUND !')->withInput();
					}
				} else if ($request->post('formAction') == "watermark") {
					if(isset($_POST['fileAlt'])) {
						if(isset($_POST['isMosaic']))
						{
							$tempPDF = $request->post('isMosaic');
							$tempCompare = $tempPDF ? 'true' : 'false';
							$isMosaicDB = "true";
							$isMosaic = filter_var($tempCompare, FILTER_VALIDATE_BOOLEAN);
						} else {
							$tempCompare = false ? 'true' : 'false';
							$isMosaicDB = "false";
							$isMosaic = filter_var($tempCompare, FILTER_VALIDATE_BOOLEAN);
						}

						if(isset($_POST['watermarkFontFamily']))
						{
							$watermarkFontFamily = $request->post('watermarkFontFamily');
						} else {
							$watermarkFontFamily = '';
						}

						if(isset($_POST['watermarkFontSize']))
						{
							$watermarkFontSize = $request->post('watermarkFontSize');
						} else {
							$watermarkFontSize = '';
						}

						if(isset($_POST['watermarkFontStyle']))
						{
							$watermarkFontStyle = $request->post('watermarkFontStyle');
						} else {
							$watermarkFontStyle = '';
						}

						if(isset($_POST['watermarkFontTransparency']))
						{
							$watermarkFontTransparencyTemp = $request->post('watermarkFontTransparency');
							$watermarkFontTransparency = 100 - intval($watermarkFontTransparencyTemp);
						} else {
							$watermarkFontTransparency = '';
						}

						if(isset($_POST['watermarkLayoutStyle']))
						{
							$watermarkLayoutStyle = $request->post('watermarkLayoutStyle');
						} else {
							$watermarkLayoutStyle = '';
						}

						if(isset($_POST['watermarkPage']))
						{
							$watermarkPage = $request->post('watermarkPage');
						} else {
							$watermarkPage = '';
						}

						if(isset($_POST['watermarkRotation']))
						{
							$watermarkRotation = $request->post('watermarkRotation');
						} else {
							$watermarkRotation = '';
						}

						if(isset($_POST['watermarkText']))
						{
							$watermarkText = $request->post('watermarkText');
						} else {
							$watermarkText = '';
						}

						if($request->hasfile('wmfile')) {
							$watermarkImage = $request->file('wmfile');
						} else {
							$watermarkImage = '';
						}

						if(isset($_POST['wmType']))
						{
							$watermarkStyle = $request->post('wmType');
						} else {
							$watermarkStyle = '';
						}

						$pdfUpload_Location =  public_path('upload-pdf');
						$file = $request->post('fileAlt');
						$pdfProcessed_Location = public_path('temp');
						$pdfName = basename($file);
						$pdfNameWithoutExtension = basename($file, ".pdf");
						$fileSize = filesize($file);
						$hostName = AppHelper::instance()->getUserIpAddr();
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
			
						watermark_pdf::create([
							'fileName' => basename($file),
							'fileSize' => $newFileSize,
							'hostName' => $hostName,
							'watermarkFontFamily' => $watermarkFontFamily,
							'watermarkFontStyle' => $watermarkFontStyle,
							'watermarkFontSize' => $watermarkFontSize,
							'watermarkFontTransparency' => $watermarkFontTransparency,
							'watermarkImage' => basename($watermarkImage),
							'watermarkLayout' => $watermarkLayoutStyle,
							'watermarkMosaic' => $isMosaicDB,
							'watermarkRotation' => $watermarkRotation,
							'watermarkStyle' => $watermarkStyle,
							'watermarkText' => $watermarkText,
							'watermarkPage' => $watermarkPage
						]);

						if($watermarkStyle == "image") {
							if($request->hasfile('wmfile')) {
								$watermarkImage = $request->file('wmfile');
								$watermarkImage->move($pdfUpload_Location,$watermarkImage->getClientOriginalName());
								$ilovepdfTask = new WatermarkTask('project_public_0ba8067b84cb4d4582b8eac3aa0591b2_XwmRS824bc5681a3ca4955a992dde44da6ac1','secret_key_937ea5acab5e22f54c6c7601fd7866dc_jT3DA5ed31082177f48cd792801dcf664c41b');
								$ilovepdfTask->setFileEncryption('XrPiOcvugxyGrJnX');
								$pdfFile = $ilovepdfTask->addFile($file);
								$wmImage = $ilovepdfTask->addElementFile($pdfUpload_Location.'/'.$watermarkImage->getClientOriginalName());
								$ilovepdfTask->setMode("image");
								$ilovepdfTask->setImageFile($wmImage);
								$ilovepdfTask->setTransparency(intval($watermarkFontTransparency));
								$ilovepdfTask->setRotation($watermarkRotation);
								$ilovepdfTask->setLayer($watermarkLayoutStyle);
								$ilovepdfTask->setMosaic($isMosaic);
								$ilovepdfTask->execute();
								$ilovepdfTask->download($pdfProcessed_Location);
							} else {
								return redirect('watermark')->withError('error',' FILE NOT FOUND !')->withInput();
							}
						} else if ($watermarkStyle == "text") {
							$ilovepdfTask = new WatermarkTask('project_public_0ba8067b84cb4d4582b8eac3aa0591b2_XwmRS824bc5681a3ca4955a992dde44da6ac1','secret_key_937ea5acab5e22f54c6c7601fd7866dc_jT3DA5ed31082177f48cd792801dcf664c41b');
								$ilovepdfTask->setFileEncryption('XrPiOcvugxyGrJnX');
							$pdfFile = $ilovepdfTask->addFile($file);
							$ilovepdfTask->setMode("text");
							$ilovepdfTask->setText($watermarkText);
							$ilovepdfTask->setPages($watermarkPage);
							$ilovepdfTask->setVerticalPosition("middle");
							$ilovepdfTask->setRotation($watermarkRotation);
							$ilovepdfTask->setFontColor('#000000');
							$ilovepdfTask->setFontFamily($watermarkFontFamily);
							$ilovepdfTask->setFontStyle($watermarkFontStyle);
							$ilovepdfTask->setFontSize($watermarkFontSize);
							$ilovepdfTask->setTransparency($watermarkFontTransparency);
							$ilovepdfTask->setLayer($watermarkLayoutStyle);
							$ilovepdfTask->setMosaic($isMosaic);
							$ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
							$ilovepdfTask->execute();
							$ilovepdfTask->download($pdfProcessed_Location);
						}

						$download_pdf = $pdfProcessed_Location.'/'.$pdfName;
						
						if(is_file($file)) {
							unlink($file);
						}
			
						if (file_exists($download_pdf)) {
							return redirect('watermark')->with('success','temp/'.$pdfName);
						} else {
							return redirect('watermark')->withError('error',' has failed to watermark !')->withInput();
						}
					} else {
						return redirect('watermark')->withError('error',' FILE NOT FOUND !')->withInput();
					}
				} else {
					return redirect('watermark')->withError('error',' REQUEST NOT FOUND !')->withInput();
				}
			} else {
				return redirect('watermark')->withError('error',' REQUEST NOT FOUND !')->withInput();
			}
		}
    }
}