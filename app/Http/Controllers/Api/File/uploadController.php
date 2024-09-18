<?php

namespace App\Http\Controllers\Api\File;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\PdfToImage\Pdf;

class uploadController extends Controller
{
    public function upload(Request $request) {
		$validator = Validator::make($request->all(),[
			'file' => 'required|mimes:pdf,pptx,docx,xlsx,jpg,png,jpeg|max:25600',
			'fileAlt' => ''
		]);

		if ($validator->fails()) {
            return $this->returnFileMesage(
                401,
                'Validation failed',
                null,
                $validator->messages()->first()
            );
		} else {
			if($request->hasfile('file')) {
                $str = rand(1000,10000000);
                $pdfUpload_Location = env('PDF_UPLOAD');
                $file = $request->file('file');
                $pdfName = $file->getClientOriginalName();
                $currentFileName = basename($pdfName);
                $pdfFileName = str_replace(' ', '_', $currentFileName);
                $fileSize = filesize($file);
                $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                $file->storeAs('public/upload', $pdfFileName);
                if (Storage::disk('local')->exists('public/'.$pdfUpload_Location.'/'.$pdfFileName)) {
                    return $this->returnFileMesage(
                        201,
                        'File uploaded successfully !',
                        Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$pdfFileName),
                        null
                    );
                } else {
                    return $this->returnFileMesage(
                        400,
                        'Failed to upload file !',
                        Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfFileName),
                        $pdfFileName.' could not be found in the server'
                    );
                }
            } else {
                return $this->returnFileMesage(
                    400,
                    'Failed to upload file !',
                    null,
                    'Requested file could not be found in the server'
                );
            }
        }
    }

    public function remove(Request $request) {
		$validator = Validator::make($request->all(),[
			'file' => ''
		]);

		if ($validator->fails()) {
            return $this->returnFileMesage(
                401,
                'Validation failed',
                null,
                $validator->messages()->first()
            );
		} else {
			if($request->has('file')) {
                $pdfUpload_Location = env('PDF_UPLOAD');
                $file = $request->input('file');
                $pdfName = basename($file);
                $currentFileName = basename($pdfName);
                $pdfFileName = str_replace(' ', '_', $currentFileName);
                $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfFileName);
                if (file_exists($pdfNewPath)) {
                    unlink($pdfNewPath);
                    return $this->returnFileMesage(
                        200,
                        'File removed successfully !',
                        Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$pdfFileName),
                        null
                    );
                } else {
                    return $this->returnFileMesage(
                        400,
                        'Failed to remove file !',
                        $pdfFileName,
                        $pdfFileName.' could not be found in the server'
                    );
                }
            } else {
                return $this->returnFileMesage(
                    400,
                    'Failed to remove file !',
                    null,
                    'Requested file could not be found in the server'
                );
            }
        }
    }

    public function getTotalPagesPDF(Request $request) {
		$validator = Validator::make($request->all(),[
			'fileName' => 'required'
		]);

		if ($validator->fails()) {
            return $this->returnFileMesage(
                401,
                'Validation failed',
                null,
                $validator->messages()->first()
            );
		} else {
			if($request->has('fileName')) {
                $pdfName = $request->post('fileName');
                $currentFileName = basename($pdfName);
                $currentFileNameExtension = pathinfo($currentFileName, PATHINFO_EXTENSION);
                $pdfUpload_Location = env('PDF_UPLOAD');
                $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName);
                if (file_exists($newFilePath)) {
                    if ($currentFileNameExtension == 'pdf') {
                        try {
                            $pdf = new Pdf($pdfNewPath);
                            $pdfTotalPages = $pdf->pageCount();
                            return $this->returnDataMesage(
                                200,
                                'PDF Page successfully counted',
                                $pdfTotalPages,
                                null,
                                null
                            );
                        } catch (\Exception $e) {
                            return $this->returnDataMesage(
                                400,
                                'Failed to count total PDF pages from '.$currentFileName,
                                null,
                                null,
                                $e->getMessage()
                            );
                        }
                    } else {
                        return $this->returnDataMesage(
                            400,
                            'File '.$currentFileName.' is not PDF file !',
                            null,
                            null,
                            'FILE_FORMAT_VALIDATION_EXCEPTION'
                        );
                    }
                } else {
                    return $this->returnDataMesage(
                        400,
                        'File '.$currentFileName.' not found !',
                        null,
                        null,
                        'FILE_NOT_FOUND_EXCEPTION'
                    );
                }
            } else {
                return $this->returnFileMesage(
                    400,
                    'Failed to upload file !',
                    null,
                    'Requested file could not be found in the server'
                );
            }
        }
    }
}
