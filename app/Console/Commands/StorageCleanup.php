<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class StorageCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hana:clear-storage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup linked storage every hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pdfUpload_Location = env('PDF_UPLOAD');
        $pdfProcessed_Location = env('PDF_DOWNLOAD');
        $pdfMerge_Location = env('PDF_MERGE_TEMP');
        $pdfImage_Location = env('ILOVEPDF_EXT_IMG_DIR');
        $publicUploadTemp = Storage::allFiles('public/'.$pdfUpload_Location);
        $publicDownloadTemp = Storage::allFiles('public/'.$pdfProcessed_Location);
        $publicMergeTemp = Storage::allFiles('public/'.$pdfMerge_Location);
        $publicImageTemp = Storage::allFiles('public/'.$pdfImage_Location);
        Storage::delete($publicUploadTemp);
        Storage::delete($publicDownloadTemp);
        Storage::delete($publicMergeTemp);
        Storage::delete($publicImageTemp);
    }
}
