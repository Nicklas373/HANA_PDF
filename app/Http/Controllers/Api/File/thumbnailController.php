<?php

namespace App\Http\Controllers\Api\File;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use Spatie\PdfToImage\Pdf;
use Mpdf\Mpdf;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class thumbnailController extends Controller
{
    public function getThumbnail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->returnFileMesage(
                400,
                'Validation failed',
                null,
                $validator->messages()->first()
            );
        } else {
            if ($request->has('file')) {
                $files = $request->post('file');
                $pdfThumbnail_Location = env('PDF_IMG_POOL');
                $pdfUpload_Location = env('PDF_UPLOAD');
                $pdfPool_Location = env('PDF_POOL');
                $currentFileName = basename($files);
                $pdfFileName = str_replace(' ', '_', $currentFileName);
                $pdfRealExtension = pathinfo($pdfFileName, PATHINFO_EXTENSION);
                $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfFileName);
                $thumbnailFilePath =  Storage::disk('local')->path('public/'.$pdfThumbnail_Location.'/'.$pdfFileName.'.png');
                try {
                    Settings::setPdfRendererPath(base_path('vendor/mpdf/mpdf'));
                    Settings::setPdfRendererName('MPDF');

                    $pdfPath = Storage::disk('local')->path('public/'.$pdfPool_Location.'/'.$pdfFileName);
                    if ($pdfRealExtension == 'docx' || $pdfRealExtension == 'doc') {
                        $phpWord = WordIOFactory::load($newFilePath);
                        $phpWord->save($pdfPath, 'PDF');
                    } else if ($pdfRealExtension == 'xls' || $pdfRealExtension  == 'xlsx') {
                        $phpXlsx = SpreadsheetIOFactory::load($newFilePath);
                        $phpXlsx->setActiveSheetIndex(0);
                        $phpXlsxWriter = SpreadsheetIOFactory::createWriter($phpXlsx, 'Mpdf');
                        $phpXlsxWriter->save($pdfPath);
                    } else {
                        return $this->returnFileMesage(
                            400,
                            'Failed to generate thumbnail !',
                            $pdfFileName,
                            'Invalid or unsupported file extension: '.$pdfRealExtension
                        );
                    }
                    $pdf = new Pdf($pdfPath);
                    $pdf->selectPage(1)
                        ->format(\Spatie\PdfToImage\Enums\OutputFormat::Png)
                        ->quality(90)
                        ->save($thumbnailFilePath);
                    return $this->returnFileMesage(
                        201,
                        'Thumbnail generated !',
                        Storage::disk('local')->url(env('PDF_IMG_POOL').'/'.$pdfFileName.'.png'),
                        $pdfFileName
                    );
                } catch (Exception $e) {
                    return $this->returnFileMesage(
                        500,
                        'Failed to generate thumbnail !',
                        $pdfFileName,
                        $pdfFileName.' could not generate thumbnail with error: '.$e->getMessage()
                    );
                }
            }
        }
    }
}
