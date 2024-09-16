<?php

namespace App\Http\Controllers\Api\Data;

use App\Http\Controllers\Controller;
use App\Models\AppLogModel;
use App\Models\JobLogModel;
use App\Models\NotifyLogModel;
use App\Models\CompressModel;
use App\Models\CnvModel;
use App\Models\HtmlModel;
use App\Models\MergeModel;
use App\Models\SplitModel;
use App\Models\WatermarkModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class notifyLogController extends Controller
{
    public function getLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'processId' => 'required|uuid',
            'logType' =>  ['required', 'in:app,jobs,notify,compress,convert,html,merge,split,watermark'],
            'telegramLogs' => 'required|boolean'
        ]);
        if ($validator->fails()) {
            return $this->returnDataMesage(
                401,
                'Validation failed',
                null,
                null,
                $validator->messages()->first()
            );
        }
        $logModel = $request->input('logType');
        $processId = $request->input('processId');
        $telegramModel = $request->input('telegramLogs');
        try {
            if ($logModel == 'app') {
                $applog = appLogModel::where('processId', $processId)->get();
                $datalog = null;
                $telegramlog = null;
            } else if ($logModel == 'jobs') {
                if ($telegramModel) {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = jobLogModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = jobLogModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'notify') {
                $applog = appLogModel::where('processId', $processId)->get();
                $datalog = null;
                $telegramlog = notifyLogModel::where('processId', $processId)->get();
            } else if ($logModel == 'compress') {
                if ($telegramModel) {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = compressModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = compressModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'convert') {
                if ($telegramModel) {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = cnvModel::where('processId', $processId) ->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = cnvModel::where('processId', $processId) ->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'html') {
                if ($telegramModel) {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = htmlModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = htmlModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'merge') {
                if ($telegramModel) {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = mergeModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = mergeModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'split') {
                if ($telegramModel) {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = splitModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = splitModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'watermark') {
                if ($telegramModel) {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = watermarkModel::where('processId', $processId)->get();
                    $telegramlog = notifyLogsModel::where('processId', $processId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = watermarkModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            }
            if ($telegramModel) {
                return response()->json([
                    'status' => 200,
                    'message'=> 'Request generated',
                    'app' => $applog,
                    'data' => $datalog,
                    'notification' => $telegramlog,
                    'errors' => null
                ], 200);
            } else {
                return response()->json([
                    'status' => 200,
                    'message'=> 'Request generated',
                    'app' => $applog,
                    'data' => $datalog,
                    'notification' => null,
                    'errors' => null
                ], 200);
            }
        } catch (QueryException $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Database connection error !',
                'app' => null,
                'data' => null,
                'notification' => null,
                'errors' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Unknown Exception',
                'app' => null,
                'data' => null,
                'notification' => null,
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logCount' => 'required|int',
            'logResult' => ['required', 'in:true,false'],
            'logType' =>  ['required', 'in:app,jobs,notify,compress,convert,html,merge,split,watermark'],
            'logOrder' => ['required', 'in:asc,desc']
        ]);
        if ($validator->fails()) {
            return $this->returnDataMesage(
                401,
                'Validation failed',
                null,
                null,
                $validator->messages()->first()
            );
        }
        $logCount = $request->input('logCount');
        $logResult = $request->input('logResult');
        $logModel = $request->input('logType');
        $logOrder = $request->input('logOrder');
        try {
            if ($logModel == 'app') {
                $datalog = appLogModel::orderBy('createdAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'jobs') {
                $datalog = jobLogModel::orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'notify') {
                $datalog = notifyLogModel::orderBy('createdAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'compress') {
                $datalog = compressModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'convert') {
                $datalog = cnvModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
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
                $dataArrayLog,
                null,
                null
            );
        } catch (QueryException $e) {
            return $this->returnDataMesage(
                500,
                'Eloquent QueryException',
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
                $e->getMessage()
            );
        }
    }
}
