<?php

namespace App\Http\Controllers\Api\Data;

use App\Http\Controllers\Controller;
use App\Models\appLogsModel;
use App\Models\compressModel;
use App\Models\cnvModel;
use App\Models\deleteModel;
use App\Models\htmlModel;
use App\Models\mergeModel;
use App\Models\splitModel;
use App\Models\watermarkModel;
use App\Models\jobLogsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class dataLogController extends Controller
{
    public function getSingleLogs()
    {
        $validator = Validator::make($request->all(), [
            'processId' => 'required|uuid',
            'logType' =>  ['required', 'in:compress,convert,html,merge,split,watermark']
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return $this->returnDataMesage(
                401,
                'Validation failed',
                0,
                0,
                null,
                null,
                null,
                $errors
            );
        }

        $logModel = $request->input('logType');
        $processId = $request->input('processId');

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

        $errorlog = appLogsModel::where('processId', $processId)->get();

        if ($datalog->isEmpty() || !$datalog) {
            if ($errorlog->isEmpty() || !$errorlog) {
                return $this->returnDataMesage(
                    200,
                    'Request generated',
                    null,
                    null,
                    null,
                    null,
                    null,
                    'Requested data was empty or not found'
                );
            } else {
                $errorArrayLog = $errorlog->toArray();
                return $this->returnDataMesage(
                    200,
                    'Request generated',
                    null,
                    null,
                    $errorArrayLog,
                    null,
                    null,
                    null
                );
            }
        } else {
            $dataArrayLog = $datalog->toArray();
            $errorArrayLog = $errorlog->toArray();
            return $this->returnDataMesage(
                200,
                'Request generated',
                null,
                null,
                $dataArrayLog,
                $errorArrayLog,
                null,
                null
            );
        }
    }

    public function getAllLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logCount' => 'required|int',
            'logType' =>  ['required', 'in:compress,convert,html,merge,split,watermark'],
            'logOrder' => ['required', 'in:asc,desc']
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return $this->returnDataMesage(
                401,
                'Validation failed',
                0,
                0,
                null,
                null,
                null,
                $errors
            );
        }

        $logCount = $request->input('logCount');
        $logModel = $request->input('logType');
        $logOrder = $request->input('logOrder');

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
        } else if ($logModel == 'joblogs') {
            $datalog = jobLogs::orderBy('procStartAt', $logOrder)->take($logCount)->get();
        }

        $dataArrayLog = $datalog->toArray();

        return $this->returnDataMesage(
            200,
            'Request generated',
            null,
            null,
            $dataArrayLog,
            null,
            null,
            null
        );
    }
}
