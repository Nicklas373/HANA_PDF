<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
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
			$newFileSize = AppHelper::instance()->convert($fileSize, "MB");

			compression_pdf::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
				'compMethod' => $compMethod,
				'hostName' => $hostName
			]);

			$ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
			$ilovepdfTask = $ilovepdf->newTask('compress');
			$ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
			$pdfFile = $ilovepdfTask->addFile($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$ilovepdfTask->setOutputFileName($file->getClientOriginalName());
			$ilovepdfTask->setCompressionLevel($compMethod);
			$ilovepdfTask->execute();
			$ilovepdfTask->download($pdfProcessed_Location);
			
			$download_pdf = $pdfProcessed_Location.'/'.$file->getClientOriginalName();
			
			if(is_file($pdfUpload_Location.'/'.$file->getClientOriginalName())) {
				unlink($pdfUpload_Location.'/'.$file->getClientOriginalName());
			}

			if (file_exists($download_pdf)) {
				return redirect()->back()->with('success',$download_pdf);
			} else {
                return redirect()->back()->withError('error',' has failed to compress !')->withInput();
			}
		}
	}
}