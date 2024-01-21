<?php

namespace App\Http\Controllers\data;

use App\Http\Controllers\Controller;
use App\Models\accessLogsModel;
use App\Models\appLogsModel;
use App\Models\jobLogsModel;
use App\Models\notifyLogsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class notifyLogController extends Controller
{
    public function getLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'processId' => 'required|uuid',
            'logType' =>  ['required', 'in:access,jobs,notify'],
            'telegramLogs' => 'required|boolean'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json([
                'status' => 401,
                'message' => 'Validation failed',
                'process'=> null,
                'errors' => $errors,
                'telegram' => null
            ]);
        }
        $logModel = $request->input('logType');
        $processId = $request->input('processId');
        $telegramModel = $request->input('telegramLogs');
        try {
            if ($logModel == 'access') {
                if ($telegramModel) {
                    $applog = null;
                    $datalog = accessLogsModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = null;
                    $datalog = accessLogsModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'jobs') {
                if ($telegramModel) {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = jobLogsModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = jobLogsModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'notify') {
                $applog = null;
                $datalog = null;
                $telegramlog = notifyLogsModel::where('processId', $processId)->get();
            }
            if ($telegramModel) {
                return response()->json([
                    'status' => 200,
                    'message'=> 'Request generated',
                    'process'=> $datalog,
                    'error' => $applog,
                    'telegram' => $telegramlog
                ]);
            } else {
                return response()->json([
                    'status' => 200,
                    'message'=> 'Request generated',
                    'process'=> $datalog,
                    'error' => $applog,
                    'telegram' => null
                ]);
            }

        } catch (QueryException $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Eloquent QueryException',
                'process'=> null,
                'error' => $e->getMessage(),
                'telegram' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Unknown Exception',
                'process'=> null,
                'error' => $e->getMessage(),
                'telegram' => null
            ]);
        }
    }

    public function getAllLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logCount' => 'required|int',
            'logType' =>  ['required', 'in:access,jobs,notify'],
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json([
                'status' => 401,
                'message' => 'Validation failed',
                'process'=> null,
                'errors' => $errors,
                'telegram' => null
            ]);
        }
        $logCount = $request->input('logCount');
        $logModel = $request->input('logType');
        try {
            if ($logModel == 'access') {
                $datalog = accessLogsModel::take($logCount)->get();
            } else if ($logModel == 'jobs') {
                $datalog = jobLogsModel::take($logCount)->get();
            } else if ($logModel == 'notify') {
                $datalog = notifyLogsModel::take($logCount)->get();
            }
            $dataArrayLog = $datalog->toArray();
            return response()->json([
                'status' => 200,
                'message'=> 'Request generated',
                'process'=> $dataArrayLog,
                'error' => null,
                'telegram' => null
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Eloquent QueryException',
                'process'=> null,
                'error' => $e->getMessage(),
                'telegram' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Unknown Exception',
                'process'=> null,
                'error' => $e->getMessage(),
                'telegram' => null
            ]);
        }
    }
}
