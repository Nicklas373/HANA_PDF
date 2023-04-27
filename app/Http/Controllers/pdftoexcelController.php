<?php
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\pdf_excel;

class pdftoexcelController extends Controller
{
    public function excel(){
		return view('pdftoexcel');
	}

    public function pdf_excel(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
			'file' => 'required|mimes:pdf|max:25000',
		]);

        if($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
            $file = $request->file('file');
            $pdfUpload_Location = 'upload-pdf';
            $pdfProcessed_Location = 'temp';
            $file->move($pdfUpload_Location,'convert_xlsx.pdf');
            $pdfFilename = pathinfo($pdfUpload_Location.'/convert_xlsx.pdf');
            $fileSize = filesize($pdfUpload_Location.'/convert_xlsx.pdf');
			$hostName = gethostname();
			$newFileSize = $this->convert($fileSize, "MB");
    
            pdf_excel::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
				'hostName' => $hostName
			]);

            $pythonScripts = escapeshellcmd('C:\Users\BCLKT\AppData\Local\Programs\Python\Python310\python.exe ext-python\pdftoxlsx.py');
            $pythonRun = shell_exec($pythonScripts);
            if ($pythonRun = "true") {
                if (file_exists($pdfProcessed_Location.'/converted.xlsx')) {
                    $download_excel = $pdfProcessed_Location.'/converted.xlsx';
                    return redirect()->back()->with('success',$download_excel);
                } else {
                    return redirect()->back()->withError('error',' has failed to convert !')->withInput();
                }
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