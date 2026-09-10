# lica-laravel-7-8/critical-report

Paket Laporan Nilai Kritis Laboratorium untuk Laravel 7/8, diekstrak dari kode existing
(`GlobalReportActionService`, `DateAndTimeServices`, `CriticalReportDataService`, `CriticalExport`,
dan 3 view report_critical).

## Instalasi

```bash
composer require lica-laravel-7-8/critical-report
php artisan critical-report:install
```

`critical-report:install` akan:

1. Membuat `app/Http/Controllers/ReportController.php` jika belum ada (memakai trait
   `HandlesCriticalReport`), atau menyisipkan `use HandlesCriticalReport;` bila controllernya
   sudah ada (dengan backup `.bak-<timestamp>` sebelum diubah).
2. Membuat `config/reports.php` jika belum ada, atau menyisipkan key `'critical'` bila filenya
   sudah ada tapi key-nya belum (dengan backup juga).
3. Mempublish `config/critical-report.php` dan menanyakan nama institusi secara interaktif
   (disimpan ke `.env` sebagai `CRITICAL_REPORT_INSTITUTION_NAME`), atau isi lewat
   `--institution="Nama RS Anda"` untuk mode non-interaktif (CI/CD).
4. Mem-publish (menimpa) 3 view: `index.blade.php`, `print.blade.php`, `excel.blade.php` ke
   `resources/views/dashboard/report/report_critical/`.

Setelah itu tambahkan route (contoh):

```php
Route::get('report/critical', [ReportController::class, 'criticalReport']);
Route::get('report/critical-datatable/{start?}/{end?}/{group?}', [ReportController::class, 'criticalDatatable']);
Route::get('report/critical-print/{start?}/{end?}/{group?}', [ReportController::class, 'criticalPrint']);
Route::get('report/critical-excel/{start?}/{end?}/{group?}', [ReportController::class, 'criticalExcel']);
```

## Kenapa langkah-langkah di atas tidak jalan otomatis pas `composer require`?

Composer **tidak** menjalankan bagian `"scripts"` milik package yang di-*require* sebagai
dependency — `scripts` cuma dieksekusi kalau file itu ada di `composer.json` root project.
Ini bukan keterbatasan implementasi kita, tapi memang cara kerja Composer.

Satu-satunya cara membuat kode benar-benar jalan otomatis persis di detik `composer require`
selesai adalah membangun package sebagai **Composer Plugin** (`"type": "composer-plugin"`,
implement `Composer\Plugin\PluginInterface`, subscribe ke event `post-package-install`). Tapi:

- Sejak Composer 2.2, setiap plugin **wajib** di-allow-list manual oleh pemilik project lewat
  `"config": {"allow-plugins": {...}}` di composer.json root — kalau tidak, Composer akan
  menolak menjalankannya. Jadi "otomatis 100% tanpa sentuhan tangan" itu sebenarnya mustahil,
  bahkan dengan plugin sekalipun.
- Plugin yang mengubah file aplikasi (controller, config, view) di luar sepengetahuan developer
  dianggap anti-pattern di ekosistem Laravel — itu sebabnya Passport, Sanctum, Cashier,
  Telescope, Horizon, Nova, dll. semuanya memilih pola yang sama dengan package ini:
  `composer require` lalu **satu** perintah `php artisan xxx:install`.

Jadi arsitektur package ini sengaja dibuat begini:

| Langkah yang Anda minta | Cara mencapainya | Butuh kode custom? |
|---|---|---|
| Gagal install kalau Laravel/PHP tidak sesuai | `composer.json` → `require.php` & `require.illuminate/support` (version constraint) | **Tidak** — Composer sendiri yang menolak install |
| Install `maatwebsite/excel` kalau belum ada | `composer.json` → `require.maatwebsite/excel` | **Tidak** — Composer resolve otomatis |
| Buat `ReportController.php` / `config/reports.php` / service / export / view | `php artisan critical-report:install` | Ya, lewat `InstallCommand` (satu kali jalan setelah require) |

## Catatan soal versi di spesifikasi Anda

- **"Maks. PHP 7.5"** — versi PHP 7.5 tidak pernah dirilis (urutannya ...7.3, 7.4, lalu
  langsung 8.0). Saya asumsikan maksudnya "PHP 7.4.x, belum masuk PHP 8", jadi constraint-nya
  saya tulis `">=7.4.2,<8.0"`. Kalau ternyata Anda memang ingin dukung PHP 8.0 juga
  (Laravel 8 mendukungnya), tinggal ganti jadi `">=7.4.2,<8.1"` dan `maatwebsite/excel` +
  `yajra/laravel-datatables-oracle` versi yang Anda pakai juga perlu dicek kompatibel PHP 8.
- `illuminate/support: "^7.0|^8.0"` sudah otomatis menolak install di Laravel 6 atau Laravel 9+,
  jadi ini yang menjawab syarat "Min. Laravel 7, Maks. Laravel 8".
- `yajra/laravel-datatables-oracle` versi persisnya saya tulis rentang longgar (`^9.0|^10.0`) —
  silakan disesuaikan dengan versi yajra yang sudah dipakai di project awal Anda, cek dengan
  `composer why-not yajra/laravel-datatables-oracle <versi>` biar tidak bentrok.

## Bug kecil yang saya perbaiki dari kode asli

View `excel.blade.php` dan `print.blade.php` lama Anda memakai variabel `$criticalData` di
`@foreach`, padahal `GlobalReportActionService::print()` dan `::excel()` mengirim data dengan
key `reportData` (`$data['reportData']`). Ini kemungkinan sisa sebelum di-refactor ke
`GlobalReportActionService` generik, dan akan menyebabkan `Undefined variable: $criticalData`
begitu file baru dipublish. Di template package ini sudah saya samakan jadi `$reportData` agar
konsisten dengan service-nya.

## Desain: kenapa service generik ikut masuk package ini?

`GlobalReportActionService`, `DateAndTimeServices`, dan `ReportDataServiceContract` sekarang
hidup di namespace package (`Lica\CriticalReport\Services\...`), **bukan** disalin ke
`App\Services\...` seperti draft awal Anda. `config/reports.php` cukup menunjuk langsung ke
class package tersebut. Keuntungannya:

- `composer update` otomatis menarik perbaikan bug di engine ini tanpa perlu jalan install
  ulang atau menimpa file di app Anda.
- Hanya `ReportController.php`, `config/reports.php`, dan 3 view yang benar-benar perlu ada
  fisik di project Anda (karena isinya spesifik ke layout & aset project Anda).

Kalau Anda berencana bikin beberapa package laporan serupa (`other-report`, dst.) yang semuanya
bergantung pada `GlobalReportActionService`, pertimbangkan menaikkan service generik ini ke
package terpisah (mis. `lica-laravel-7-8/report-core`) supaya tidak dobel-register providernya
tiap kali dua package laporan sama-sama ter-install. Untuk saat ini, dengan baru satu report,
menyatukannya dalam satu package seperti ini sudah cukup rapi.

## Uji lokal sebelum publish ke Packagist

Sebelum push ke Packagist/repo privat, test dulu dari project Laravel Anda pakai path repository:

```json
// composer.json project Laravel Anda
"repositories": [
    { "type": "path", "url": "../critical-report" }
],
"require": {
    "lica-laravel-7-8/critical-report": "*"
}
```

```bash
composer require lica-laravel-7-8/critical-report
php artisan critical-report:install
```

## Publish ke Packagist

1. Push folder ini sebagai repo Git tersendiri (atau taruh sebagai repo terpisah di GitHub/GitLab
   organisasi `lica-laravel-7-8`).
2. Submit ke https://packagist.org (submit → arahkan ke URL repo Git). Kalau internal/tidak mau
   publik, pakai **Private Packagist** atau **Satis** (self-hosted) supaya tetap bisa
   `composer require` tanpa repo publik.
3. Tag versi semver, misalnya `v1.0.0`, supaya user bisa pin versi di composer.json mereka.
