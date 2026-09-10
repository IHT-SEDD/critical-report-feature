<?php

namespace Lica\CriticalReport\Services\Reports;

use Lica\CriticalReport\Services\Utilities\DateAndTimeServices;
use Lica\CriticalReport\Services\Reports\Contracts\ReportDataServiceContract;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class GlobalReportActionService
{
    protected DateAndTimeServices $dateTimeService;

    public function __construct(DateAndTimeServices $dateTimeService)
    {
        $this->dateTimeService = $dateTimeService;
    }

    protected function config(string $key): array
    {
        $config = config("reports.$key");
        abort_unless($config, 404, "Report [$key] tidak terdaftar di config/reports.php");
        return $config;
    }

    /** @return ReportDataServiceContract */
    protected function dataService(array $config)
    {
        return app()->make($config['data_service']); // resolve on-demand
    }

    protected function filterLabel(array $config, $filter): string
    {
        if (empty($config['filter_label']) || $filter === null || $filter === 'null' || $filter == 0) {
            return '-';
        }
        return $config['filter_label']::findOrFail($filter)->name;
    }

    public function index(string $key)
    {
        $config = $this->config($key);
        return view($config['view_index'], ['title' => $config['title']]);
    }

    public function datatable(string $key, $startDate, $endDate, $filter = null)
    {
        $config = $this->config($key);
        $range = $this->dateTimeService->getDateRange($startDate, $endDate);

        $query = $this->dataService($config)->queryData($range['from'], $range['to'], $filter);

        return DataTables::of($query)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function print(string $key, $startDate, $endDate, $filter = null)
    {
        $config = $this->config($key);
        $range = $this->dateTimeService->getDateRange($startDate, $endDate);

        $data['reportData'] = $this->dataService($config)->queryData($range['from'], $range['to'], $filter)->get();
        $data['group'] = $this->filterLabel($config, $filter);
        $data['startDate'] = date('d/m/Y', strtotime($range['from']));
        $data['endDate'] = date('d/m/Y', strtotime($range['to']));

        return view($config['view_print'], $data);
    }

    public function excel(string $key, $startDate, $endDate, $filter = null)
    {
        $config = $this->config($key);
        abort_unless($config['export_class'], 404, "Report [$key] belum punya export excel.");

        $range = $this->dateTimeService->getDateRange($startDate, $endDate);
        $data['reportData'] = $this->dataService($config)->queryData($range['from'], $range['to'], $filter)->get();
        $data['group'] = $this->filterLabel($config, $filter);
        $data['startDate'] = date('d/m/Y', strtotime($range['from']));
        $data['endDate'] = date('d/m/Y', strtotime($range['to']));

        $fileName = $config['title'] . ' - ' . str_replace('/', '', $data['startDate']) . '_' . str_replace('/', '', $data['endDate']) . '.xlsx';

        return Excel::download(new $config['export_class']($data), $fileName);
    }
}
