<?php

namespace App\Http\Controllers\main;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class uploadController extends Controller
{
    public function upload(Request $request) {
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf,pptx,docx,xlsx,jpg,png,jpeg,tiff|max:25600',
			'fileAlt' => ''
		]);

		if ($validator->fails()) {
            return response()->json([
                'status' => 401,
                'message' => 'Validation failed !',
                'error' => $validator->messages()->first(),
                'fileLocation' => null,
                'fileName' => null,
                'processId' => null
            ], 401);
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
                    return response()->json([
                        'status' => 200,
                        'message' => 'OK',
                        'error' => null,
                        'fileLocation' => Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$pdfFileName),
                        'fileName' => $pdfFileName,
                        'processId' => null
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Failed to upload file !',
                        'error' => $pdfFileName.' could not be found in the server',
                        'fileLocation' => null,
                        'fileName' => null,
                        'processId' => null
                    ], 400);
                }
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => 'Failed to upload file !',
                    'error' => 'Requested file coult not be found',
                    'fileLocation' => null,
                    'fileName' => null,
                    'processId' => null
                ], 400);
            }
        }
    }

    public function remove(Request $request) {
		$validator = Validator::make($request->all(),[
			'file' => ''
		]);

		if ($validator->fails()) {
            return response()->json([
                'status' => 401,
                'message' => 'Validation failed !',
                'error' => $validator->messages()->first(),
                'fileLocation' => null,
                'fileName' => null,
                'processId' => null
            ], 401);
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
                    return response()->json([
                        'status' => 200,
                        'message' => 'OK',
                        'error' => null,
                        'fileLocation' => Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$pdfFileName),
                        'fileName' => $pdfFileName,
                        'processId' => null
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Failed to remove file !',
                        'error' => $pdfFileName.' could not be found in the server',
                        'fileLocation' => null,
                        'fileName' => null,
                        'processId' => null
                    ], 400);
                }
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => 'Failed to upload file !',
                    'error' => 'Requested file coult not be found',
                    'fileLocation' => null,
                    'fileName' => null,
                    'processId' => null
                ], 400);
            }
        }
    }
}
