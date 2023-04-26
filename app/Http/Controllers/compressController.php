<?php
 
namespace App\Http\Controllers;

use App\Models\compression_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;

class compressController extends Controller
{
	public function compress(){
		return view('compress');
	}
 
	public function pdf_compression(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'required|mimes:pdf|max:25000',
			'compMethod' => 'required',
		]);
 
		if($validator->fails()) {
			return redirect()->back()->withErrors($validator->messages())->withInput();
		} else {
			$file = $request->file('file');
			if(isset($_POST['compMethod']))
			{
				$compMethod = $request->post('compMethod');
			} else {
				$compMethod = "recommended";
			}

			$pdfUpload_Location = 'upload-pdf';
			$pdfProcessed_Location = 'temp';
	
			$file->move($pdfUpload_Location,$file->getClientOriginalName());
			$fileSize = filesize($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$hostName = gethostname();
			$newFileSize = $this->convert($fileSize, "MB");

			compression_pdf::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
				'compMethod' => $compMethod,
				'hostName' => $hostName
			]);

			$ilovepdf = new Ilovepdf('project_public_325d386bc0c634a66ce67d65413fe30c_GE-Cv2861de258f64776f2928e69cb4868675','secret_key_a704c544b92db47bc422a824c6b3004e_QZVE20e592b1888ab4c21fca2f1b170b20f8b');
			$ilovepdfTask = $ilovepdf->newTask('compress');
			$ilovepdfTask->setFileEncryption('XrPiOcvugxyGrJnX');
			$pdfFile = $ilovepdfTask->addFile($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$ilovepdfTask->setOutputFileName($file->getClientOriginalName());
			$ilovepdfTask->setCompressionLevel($compMethod);
			$ilovepdfTask->execute();
			$ilovepdfTask->download($pdfProcessed_Location);
			
			$download_pdf = $pdfProcessed_Location.'/'.$file->getClientOriginalName();

			if (file_exists($download_pdf)) {
				return redirect()->back()->with('success',$download_pdf);
			} else {
                return redirect()->back()->withError('error',' has failed to compress !')->withInput();
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