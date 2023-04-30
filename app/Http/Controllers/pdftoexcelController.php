<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\pdf_excel;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

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
			$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
    
            pdf_excel::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
				'hostName' => $hostName
			]);

            if(is_file($pdfUpload_Location.'/'.$file->getClientOriginalName())) {
                unlink($pdfUpload_Location.'/'.$file->getClientOriginalName());
            }

            $pythonScripts = escapeshellcmd(env('PYTHON_EXECUTABLES').' ext-python\pdftoxlsx.py');
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
}