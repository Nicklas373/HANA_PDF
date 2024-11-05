<?php

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Models\appLogModel;
use App\Models\jobLogModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Stringable;

// Generate unique UUID
$uuid = AppHelper::Instance()->generateUniqueUuid(jobLogModel::class, 'processId');
$muuid = AppHelper::Instance()->generateSingleUniqueUuid(jobLogModel::class, 'groupId');

// Carbon timezone
date_default_timezone_set('Asia/Jakarta');
$now = Carbon::now('Asia/Jakarta');
$startProc = $now->format('Y-m-d H:i:s');

Schedule::command('cache:clear')
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

Schedule::command('optimize:clear')
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

Schedule::command('view:clear')
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

Schedule::command('view:cache')
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

Schedule::command('hana:clean-storage')
    ->hourly()
    ->timezone('Asia/Jakarta')
    ->environments(env('APP_ENV'))
    ->before(function(AppHelper $helper) use($uuid, $muuid, $startProc) {
        appLogModel::create([
            'processId' => $uuid,
            'groupId' => $muuid,
            'errReason' => null,
            'errStatus' => null
        ]);
        jobLogModel::create([
            'jobsName' => 'hana:clean-storage',
            'jobsEnv' => env('APP_ENV'),
            'jobsRuntime' => 'hourly',
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
        if ($output == null || $output == '' || empty($output)) {
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
                'hana:clean-storage',
                'hourly',
                $muuid,
                'FAIL',
                'Laravel Scheduler Error !',
                $output
            );
        }
    });

Schedule::command('hana:daily-report')
    ->dailyAt('19:00')
    ->timezone('Asia/Jakarta')
    ->environments(env('APP_ENV'))
    ->before(function(AppHelper $helper) use($uuid, $muuid, $startProc) {
        appLogModel::create([
            'processId' => $uuid,
            'groupId' => $muuid,
            'errReason' => null,
            'errStatus' => null
        ]);
        jobLogModel::create([
            'jobsName' => 'hana:daily-report',
            'jobsEnv' => env('APP_ENV'),
            'jobsRuntime' => 'daily at 19:00',
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
        if ($output == null || $output == '' || empty($output)) {
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
                'hana:daily-report',
                'daily at 19:00',
                $muuid,
                'FAIL',
                'Laravel Scheduler Error !',
                $output
            );
        }
    });

    Schedule::command('hana:clean-session')
        ->everyFifteenMinutes()
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
                'jobsName' => 'hana:clean-session',
                'jobsEnv' => env('APP_ENV'),
                'jobsRuntime' => 'every 15 minutes',
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
                    'hana:daily-session',
                    'every 15 minutes',
                    $muuid,
                    'FAIL',
                    'Laravel Scheduler Error !',
                    $output
                );
            }
        });
