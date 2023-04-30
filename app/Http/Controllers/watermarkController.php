<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\watermark_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\WatermarkTask;

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
			$newFileSize = AppHelper::instance()->convert($fileSize, "MB");

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

			$ilovepdfTask = new WatermarkTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
			$ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
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
			
			if(is_file($pdfUpload_Location.'/'.$file->getClientOriginalName())) {
				unlink($pdfUpload_Location.'/'.$file->getClientOriginalName());
			}

			if (file_exists($download_pdf)) {
				return redirect()->back()->with('success',$download_pdf);
			} else {
				return redirect()->back()->withError('error',' has failed to watermark !')->withInput();
			}
		}
    }
}