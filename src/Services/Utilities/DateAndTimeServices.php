<?php

namespace Lica\CriticalReport\Services\Utilities;

use Illuminate\Support\Carbon;

class DateAndTimeServices
{
    /**
     * Mendapatkan nilai rentang tanggal dan waktu (from and to)
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array{from: string, to: string}
     */
    public function getDateRange(?string $startDate = null, ?string $endDate = null): array
    {
        if ($startDate === null && $endDate === null) {
            $from = Carbon::today()->startOfDay()->toDateTimeString();
            $to = Carbon::today()->setTime(23, 59, 59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
            $to = Carbon::parse($endDate)->setTime(23, 59, 59)->toDateTimeString();
        }

        return ['from' => $from, 'to' => $to];
    }
}
