<?php

namespace App\Http\Controllers\Api\Data;

use App\Http\Controllers\Controller;
use App\Models\appLogModel;
use App\Models\jobLogModel;
use App\Models\notifyLogModel;
use App\Models\compressModel;
use App\Models\cnvModel;
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
            'groupId' => 'uuid',
            'logType' =>  ['required', 'in:app,jobs,notify,compress,convert,html,merge,split,watermark']
        ]);
        if ($validator->fails()) {
            return $this->returnDataMesage(
                401,
                'Validation failed',
                null,
                null,
                null,
                $validator->messages()->first()
            );
        }
        $logModel = $request->input('logType');
        $processId = $request->input('processId');
        $groupId = $request->input('groupId');
        try {
            if ($logModel == 'app') {
                if ($groupId) {
                    $applog = appLogModel::where('groupId', $groupId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                }
                $datalog = null;
            } else if ($logModel == 'jobs') {
                if ($groupId) {
                    $applog = appLogModel::where('groupId', $groupId)->get();
                    $datalog = jobLogModel::where('groupId', $groupId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = jobLogModel::where('processId', $processId)->get();
                }
            } else if ($logModel == 'notify') {
                $applog = appLogModel::where('processId', $processId)->get();
                $datalog = notifyLogModel::where('processId', $processId)->get();
            } else if ($logModel == 'compress') {
                if ($groupId) {
                    $applog = appLogModel::where('groupId', $groupId)->get();
                    $datalog = compressModel::where('groupId', $groupId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = compressModel::where('processId', $processId)->get();
                }
            } else if ($logModel == 'convert') {
                if ($groupId) {
                    $applog = appLogModel::where('groupId', $groupId)->get();
                    $datalog = cnvModel::where('groupId', $groupId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = cnvModel::where('processId', $processId)->get();
                }
            } else if ($logModel == 'html') {
                if ($groupId) {
                    $applog = appLogModel::where('groupId', $groupId)->get();
                    $datalog = htmlModel::where('groupId', $groupId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = htmlModel::where('processId', $processId)->get();
                }
                $telegramlog = notifyLogModel::where('processId', $processId)->get();
            } else if ($logModel == 'merge') {
                if ($groupId) {
                    $applog = appLogModel::where('groupId', $groupId)->get();
                    $datalog = mergeModel::where('groupId', $groupId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = mergeModel::where('processId', $processId)->get();
                }
            } else if ($logModel == 'split') {
                if ($groupId) {
                    $applog = appLogModel::where('groupId', $groupId)->get();
                    $datalog = splitModel::where('groupId', $groupId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = splitModel::where('processId', $processId)->get();
                }
            } else if ($logModel == 'watermark') {
                if ($groupId) {
                    $applog = appLogModel::where('groupId', $groupId)->get();
                    $datalog = splitModel::where('groupId', $groupId)->get();
                } else {
                    $applog = appLogModel::where('processId', $processId)->get();
                    $datalog = splitModel::where('processId', $processId)->get();
                }
            }
            return response()->json([
                'status' => 200,
                'message'=> 'Request generated',
                'app' => $applog,
                'data' => $datalog,
                'errors' => null
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Database connection error !',
                'app' => null,
                'data' => null,
                'errors' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message'=> 'Unknown Exception',
                'app' => null,
                'data' => null,
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
                $datalog = appLogModel::orderBy('created_at', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'jobs') {
                $datalog = jobLogModel::where('jobsResult', '=', $logResult)->orderBy('jobsId', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'notify') {
                $datalog = notifyLogModel::orderBy('notifyId', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'compress') {
                $datalog = compressModel::where('result', '=', $logResult)->orderBy('compressId', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'convert') {
                $datalog = cnvModel::where('result', '=', $logResult)->orderBy('cnvId', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'html') {
                $datalog = htmlModel::where('result', '=', $logResult)->orderBy('htmlId', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'merge') {
                $datalog = mergeModel::where('result', '=', $logResult)->orderBy('mergeId', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'split') {
                $datalog = splitModel::where('result', '=', $logResult)->orderBy('splitId', $logOrder)->take($logCount)->get();
            } else if ($logModel == 'watermark') {
                $datalog = watermarkModel::where('result', '=', $logResult)->orderBy('watermarkId', $logOrder)->take($logCount)->get();
            }
            $dataArrayLog = $datalog->toArray();
            return $this->returnDataMesage(
                200,
                'Request generated',
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
                $e->getMessage()
            );
        } catch (\Exception $e) {
            return $this->returnDataMesage(
                500,
                'Unknown Exception',
                null,
                null,
                null,
                $e->getMessage()
            );
        }
    }
}
