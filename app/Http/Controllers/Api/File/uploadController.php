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
                400,
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
                Storage::disk('minio')->put($pdfUpload_Location.'/'.$pdfFileName, file_get_contents($file));
                try {
                    if (Storage::disk('minio')->exists($pdfUpload_Location.'/'.$pdfFileName)) {
                        return $this->returnFileMesage(
                            201,
                            'File uploaded successfully !',
                            Storage::disk('minio')->exists($pdfUpload_Location.'/'.$pdfFileName),
                            null
                        );
                    } else {
                        return $this->returnFileMesage(
                            400,
                            'Failed to upload file !',
                            $pdfFileName,
                            $pdfFileName.' could not be found in the object storage'
                        );
                    }
                } catch (\Exception $e) {
                    return $this->returnFileMesage(
                        400,
                        'Failed to upload file !',
                        $pdfFileName,
                        $e->getMessage()
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
                400,
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
                }
                try {
                    if (Storage::disk('minio')->exists($pdfUpload_Location.'/'.$pdfFileName)) {
                        Storage::disk('minio')->delete($pdfUpload_Location.'/'.$pdfFileName);
                        return $this->returnFileMesage(
                            200,
                            'File removed successfully !',
                            Storage::disk('minio')->exists($pdfUpload_Location.'/'.$pdfFileName),
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
                } catch (Exception $e) {
                    return $this->returnFileMesage(
                        400,
                        'Failed to remove file !',
                        $pdfFileName,
                        $e->getMessage()
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
                400,
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
                if (Storage::disk('minio')->exists($pdfUpload_Location.'/'.$currentFileName)) {
                    if ($currentFileNameExtension == 'pdf') {
                        $minioUpload = Storage::disk('minio')->get($pdfUpload_Location.'/'.$currentFileName);
                        file_put_contents(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName), $minioUpload);
                        $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$currentFileName);
                        try {
                            $pdf = new Pdf($newFilePath);
                            $pdfTotalPages = $pdf->pageCount();
                            Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$currentFileName);
                            return $this->returnDataMesage(
                                200,
                                'PDF Page successfully counted',
                                $pdfTotalPages,
                                null,
                                null,
                                null
                            );
                        } catch (\Exception $e) {
                            return $this->returnDataMesage(
                                400,
                                'Failed to count total PDF pages from '.$currentFileName,
                                null,
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
