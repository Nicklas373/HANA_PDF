<?php

namespace App\Http\Controllers\data;

use App\Http\Controllers\Controller;
use App\Models\appLogsModel;
use App\Models\compressModel;
use App\Models\cnvModel;
use App\Models\deleteModel;
use App\Models\htmlModel;
use App\Models\mergeModel;
use App\Models\splitModel;
use App\Models\watermarkModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class processLogController extends Controller
{
    public function getLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'processId' => 'required|uuid',
            'logType' =>  ['required', 'in:compress,convert,delete,html,merge,split,watermark']
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json([
                'status' => 401,
                'message' => 'Validation failed',
                'process'=> null,
                'errors' => $errors
            ]);
        }
        $logModel = $request->input('logType');
        $processId = $request->input('processId');
        try {
            $errorlog = appLogsModel::where('processId', $processId)->get();
            if ($logModel == 'compress') {
                $datalog = compressModel::where('processId', $processId)->get();
            } else if ($logModel == 'convert') {
                $datalog = cnvModel::where('processId', $processId) ->get();
            } else if ($logModel == 'delete') {
                $datalog = deleteModel::where('processId', $processId)->get();
            }  else if ($logModel == 'html') {
                $datalog = htmlModel::where('processId', $processId)->get();
            } else if ($logModel == 'merge') {
                $datalog = mergeModel::where('processId', $processId)->get();
            } else if ($logModel == 'split') {
                $datalog = splitModel::where('processId', $processId)->get();
            } else if ($logModel == 'watermark') {
                $datalog = watermarkModel::where('processId', $processId)->get();
            }
            $dataArrayLog = $datalog->toArray();
            $errorArrayLog = $errorlog->toArray();
            return response()->json([
                'status' => 200,
                'message'=> 'Request generated',
                'process'=> $dataArrayLog,
                'error' => $errorArrayLog
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Eloquent QueryException',
                'process'=> null,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Unknown Exception',
                'process'=> null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getAllLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logCount' => 'required|int',
            'logType' =>  ['required', 'in:compress,convert,delete,html,merge,split,watermark'],
            'logOrder' => ['required', 'in:asc,desc']
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json([
                'status' => 401,
                'message' => 'Validation failed',
                'process'=> null,
                'errors' => $errors
            ]);
        }
        $logCount = $request->input('logCount');
        $logModel = $request->input('logType');
        $logOrder = $request->input('logOrder');
        try {
            if ($logModel == 'compress') {
                $datalog = compressModel::orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'convert') {
                $datalog = cnvModel::orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'delete') {
                $datalog = deleteModel::orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'html') {
                $datalog = htmlModel::orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'merge') {
                $datalog = mergeModel::orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'split') {
                $datalog = splitModel::orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'watermark') {
                $datalog = watermarkModel::orderBy('procStartAt', $logOrder)->take($logCount)->get();
            }
            $dataArrayLog = $datalog->toArray();
            return response()->json([
                'status' => 200,
                'message'=> 'Request generated',
                'process'=> $dataArrayLog,
                'error' => null
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Eloquent QueryException',
                'process'=> null,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Unknown Exception',
                'process'=> null,
                'error' => $e->getMessage()
            ]);
        }
    }
}
