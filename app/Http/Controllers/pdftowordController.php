<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\pdf_word;
use Aspose\Words\WordsApi;
use Aspose\Words\Model\Requests\{SaveAsRequest, UploadFileRequest};
use Aspose\Words\Model\{DocxSaveOptionsData};
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

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
            $pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');
            $file->move($pdfUpload_Location,$file->getClientOriginalName());
            $pdfFilename = pathinfo($pdfUpload_Location.'/'.$file->getClientOriginalName());
            $fileSize = filesize($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$hostName = gethostname();
			$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
    
            pdf_word::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
				'hostName' => $hostName
			]);
            
            $wordsApi = new WordsApi(env('ASPOSE_CLOUD_CLIENT_ID'), env('ASPOSE_CLOUD_TOKEN'));
            $uploadFileRequest = new UploadFileRequest($pdfUpload_Location.'/'.$file->getClientOriginalName(), $file->getClientOriginalName());
            $wordsApi->uploadFile($uploadFileRequest);
            $requestSaveOptionsData = new DocxSaveOptionsData(array(
                "save_format" => "docx",
                "file_name" => env('ASPOSE_CLOUD_STORAGE_COMPLETED_DIR').$pdfNameWithoutExtension.".docx",
            ));

            $request = new SaveAsRequest(
                $file->getClientOriginalName(),
                $requestSaveOptionsData,
                NULL,
                NULL,
                NULL,
                NULL
            );
            $result = $wordsApi->saveAs($request);

            if (json_decode($result, true) !== NULL) {
                $download_word = env('ASPOSE_CLOUD_STORAGE_COMPLETED_LINK');
                return redirect()->back()->with('success',$download_word);
            } else {
                return redirect()->back()->withError('error',' has failed to convert !')->withInput();
            }
        }
    }
}