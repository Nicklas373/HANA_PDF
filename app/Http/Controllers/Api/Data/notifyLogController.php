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
                $applog = AppLogModel::where('processId', $processId)->get();
                $datalog = null;
                $telegramlog = null;
            } else if ($logModel == 'jobs') {
                if ($telegramModel) {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = JobLogModel::where('processId', $processId)->get();
                    $telegramlog = NotifyLogModel::where('processId', $processId)->get();
                } else {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = JobLogModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'notify') {
                $applog = AppLogModel::where('processId', $processId)->get();
                $datalog = null;
                $telegramlog = NotifyLogModel::where('processId', $processId)->get();
            } else if ($logModel == 'compress') {
                if ($telegramModel) {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = CompressModel::where('processId', $processId)->get();
                    $telegramlog = NotifyLogModel::where('processId', $processId)->get();
                } else {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = CompressModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'convert') {
                if ($telegramModel) {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = CnvModel::where('processId', $processId) ->get();
                    $telegramlog = NotifyLogModel::where('processId', $processId)->get();
                } else {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = CnvModel::where('processId', $processId) ->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'html') {
                if ($telegramModel) {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = HtmlModel::where('processId', $processId)->get();
                    $telegramlog = NotifyLogModel::where('processId', $processId)->get();
                } else {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = HtmlModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'merge') {
                if ($telegramModel) {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = MergeModel::where('processId', $processId)->get();
                    $telegramlog = NotifyLogModel::where('processId', $processId)->get();
                } else {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = MergeModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'split') {
                if ($telegramModel) {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = SplitModel::where('processId', $processId)->get();
                    $telegramlog = NotifyLogModel::where('processId', $processId)->get();
                } else {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = SplitModel::where('processId', $processId)->get();
                    $telegramlog = null;
                }
            } else if ($logModel == 'watermark') {
                if ($telegramModel) {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = WatermarkModel::where('processId', $processId)->get();
                    $telegramlog = NotifyLogModel::where('processId', $processId)->get();
                } else {
                    $applog = AppLogModel::where('processId', $processId)->get();
                    $datalog = WatermarkModel::where('processId', $processId)->get();
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
                $datalog = AppLogModel::orderBy('createdAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'jobs') {
                $datalog = JobLogModel::orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'notify') {
                $datalog = NotifyLogModel::orderBy('createdAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'compress') {
                $datalog = CompressModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'convert') {
                $datalog = CnvModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'html') {
                $datalog = HtmlModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'merge') {
                $datalog = MergeModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'split') {
                $datalog = SplitModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'watermark') {
                $datalog = WatermarkModel::where('result', '=', $logResult)->orderBy('procStartAt', $logOrder)->take($logCount)->get();
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
