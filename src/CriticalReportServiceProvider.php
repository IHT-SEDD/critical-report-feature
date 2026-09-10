<?php

namespace Lica\CriticalReport;

use Illuminate\Support\ServiceProvider;
use Lica\CriticalReport\Console\InstallCommand;
use Lica\CriticalReport\Services\Reports\GlobalReportActionService;

class CriticalReportServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/critical-report.php', 'critical-report');

        $this->app->singleton(GlobalReportActionService::class);
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/critical-report.php' => config_path('critical-report.php'),
            ], 'critical-report-config');

            $this->publishes([
                __DIR__ . '/../resources/views/report_critical' => resource_path('views/dashboard/report/report_critical'),
            ], 'critical-report-views');
        }
    }
}
