<?php

namespace App\Console;

use App\Helpers\AppHelper;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Stringable;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */

    protected $commands = [
        \App\Console\Commands\StorageCleanup::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $cacheClearGUID = AppHelper::instance()->get_guid();
        $optimizeClearGUID = AppHelper::instance()->get_guid();
        $viewClearGUID = AppHelper::instance()->get_guid();
        $viewCacheGUID = AppHelper::instance()->get_guid();
        $hanaClearGUID = AppHelper::instance()->get_guid();
        $schedule
            ->command('cache:clear')
            ->weekly()
            ->environments(['production'])
            ->timezone('Asia/Jakarta')
            ->before(function(AppHelper $helper) use($cacheClearGUID) {
                DB::table('datalogs')->insert([
                    'jobsId' => $cacheClearGUID,
                    'jobsName' => 'cache:clear',
                    'jobsEnv' => 'production',
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'jobsErrMessage' => null,
                    'jobsStart' => $helper::instance()->getCurrentTimeZone(),
                    'jobsEnd' => null
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($cacheClearGUID) {
                DB::table('datalogs')
                    ->where('jobsId', '=', $cacheClearGUID)
                    ->update([
                        'jobsErrMessage' => $output,
                        'jobsResult' => true,
                        'jobsEnd' => $helper::instance()->getCurrentTimeZone()
                ]);
            });
        $schedule
            ->command('optimize:clear')
            ->weekly()
            ->environments(['production'])
            ->timezone('Asia/Jakarta')
            ->before(function(AppHelper $helper) use($optimizeClearGUID) {
                DB::table('datalogs')->insert([
                    'jobsId' => $optimizeClearGUID,
                    'jobsName' => 'optimize:clear',
                    'jobsEnv' => 'production',
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'jobsErrMessage' => null,
                    'jobsStart' => $helper::instance()->getCurrentTimeZone(),
                    'jobsEnd' => null
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($optimizeClearGUID) {
                DB::table('datalogs')
                    ->where('jobsId', '=', $optimizeClearGUID)
                    ->update([
                        'jobsErrMessage' => $output,
                        'jobsResult' => true,
                        'jobsEnd' => $helper::instance()->getCurrentTimeZone()
                ]);
            });
        $schedule
            ->command('view:clear')
            ->weekly()
            ->environments(['production'])
            ->timezone('Asia/Jakarta')
            ->before(function(AppHelper $helper) use($viewClearGUID) {
                DB::table('datalogs')->insert([
                    'jobsId' => $viewClearGUID,
                    'jobsName' => 'view:clear',
                    'jobsEnv' => 'production',
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'jobsErrMessage' => null,
                    'jobsStart' => $helper::instance()->getCurrentTimeZone(),
                    'jobsEnd' => null
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($viewClearGUID) {
                DB::table('datalogs')
                    ->where('jobsId', '=', $viewClearGUID)
                    ->update([
                        'jobsErrMessage' => $output,
                        'jobsResult' => true,
                        'jobsEnd' => $helper::instance()->getCurrentTimeZone()
                ]);
            });
        $schedule
            ->command('view:cache')
            ->weekly()
            ->environments(['production'])
            ->timezone('Asia/Jakarta')
            ->before(function(AppHelper $helper) use($viewCacheGUID) {
                DB::table('datalogs')->insert([
                    'jobsId' => $viewCacheGUID,
                    'jobsName' => 'view:cache',
                    'jobsEnv' => 'production',
                    'jobsRuntime' => 'weekly',
                    'jobsResult' => false,
                    'jobsErrMessage' => null,
                    'jobsStart' => $helper::instance()->getCurrentTimeZone(),
                    'jobsEnd' => null
                ]);
            })
            ->after(function(AppHelper $helper, Stringable $output) use($viewCacheGUID) {
                DB::table('datalogs')
                    ->where('jobsId', '=', $viewCacheGUID)
                    ->update([
                        'jobsErrMessage' => $output,
                        'jobsResult' => true,
                        'jobsEnd' => $helper::instance()->getCurrentTimeZone()
                ]);
            });
        $schedule
            ->command('hana:clear-storage')
            ->hourly()
            ->timezone('Asia/Jakarta')
            ->environments(['production'])
            ->before(function(AppHelper $helper) use($hanaClearGUID) {
                DB::table('datalogs')->insert([
                    'jobsId' => $hanaClearGUID,
                    'jobsName' => 'hana:clear-storage',
                    'jobsEnv' => 'production',
                    'jobsRuntime' => 'hourly',
                    'jobsResult' => false,
                    'jobsErrMessage' => null,
                    'jobsStart' => $helper::instance()->getCurrentTimeZone(),
                    'jobsEnd' => null
                ]);
            })
            ->onFailure(function(Stringable $output, AppHelper $helper) use($hanaClearGUID) {
                DB::table('datalogs')
                    ->where('jobsId', '=', $hanaClearGUID)
                    ->update([
                        'jobsErrMessage' => $output,
                        'jobsResult' => false,
                        'jobsEnd' => $helper::instance()->getCurrentTimeZone()
                ]);
            })
            ->onSuccess(function(AppHelper $helper) use($hanaClearGUID) {
                DB::table('datalogs')
                    ->where('jobsId', '=', $hanaClearGUID)
                    ->where('jobsName', 'like', 'hana%')
                    ->where('jobsResult', '=', false)
                    ->update([
                        'jobsResult' => true,
                        'jobsEnd' => $helper::instance()->getCurrentTimeZone()
                ]);
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
