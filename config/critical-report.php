<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nama Institusi
    |--------------------------------------------------------------------------
    |
    | Ditampilkan di kop laporan cetak (print) & excel. Diisi otomatis saat
    | menjalankan `php artisan critical-report:install`, atau isi manual
    | di sini / lewat .env (CRITICAL_REPORT_INSTITUTION_NAME).
    |
    */
    'institution_name' => env('CRITICAL_REPORT_INSTITUTION_NAME', ''),

    /*
    |--------------------------------------------------------------------------
    | Target TAT (Turn Around Time) pelaporan, dalam menit
    |--------------------------------------------------------------------------
    */
    'target_tat_minutes' => env('CRITICAL_REPORT_TARGET_TAT_MINUTES', 30),
];
