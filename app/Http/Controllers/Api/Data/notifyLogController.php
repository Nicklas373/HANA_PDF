<?php

namespace App\Http\Controllers\Api\Data;

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
            'logType' =>  ['required', 'in:access,jobs,notify'],
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
        try {
            if ($logModel == 'access') {
                $datalog = accessLogsModel::take($logCount)->get();
            } else if ($logModel == 'jobs') {
                $datalog = jobLogsModel::take($logCount)->get();
            } else if ($logModel == 'notify') {
                $datalog = notifyLogsModel::take($logCount)->get();
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
