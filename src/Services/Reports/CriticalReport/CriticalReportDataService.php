<?php

namespace Lica\CriticalReport\Services\Reports\CriticalReport;

use Lica\CriticalReport\Services\Reports\Contracts\ReportDataServiceContract;
use Illuminate\Support\Facades\DB;

class CriticalReportDataService implements ReportDataServiceContract
{
    public function queryData($from, $to, $filter = null)
    {
        $model = DB::table('finish_transaction_tests')
            ->select([
                'finish_transaction_tests.id',
                'finish_transaction_tests.finish_transaction_id',
                'finish_transaction_tests.transaction_id',
                'finish_transaction_tests.test_id',
                'finish_transaction_tests.test_name',
                'finish_transaction_tests.group_id',
                'finish_transaction_tests.group_name',
                'finish_transaction_tests.global_result',
                'finish_transaction_tests.normal_value',
                'finish_transaction_tests.validate_time',
                'finish_transaction_tests.report_time',
                'finish_transaction_tests.input_time',

                'finish_transactions.id as finish_transaction_ref_id',
                'finish_transactions.patient_id',
                'finish_transactions.room_id',
                'finish_transactions.no_lab',

                'rooms.id as room_ref_id',
                'rooms.room as room_name',

                'patients.id as patient_ref_id',
                'patients.name as patient_name',
                'patients.medrec as patient_medrec',
            ])
            ->where('finish_transaction_tests.report_status', '=', 1)
            ->whereBetween('finish_transaction_tests.input_time', [$from, $to])
            ->when($filter != null && $filter != "null" && $filter != 0, function ($q) use ($filter) {
                $q->where('finish_transaction_tests.group_id', '=', $filter);
            })
            ->join('finish_transactions', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
            ->leftJoin('patients', 'finish_transactions.patient_id', '=', 'patients.id')
            ->leftJoin('rooms', 'finish_transactions.room_id', '=', 'rooms.id')
            ->orderBy('finish_transaction_tests.input_time', 'desc');

        return $model;
    }
}
