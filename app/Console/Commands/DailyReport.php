<?php

namespace App\Console\Commands;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Models\compressModel;
use App\Models\cnvModel;
use App\Models\deleteModel;
use App\Models\htmlModel;
use App\Models\mergeModel;
use App\Models\splitModel;
use App\Models\watermarkModel;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hana:daily-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report total process from every task on daily';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentTime = Carbon::yesterday('Asia/Jakarta');
        $currentTime->hour(19);
        try {
            $compTotalScs = compressModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($compTotalScs > 0) {
                compressModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $compTotalErr = compressModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', false)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($compTotalErr > 0) {
                compressModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', false)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $cnvTotalScs = cnvModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($cnvTotalScs > 0) {
                cnvModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $cnvTotalErr = cnvModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', false)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($cnvTotalErr > 0) {
                cnvModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', false)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $htmlTotalScs = htmlModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($htmlTotalScs > 0) {
                htmlModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $htmlTotalErr = htmlModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', false)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($htmlTotalErr > 0) {
                htmlModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', false)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $mergeTotalScs = mergeModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($mergeTotalScs > 0) {
                mergeModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $mergeTotalErr = mergeModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', false)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($mergeTotalErr > 0) {
                mergeModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', false)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $splitTotalScs = splitModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($splitTotalScs > 0) {
                splitModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $splitTotalErr = splitModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', false)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($splitTotalErr > 0) {
                splitModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', false)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $watermarkTotalScs = watermarkModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($watermarkTotalScs > 0) {
                watermarkModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', '=', true)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $watermarkTotalErr = watermarkModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', false)
                                        ->where('isReport', '=', false)
                                        ->count();
            if ($watermarkTotalErr > 0) {
                watermarkModel::where('procStartAt', '>=', $currentTime)
                                        ->where('result', false)
                                        ->where('isReport', '=', false)
                                        ->update(['isReport' => true]);
            }
            $CountTotalTask = $CountTotalTask = $compTotalScs + $compTotalErr + $cnvTotalScs + $cnvTotalErr + $htmlTotalScs + $htmlTotalErr +
            $mergeTotalScs + $mergeTotalErr + $splitTotalScs + $splitTotalErr + $watermarkTotalScs;
            if ($CountTotalTask > 0) {
                NotificationHelper::Instance()->sendDailyTaskNotify($compTotalScs, $compTotalErr, $cnvTotalScs, $cnvTotalErr, $htmlTotalScs, $htmlTotalErr,
                $mergeTotalScs, $mergeTotalErr, $splitTotalScs, $splitTotalErr, $watermarkTotalScs,
                $watermarkTotalErr, true, "", "");
            }
        } catch (QueryException $e) {
            NotificationHelper::Instance()->sendDailyTaskNotify("", "", "", "", "", "", "", "", "", "", "", "", false, 'Eloquent Query Exception', $e->getMessage());
        } catch (\Exception $e) {
            NotificationHelper::Instance()->sendDailyTaskNotify("", "", "", "", "", "", "", "", "", "", "", "", false, 'Unknown Exception', $e->getMessage());
        }
    }
}
