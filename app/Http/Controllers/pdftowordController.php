<?php
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\pdf_word;
use Aspose\Words\WordsApi;
use Aspose\Words\Model\Requests\{SaveAsRequest, UploadFileRequest};
use Aspose\Words\Model\{DocxSaveOptionsData};

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
            $file->move($pdfUpload_Location,$file->getClientOriginalName());
            $pdfFilename = pathinfo($pdfUpload_Location.'/'.$file->getClientOriginalName());
            $fileSize = filesize($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$hostName = gethostname();
			$newFileSize = $this->convert($fileSize, "MB");
    
            pdf_word::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
				'hostName' => $hostName
			]);

            $clientId= '73751f49-388b-4366-aeb4-d76587d5123e';
            $token = '1792ea481716ff7788b276c8c88df6b8';
            
            $wordsApi = new WordsApi($clientId, $token);
            $uploadFileRequest = new UploadFileRequest($pdfUpload_Location.'/'.$file->getClientOriginalName(), $file->getClientOriginalName());
            $wordsApi->uploadFile($uploadFileRequest);
            $requestSaveOptionsData = new DocxSaveOptionsData(array(
                "save_format" => "docx",
                "file_name" => 'EMSITPRO-PDFTools/Completed/'.$pdfNameWithoutExtension.".docx",
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
                echo "Success !";
            } else {
                echo "Error !";
            }

            if (file_exists($pdfProcessed_Location.'/converted.docx')) {
                $download_word = 'https://drive.google.com/drive/folders/1D3YicPoJDk595tVw01NUyx_Osf3Q2Ca8?usp=sharing';
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