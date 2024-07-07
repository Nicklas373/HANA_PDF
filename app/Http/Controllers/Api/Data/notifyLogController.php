<?php

namespace App\Http\Controllers\Api\Data;

use App\Http\Controllers\Controller;
use App\Models\accessLogsModel;
use App\Models\appLogsModel;
use App\Models\jobLogsModel;
use App\Models\notifyLogsModel;
use App\Models\compressModel;
use App\Models\cnvModel;
use App\Models\deleteModel;
use App\Models\htmlModel;
use App\Models\mergeModel;
use App\Models\splitModel;
use App\Models\watermarkModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class notifyLogController extends Controller
{
    public function getLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'processId' => 'required|uuid',
            'logType' =>  ['required', 'in:access,jobs,notify,compress,convert,html,merge,split,watermark'],
            'telegramLogs' => 'required|boolean'
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
            } else if ($logModel == 'compress') {
                if ($telegramModel) {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = compressModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = compressModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'convert') {
                if ($telegramModel) {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = cnvModel::where('processId', $processId) ->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = cnvModel::where('processId', $processId) ->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'html') {
                if ($telegramModel) {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = htmlModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = htmlModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'merge') {
                if ($telegramModel) {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = mergeModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = mergeModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'split') {
                if ($telegramModel) {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = splitModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = splitModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'watermark') {
                if ($telegramModel) {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = watermarkModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogsModel::where('processId', $processId)->get();
                    $datalog = watermarkModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            }
            if ($telegramModel) {
                return $this->returnDataMesage(
                    200,
                    'Request generated',
                    null,
                    null,
                    $datalog,
                    $applog,
                    $telegramlog,
                    null
                );
            } else {
                return $this->returnDataMesage(
                    200,
                    'Request generated',
                    null,
                    null,
                    $datalog,
                    $applog,
                    null,
                    null
                );
            }
        } catch (QueryException $e) {
            return $this->returnDataMesage(
                500,
                'Eloquent QueryException',
                null,
                null,
                null,
                null,
                null,
                $e->getMessage()
            );
        } catch (\Exception $e) {
            return $this->returnDataMesage(
                500,
                'Unknown Exception',
                null,
                null,
                null,
                null,
                null,
                $e->getMessage()
            );
        }
    }

    public function getAllLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logCount' => 'required|int',
            'logResult' => ['required', 'in:true,false'],
            'logType' =>  ['required', 'in:access,jobs,notify,compress,convert,html,merge,split,watermark'],
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
        $logResult = $request->input('logResult');
        $logModel = $request->input('logType');
        $logOrder = $request->input('logOrder');
        try {
            if ($logModel == 'access') {
                $datalog = accessLogsModel::take($logCount)->get();
            } else if ($logModel == 'jobs') {
                $datalog = jobLogsModel::orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'notify') {
                $datalog = notifyLogsModel::take($logCount)->get();
            } else if ($logModel == 'compress') {
                $datalog = compressModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'convert') {
                $datalog = cnvModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'delete') {
                $datalog = deleteModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'html') {
                $datalog = htmlModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'merge') {
                $datalog = mergeModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'split') {
                $datalog = splitModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'watermark') {
                $datalog = watermarkModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
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
        } catch (QueryException $e) {
            return $this->returnDataMesage(
                500,
                'Eloquent QueryException',
                null,
                null,
                null,
                null,
                null,
                $e->getMessage()
            );
        } catch (\Exception $e) {
            return $this->returnDataMesage(
                500,
                'Unknown Exception',
                null,
                null,
                null,
                null,
                null,
                $e->getMessage()
            );
        }
    }
}
