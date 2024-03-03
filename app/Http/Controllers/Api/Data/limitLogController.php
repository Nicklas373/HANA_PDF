<?php

namespace App\Http\Controllers\Api\Data;

use App\Http\Controllers\Controller;
use Ilovepdf\Ilovepdf;

class limitLogController extends Controller
{
    public function getLimit() {
        try {
            $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
            $remainingFiles = $ilovepdf->getRemainingFiles();
            $totalUsage = 250 - $remainingFiles;
            return $this->returnDataMesage(
                200,
                'Request generated',
                $remainingFiles,
                $totalUsage,
                null,
                null,
                null,
                null
            );
        } catch (\Exception $e) {
            return $this->returnDataMesage(
                500,
                'Cannot establish connection with iLovePDF server',
                0,
                0,
                null,
                null,
                null,
                $e->getMessage()
            );
        }
    }
}
