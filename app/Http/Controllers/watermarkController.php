<?php
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\WatermarkTask;
use App\Models\watermark_pdf;

class watermarkController extends Controller
{
	public function watermark(){
		return view('watermark');
	}

	public function pdf_watermark(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
			'file' => 'required|mimes:pdf|max:25000',
			'watermarkText' => 'required',
			'watermarkPage' => 'required',
		]);
 
		if($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
			$file = $request->file('file');
			$watermarkText = $request->post('watermarkText');
			$watermarkPage = $request->post('watermarkPage');

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

			$pdfUpload_Location = 'upload-pdf';
			$pdfProcessed_Location = 'temp';
			$pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');
			$file->move($pdfUpload_Location,$file->getClientOriginalName());
			$fileSize = filesize($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$hostName = gethostname();
			$newFileSize = $this->convert($fileSize, "MB");

			watermark_pdf::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
				'hostName' => $hostName,
				'watermarkText' => $watermarkText,
				'watermarkPage' => $watermarkPage,
				'watermarkFontStyle' => $watermarkFontStyle,
				'watermarkFontSize' => $watermarkFontSize,
				'watermarkFontTransparency' => $watermarkFontTransparency
			]);

			$ilovepdfTask = new WatermarkTask('project_public_325d386bc0c634a66ce67d65413fe30c_GE-Cv2861de258f64776f2928e69cb4868675','secret_key_a704c544b92db47bc422a824c6b3004e_QZVE20e592b1888ab4c21fca2f1b170b20f8b');
			$pdfFile = $ilovepdfTask->addFile($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$ilovepdfTask->setMode("text");
			$ilovepdfTask->setText($watermarkText);
			$ilovepdfTask->setPages($watermarkPage);
			$ilovepdfTask->setVerticalPosition("middle");
			$ilovepdfTask->setHorizontalPosition("center");
			$ilovepdfTask->setFontFamily("Arial");
			$ilovepdfTask->setFontStyle($watermarkFontStyle);
			$ilovepdfTask->setFontSize($watermarkFontSize);
			$ilovepdfTask->setTransparency($watermarkFontTransparency);
			$ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
			$ilovepdfTask->execute();
			$ilovepdfTask->download($pdfProcessed_Location);
			
			$download_pdf = $pdfProcessed_Location.'/'.$file->getClientOriginalName();
			
			if (file_exists($download_pdf)) {
				return redirect()->back()->with('success',$download_pdf);
			} else {
				return redirect()->back()->withError('error',' has failed to watermark !')->withInput();
			}
		}
    }

	function convert($size,$unit) 
	{
		if($unit == "KB")
		{
			return $fileSize = number_format(round($size / 1024,4), 2) . ' KB';	
		}
		if($unit == "MB")
		{
			return $fileSize = number_format(round($size / 1024 / 1024,4), 2) . ' MB';	
		}
		if($unit == "GB")
		{
			return $fileSize = number_format(round($size / 1024 / 1024 / 1024,4), 2) . ' GB';	
		}
	}
}