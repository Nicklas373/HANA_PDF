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
                $trimPhase1 = str_replace(' ', '_', $currentFileName);
                $newFileNameWithoutExtension = str_replace('.', '_', $trimPhase1);
                $pdfRealExtension = pathinfo($currentFileName, PATHINFO_EXTENSION);
                $pdfRealName = pathinfo($trimPhase1, PATHINFO_FILENAME);
                $newFormattedFilename = str_replace('_'.$pdfRealExtension, '', $newFileNameWithoutExtension);
                $minioUpload = Storage::disk('minio')->get($pdfUpload_Location.'/'.$trimPhase1);
                if (Storage::disk('local')->exists('public/'.$pdfUpload_Location.'/'.$newFormattedFilename.'.'.$pdfRealExtension)) {
                    Storage::disk('local')->delete('public/'.$pdfUpload_Location.'/'.$newFormattedFilename.'.'.$pdfRealExtension);
                }
                file_put_contents(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$newFormattedFilename.'.'.$pdfRealExtension), $minioUpload);
                $newFilePath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$newFormattedFilename.'.'.$pdfRealExtension);
                $thumbnailFilePath =  Storage::disk('local')->path('public/'.$pdfThumbnail_Location.'/'.$pdfRealName.'.png');
                try {
                    ini_set("pcre.backtrack_limit", "5000000");
                    Settings::setPdfRendererPath(base_path('vendor/mpdf/mpdf'));
                    Settings::setPdfRendererName('MPDF');

                    $pdfPath = Storage::disk('local')->path('public/'.$pdfPool_Location.'/'.$newFormattedFilename.'.'.$pdfRealExtension);
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
                            $pdfRealName,
                            'Invalid or unsupported file extension: '.$pdfRealExtension
                        );
                    }
                    $pdf = new Pdf($pdfPath);
                    $pdf->selectPage(1)
                        ->format(\Spatie\PdfToImage\Enums\OutputFormat::Png)
                        ->quality(90)
                        ->save($thumbnailFilePath);
                    if (Storage::disk('local')->exists('public/'.$pdfThumbnail_Location.'/'.$pdfRealName.'.png')) {
                        Storage::disk('minio')->put($pdfThumbnail_Location.'/'.$pdfRealName.'.png', file_get_contents($thumbnailFilePath));
                        Storage::disk('local')->delete('public/'.$pdfThumbnail_Location.'/'.$pdfRealName.'.png');
                        Storage::disk('local')->delete('public/'.$pdfPool_Location.'/'.$newFormattedFilename.'.'.$pdfRealExtension);
                        try {
                            if (Storage::disk('minio')->exists($pdfThumbnail_Location.'/'.$pdfRealName.'.png')) {
                                return $this->returnFileMesage(
                                    201,
                                    'Thumbnail generated !',
                                    Storage::disk('minio')->temporaryUrl(
                                        env('PDF_IMG_POOL').'/'.$pdfRealName.'.png',
                                        now()->addMinutes(5)
                                    ),
                                    Storage::disk('local')->url(env('PDF_IMG_POOL').'/'.$pdfRealName.'.png'),
                                    null,
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
                                'Failed to upload thumbnail to object storage !',
                                $pdfFileName,
                                $e->getMessage()
                            );
                        }
                    }
                } catch (Exception $e) {
                    return $this->returnFileMesage(
                        500,
                        'Failed to generate thumbnail !',
                        $pdfRealName,
                        'Could not generate thumbnail with error: '.$e->getMessage()
                    );
                }
            }
        }
    }
}
