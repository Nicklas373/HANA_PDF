<?php

namespace App\Http\Controllers\data;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;

class limitLogController extends Controller
{
    public function getLimit(Request $request) {
        try {
            $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
            $remainingFiles = $ilovepdf->getRemainingFiles();
            $totalUsage = 250 - $remainingFiles;
            return response()->json([
                'status' => 200,
                'message' => 'Request generated',
                'remaining' => $remainingFiles,
                'total' => 0,
                'error' => ''
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Internal Server Error',
                'remaining' => 0,
                'total' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
