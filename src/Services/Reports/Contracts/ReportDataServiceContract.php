<?php

namespace Lica\CriticalReport\Services\Reports\Contracts;

interface ReportDataServiceContract
{
    public function queryData($from, $to, $filter = null);
}
