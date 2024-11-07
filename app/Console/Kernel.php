<?php

namespace App\Console;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Models\appLogModel;
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
        $cacheClearUUIDProc = AppHelper::Instance()->generateUniqueUuid(jobLogModel::class, 'processId');
        $cacheClearUUIDGroup = AppHelper::Instance()->generateUniqueUuid(jobLogModel::class, 'groupId');
        $optimizeClearUUIDProc = AppHelper::Instance()->generateUniqueUuid(jobLogModel::class, 'processId');
        $optimizeClearUUIDGroup = AppHelper::Instance()->generateUniqueUuid(jobLogModel::class, 'groupId');
        $viewClearUUIDProc = AppHelper::Instance()->generateUniqueUuid(jobLogModel::class, 'processId');
        $viewClearUUIDGroup = AppHelper::Instance()->generateUniqueUuid(jobLogModel::class, 'groupId');
        $viewCacheUUIDProc = AppHelper::Instance()->generateUniqueUuid(jobLogModel::class, 'processId');
        $viewCacheUUIDGroup = AppHelper::Instance()->generateUniqueUuid(jobLogModel::class, 'groupId');

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

        $schedule
            ->command('cache:clear')
            ->weekly()
            ->environments(env('APP_ENV'))
            ->timezone('Asia/Jakarta')
            ->before(function(AppHelper $helper) use($cacheClearUUIDProc, $cacheClearUUIDGroup, $startProc) {
                appLogModel::create([
                    'processId' => $cacheClearUUIDProc,
                    'groupId' => $cacheClearUUIDGroup,
                    'errReason' => null,
                    'errStatus' => null
                ]);
                jobLogModel::create([
                    'jobsName' => 'cache:clear',
                    'jobsEnv' => env('APP_ENV'),
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'groupId' => $muuid,
                    'processId' => $cacheClearUUIDProc,
                    'procStartAt' => $startProc
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($cacheClearUUIDProc, $cacheClearUUIDGroup, $startProc)  {
                $start = Carbon::parse($startProc);
                $end =  Carbon::parse($helper::instance()->getCurrentTimeZone());
                $duration = $end->diff($start);
                if ($output == null || $output == '' || empty($output) || str_contains($output, 'successfully')) {
                    jobLogModel::where('processId', '=', $cacheClearUUIDProc)
                        ->update([
                            'jobsResult' => true,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                } else {
                    appLogModel::where('processId', '=', $cacheClearUUIDProc)
                        ->update([
                            'errReason' => 'Laravel Scheduler Error !',
                            'errStatus' => $output
                        ]);
                    jobLogModel::where('processId', '=', $cacheClearUUIDProc)
                        ->update([
                            'jobsResult' => false,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                    NotificationHelper::Instance()->sendSchedErrNotify(
                        'cache:clear',
                        'weekly',
                        $cacheClearUUIDGroup,
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
            ->before(function(AppHelper $helper) use($optimizeClearUUIDProc, $optimizeClearUUIDGroup, $startProc) {
                appLogModel::create([
                    'processId' => $optimizeClearUUIDProc,
                    'groupId' => $optimizeClearUUIDGroup,
                    'errReason' => null,
                    'errStatus' => null
                ]);
                jobLogModel::create([
                    'jobsName' => 'optimize:clear',
                    'jobsEnv' => env('APP_ENV'),
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'groupId' => $optimizeClearUUIDGroup,
                    'processId' => $optimizeClearUUIDProc,
                    'procStartAt' => $startProc
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($optimizeClearUUIDProc, $optimizeClearUUIDGroup, $startProc)  {
                $start = Carbon::parse($startProc);
                $end =  Carbon::parse($helper::instance()->getCurrentTimeZone());
                $duration = $end->diff($start);
                if ($output == null || $output == '' || empty($output) || str_contains($output, 'DONE')) {
                    jobLogModel::where('processId', '=', $optimizeClearUUIDProc)
                        ->update([
                            'jobsResult' => true,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                } else {
                    appLogModel::where('processId', '=', $optimizeClearUUIDProc)
                        ->update([
                            'errReason' => 'Laravel Scheduler Error !',
                            'errStatus' => $output
                        ]);
                    jobLogModel::where('processId', '=', $optimizeClearUUIDProc)
                        ->update([
                            'jobsResult' => false,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                    NotificationHelper::Instance()->sendSchedErrNotify(
                        'optimize:clear',
                        'weekly',
                        $optimizeClearUUIDGroup,
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
            ->before(function(AppHelper $helper) use($viewClearUUIDProc, $viewClearUUIDGroup, $startProc) {
                appLogModel::create([
                    'processId' => $viewClearUUIDProc,
                    'groupId' => $viewClearUUIDGroup,
                    'errReason' => null,
                    'errStatus' => null
                ]);
                jobLogModel::create([
                    'jobsName' => 'view:clear',
                    'jobsEnv' => env('APP_ENV'),
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'groupId' => $viewClearUUIDGroup,
                    'processId' => $viewClearUUIDProc,
                    'procStartAt' => $startProc
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($viewClearUUIDProc, $viewClearUUIDGroup, $startProc)  {
                $start = Carbon::parse($startProc);
                $end =  Carbon::parse($helper::instance()->getCurrentTimeZone());
                $duration = $end->diff($start);
                if ($output == null || $output == '' || empty($output) || str_contains($output, 'successfully')) {
                    jobLogModel::where('processId', '=', $viewClearUUIDProc)
                        ->update([
                            'jobsResult' => true,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                } else {
                    appLogModel::where('processId', '=', $viewClearUUIDProc)
                        ->update([
                            'errReason' => 'Laravel Scheduler Error !',
                            'errStatus' => $output,
                        ]);
                    jobLogModel::where('processId', '=', $viewClearUUIDProc)
                        ->update([
                            'jobsResult' => false,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                    NotificationHelper::Instance()->sendSchedErrNotify(
                        'view:clear',
                        'weekly',
                        $viewClearUUIDGroup,
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
            ->before(function(AppHelper $helper) use($viewCacheUUIDProc, $viewCacheUUIDGroup, $startProc) {
                appLogModel::create([
                    'processId' => $viewCacheUUIDProc,
                    'groupId' => $viewCacheUUIDGroup,
                    'errReason' => null,
                    'errStatus' => null
                ]);
                jobLogModel::create([
                    'jobsName' => 'view:cache',
                    'jobsEnv' => env('APP_ENV'),
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'groupId' => $viewCacheUUIDGroup,
                    'processId' => $viewCacheUUIDProc,
                    'procStartAt' => $startProc
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($viewCacheUUIDProc, $viewCacheUUIDGroup, $startProc)  {
                $start = Carbon::parse($startProc);
                $end =  Carbon::parse($helper::instance()->getCurrentTimeZone());
                $duration = $end->diff($start);
                if ($output == null || $output == '' || empty($output) || str_contains($output, 'successfully')) {
                    jobLogModel::where('processId', '=', $viewCacheUUIDProc)
                        ->update([
                            'jobsResult' => true,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                } else {
                    appLogModel::where('processId', '=', $viewCacheUUIDProc)
                        ->update([
                            'errReason' => 'Laravel Scheduler Error !',
                            'errStatus' => $output,
                        ]);
                    jobLogModel::where('processId', '=', $viewCacheUUIDProc)
                        ->update([
                            'jobsResult' => false,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' => $duration->s.' seconds'
                        ]);
                    NotificationHelper::Instance()->sendSchedErrNotify(
                        'view:cache',
                        'weekly',
                        $viewCacheUUIDGroup,
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
