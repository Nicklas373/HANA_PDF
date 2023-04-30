<?php
 
namespace App\Http\Controllers;

use App\Models\File;
use App\Helpers\AppHelper;
use App\Models\merge_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;

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
                $fileSizeArray = AppHelper::instance()->folderSize(public_path('temp-merge'));
                $fileSizeInMB = AppHelper::instance()->convert($fileSizeArray, "MB");
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

                $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                $ilovepdfTask = $ilovepdf->newTask('merge');
                $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
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
    
                if(is_file($pdfUpload_Location.'/'.$file->getClientOriginalName())) {
                    unlink($pdfUpload_Location.'/'.$file->getClientOriginalName());
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
}