<?php
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\pdf_word;

class pdftowordController extends Controller
{
    public function word(){
		return view('pdftoword');
	}

    public function pdf_word(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
			'file' => 'required|mimes:pdf|max:25000',
		]);

        if($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
            $file = $request->file('file');
            $pdfUpload_Location = 'upload-pdf';
            $pdfProcessed_Location = 'temp';
            $file->move($pdfUpload_Location,'convert_docx.pdf');
            $pdfFilename = pathinfo($pdfUpload_Location.'/'.'convert_docx.pdf');
            $fileSize = filesize($pdfUpload_Location.'/'.'convert_docx.pdf');
			$hostName = gethostname();
			$newFileSize = $this->convert($fileSize, "MB");
    
            pdf_word::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
				'hostName' => $hostName
			]);

            $pythonScripts = escapeshellcmd('C:\Users\Nickl\AppData\Local\Programs\Python\Python310\python.exe ext-python\pdftoword.py');
            $pythonRun = shell_exec($pythonScripts);
            if (file_exists($pdfProcessed_Location.'/converted.docx')) {
                $download_word = $pdfProcessed_Location.'/converted.docx';
                return redirect()->back()->with('success',$download_word);
            } else {
                return redirect()->back()->withError('error',' has failed to convert !')->withInput();
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