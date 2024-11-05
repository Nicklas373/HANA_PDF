<?php

namespace App\Console;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Models\jobLogModel;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Stringable;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Generate unique UUID
        $uuid = AppHelper::Instance()->generateUniqueUuid(jobLogModel::class, 'processId');
        $muuid = AppHelper::Instance()->generateSingleUniqueUuid(jobLogModel::class, 'groupId');

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

        $schedule
            ->command('cache:clear')
            ->weekly()
            ->environments(env('APP_ENV'))
            ->timezone('Asia/Jakarta')
            ->before(function(AppHelper $helper) use($uuid, $muuid, $startProc) {
                appLogModel::create([
                    'processId' => $uuid,
                    'groupId' => $muuid,
                    'errReason' => null,
                    'errStatus' => null
                ]);
                jobLogModel::create([
                    'jobsName' => 'cache:clear',
                    'jobsEnv' => env('APP_ENV'),
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'groupId' => $muuid,
                    'processId' => $uuid,
                    'procStartAt' => $startProc
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($uuid, $muuid, $startProc)  {
                $start = Carbon::parse($startProc);
                $end =  Carbon::parse($helper::instance()->getCurrentTimeZone());
                $duration = $end->diff($start);
                if ($output == null || $output == '' || empty($output) || str_contains($output, 'successfully')) {
                    jobLogModel::where('processId', '=', $uuid)
                        ->update([
                            'jobsResult' => true,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                } else {
                    appLogModel::where('processId', '=', $uuid)
                        ->update([
                            'errReason' => 'Laravel Scheduler Error !',
                            'errStatus' => $output
                        ]);
                    jobLogModel::where('processId', '=', $uuid)
                        ->update([
                            'jobsResult' => false,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                    NotificationHelper::Instance()->sendSchedErrNotify(
                        'cache:clear',
                        'weekly',
                        $muuid,
                        'FAIL',
                        'Laravel Scheduler Error !',
                        $output
                    );
                }
            });
        $schedule
            ->command('optimize:clear')
            ->weekly()
            ->environments(env('APP_ENV'))
            ->timezone('Asia/Jakarta')
            ->before(function(AppHelper $helper) use($uuid, $muuid, $startProc) {
                appLogModel::create([
                    'processId' => $uuid,
                    'groupId' => $muuid,
                    'errReason' => null,
                    'errStatus' => null
                ]);
                jobLogModel::create([
                    'jobsName' => 'optimize:clear',
                    'jobsEnv' => env('APP_ENV'),
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'groupId' => $muuid,
                    'processId' => $uuid,
                    'procStartAt' => $startProc
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($uuid, $muuid, $startProc)  {
                $start = Carbon::parse($startProc);
                $end =  Carbon::parse($helper::instance()->getCurrentTimeZone());
                $duration = $end->diff($start);
                if ($output == null || $output == '' || empty($output) || str_contains($output, 'DONE')) {
                    jobLogModel::where('processId', '=', $uuid)
                        ->update([
                            'jobsResult' => true,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                } else {
                    appLogModel::where('processId', '=', $uuid)
                        ->update([
                            'errReason' => 'Laravel Scheduler Error !',
                            'errStatus' => $output
                        ]);
                    jobLogModel::where('processId', '=', $uuid)
                        ->update([
                            'jobsResult' => false,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                    NotificationHelper::Instance()->sendSchedErrNotify(
                        'optimize:clear',
                        'weekly',
                        $muuid,
                        'FAIL',
                        'Laravel Scheduler Error !',
                        $output
                    );
                }
            });
        $schedule
            ->command('view:clear')
            ->weekly()
            ->environments(env('APP_ENV'))
            ->timezone('Asia/Jakarta')
            ->before(function(AppHelper $helper) use($uuid, $muuid, $startProc) {
                appLogModel::create([
                    'processId' => $uuid,
                    'groupId' => $muuid,
                    'errReason' => null,
                    'errStatus' => null
                ]);
                jobLogModel::create([
                    'jobsName' => 'view:clear',
                    'jobsEnv' => env('APP_ENV'),
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'groupId' => $muuid,
                    'processId' => $uuid,
                    'procStartAt' => $startProc
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($uuid, $muuid, $startProc)  {
                $start = Carbon::parse($startProc);
                $end =  Carbon::parse($helper::instance()->getCurrentTimeZone());
                $duration = $end->diff($start);
                if ($output == null || $output == '' || empty($output) || str_contains($output, 'successfully')) {
                    jobLogModel::where('processId', '=', $uuid)
                        ->update([
                            'jobsResult' => true,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                } else {
                    appLogModel::where('processId', '=', $uuid)
                        ->update([
                            'errReason' => 'Laravel Scheduler Error !',
                            'errStatus' => $output,
                        ]);
                    jobLogModel::where('processId', '=', $uuid)
                        ->update([
                            'jobsResult' => false,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                    NotificationHelper::Instance()->sendSchedErrNotify(
                        'view:clear',
                        'weekly',
                        $muuid,
                        'FAIL',
                        'Laravel Scheduler Error !',
                        $output
                    );
                }
            });
        $schedule
            ->command('view:cache')
            ->weekly()
            ->environments(env('APP_ENV'))
            ->timezone('Asia/Jakarta')
            ->before(function(AppHelper $helper) use($uuid, $muuid, $startProc) {
                appLogModel::create([
                    'processId' => $uuid,
                    'groupId' => $muuid,
                    'errReason' => null,
                    'errStatus' => null
                ]);
                jobLogModel::create([
                    'jobsName' => 'view:cache',
                    'jobsEnv' => env('APP_ENV'),
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'groupId' => $muuid,
                    'processId' => $uuid,
                    'procStartAt' => $startProc
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($uuid, $muuid, $startProc)  {
                $start = Carbon::parse($startProc);
                $end =  Carbon::parse($helper::instance()->getCurrentTimeZone());
                $duration = $end->diff($start);
                if ($output == null || $output == '' || empty($output) || str_contains($output, 'successfully')) {
                    jobLogModel::where('processId', '=', $uuid)
                        ->update([
                            'jobsResult' => true,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                } else {
                    appLogModel::where('processId', '=', $uuid)
                        ->update([
                            'errReason' => 'Laravel Scheduler Error !',
                            'errStatus' => $output,
                        ]);
                    jobLogModel::where('processId', '=', $uuid)
                        ->update([
                            'jobsResult' => false,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                    NotificationHelper::Instance()->sendSchedErrNotify(
                        'view:cache',
                        'weekly',
                        $muuid,
                        'FAIL',
                        'Laravel Scheduler Error !',
                        $output
                    );
                }
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
