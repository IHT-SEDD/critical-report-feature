<?php

namespace Lica\CriticalReport\Http\Controllers\Concerns;

use Lica\CriticalReport\Services\Reports\GlobalReportActionService;

/**
 * Tambahkan `use HandlesCriticalReport;` di dalam class App\Http\Controllers\ReportController
 * untuk mendapatkan 4 endpoint berikut:
 *
 *   GET  /report/critical                                -> criticalReport()
 *   GET  /report/critical-datatable/{start?}/{end?}/{grp?}-> criticalDatatable()
 *   GET  /report/critical-print/{start?}/{end?}/{grp?}    -> criticalPrint()
 *   GET  /report/critical-excel/{start?}/{end?}/{grp?}    -> criticalExcel()
 *
 * Method-method ini murni memanggil GlobalReportActionService milik package,
 * jadi ReportController Anda tidak perlu tahu detail implementasi report critical.
 */
trait HandlesCriticalReport
{
    protected function reportEngine(): GlobalReportActionService
    {
        return app(GlobalReportActionService::class);
    }

    public function criticalReport()
    {
        return $this->reportEngine()->index('critical');
    }

    public function criticalDatatable($startDate = null, $endDate = null, $groupId = null)
    {
        return $this->reportEngine()->datatable('critical', $startDate, $endDate, $groupId);
    }

    public function criticalPrint($startDate = null, $endDate = null, $groupId = null)
    {
        return $this->reportEngine()->print('critical', $startDate, $endDate, $groupId);
    }

    public function criticalExcel($startDate = null, $endDate = null, $groupId = null)
    {
        return $this->reportEngine()->excel('critical', $startDate, $endDate, $groupId);
    }
}
