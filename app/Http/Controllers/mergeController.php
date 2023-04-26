<?php
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use App\Models\File;
use App\Models\merge_pdf;

class mergeController extends Controller
{
    public function merge() {
        return view('merge');
    }

    public function pdf_merge(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
            'file' => 'required|max:25000',
        ]);

        if($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
            if ($request->hasfile('file')) { 
                $pdfArray = [];
                $pdfNameArray = array();
    
                foreach ($request->file('file') as $file) {
                    $filename = $file->getClientOriginalName();
                    $pdfNameArray[] = $filename;
                    $file->move(public_path('temp-merge'), $filename);
                }

                $fileNameArray = implode(', ', $pdfNameArray);
                $fileSizeArray = $this->folderSize(public_path('temp-merge'));
                $fileSizeInMB = $this->convert($fileSizeArray, "MB");
                $hostName = gethostname();
                $pdfArray = scandir(public_path('temp-merge'));
                $pdfStartPages = 1;
                $pdfPreProcessed_Location = 'temp-merge';
                $pdfProcessed_Location = 'temp';
    
                merge_pdf::create([
                    'fileName' => $fileNameArray,
                    'fileSize' => $fileSizeInMB,
                    'hostName' => $hostName
                ]);

                $ilovepdf = new Ilovepdf('project_public_325d386bc0c634a66ce67d65413fe30c_GE-Cv2861de258f64776f2928e69cb4868675','secret_key_a704c544b92db47bc422a824c6b3004e_QZVE20e592b1888ab4c21fca2f1b170b20f8b');
                $ilovepdfTask = $ilovepdf->newTask('merge');
                $ilovepdfTask->setFileEncryption('XrPiOcvugxyGrJnX');
                foreach($pdfArray as $value) {
                    if (strlen($value) >= 4) {
                        $arrayCount = 1;
                        $arrayOrder = strval($arrayCount);
                        $pdfName = $ilovepdfTask->addFile($pdfPreProcessed_Location.'/'.$value);
                        $arrayCount += 1;
                    }
                }
                $ilovepdfTask->execute();
                $ilovepdfTask->download($pdfProcessed_Location);
                $download_pdf = $pdfProcessed_Location.'/merged.pdf';
    
                $tempPDFfiles = glob($pdfPreProcessed_Location . '/*');
                foreach($tempPDFfiles as $file){
                    if(is_file($file)) {
                        unlink($file);
                    }
                }
    
                if (file_exists($download_pdf)) {
                    return redirect()->back()->with('success',$download_pdf);
                } else {
                    return redirect()->back()->withError('error',' has failed to merged !')->withInput();
                }
            } else {
                return redirect()->back()->withError('error',' has failed to merged !')->withInput();
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

    function folderSize($dir)
    {
        $size = 0;

        foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : folderSize($each);
        }

        return $size;
    }
}