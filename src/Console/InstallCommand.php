<?php

namespace Lica\CriticalReport\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'critical-report:install
                            {--institution= : Nama institusi lab, langsung diisi tanpa ditanya interaktif}
                            {--force : Timpa ulang view meskipun sudah pernah dipublish}';

    protected $description = 'Pasang modul Critical Report: controller, config/reports.php, view, dan config institusi.';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $this->info('Memasang modul Critical Report...');

        $this->ensureReportController();
        $this->ensureReportsConfig();
        $this->ensureInstitutionName();
        $this->publishViews();

        $this->newLine();
        $this->info('Selesai. Jangan lupa:');
        $this->line('  1. Tambahkan route ke ReportController@critical* di routes/web.php (lihat README).');
        $this->line('  2. Pastikan model App\\Group (atau ganti filter_label di config/reports.php) sesuai skema Anda.');
        $this->line('  3. Jalankan `php artisan config:clear` jika config lama masih ke-cache.');

        return self::SUCCESS;
    }

    /**
     * Langkah: pastikan app/Http/Controllers/ReportController.php ada,
     * dan memakai trait HandlesCriticalReport.
     */
    protected function ensureReportController(): void
    {
        $path = app_path('Http/Controllers/ReportController.php');
        $stub = __DIR__ . '/stubs/ReportController.stub';

        if (!$this->files->exists($path)) {
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->copy($stub, $path);
            $this->info("Dibuat: app/Http/Controllers/ReportController.php");
            return;
        }

        $contents = $this->files->get($path);

        if (Str::contains($contents, 'HandlesCriticalReport')) {
            $this->comment('ReportController.php sudah memakai HandlesCriticalReport, dilewati.');
            return;
        }

        $this->backup($path, $contents);

        // Sisipkan `use` import setelah baris `namespace ...;`
        $useImport = "use Lica\\CriticalReport\\Http\\Controllers\\Concerns\\HandlesCriticalReport;\n";
        if (preg_match('/^namespace\s+[^;]+;\s*$/m', $contents, $m, PREG_OFFSET_CAPTURE)) {
            $insertAt = $m[0][1] + strlen($m[0][0]);
            $contents = substr_replace($contents, "\n" . $useImport, $insertAt, 0);
        } else {
            $this->warnManual($path, 'Tidak menemukan baris "namespace ...;" untuk disisipi use HandlesCriticalReport.');
            return;
        }

        // Sisipkan `use HandlesCriticalReport;` tepat setelah baris pembuka class "{"
        if (preg_match('/class\s+[^\{]+\{\s*/', $contents, $m, PREG_OFFSET_CAPTURE)) {
            $insertAt = $m[0][1] + strlen($m[0][0]);
            $contents = substr_replace($contents, "    use HandlesCriticalReport;\n\n", $insertAt, 0);
        } else {
            $this->warnManual($path, 'Tidak menemukan baris pembuka class untuk disisipi use HandlesCriticalReport;.');
            return;
        }

        $this->files->put($path, $contents);
        $this->info('ReportController.php diperbarui: ditambahkan use HandlesCriticalReport;');
    }

    /**
     * Langkah: pastikan config/reports.php ada dan memuat key 'critical'.
     */
    protected function ensureReportsConfig(): void
    {
        $path = config_path('reports.php');
        $fullStub = __DIR__ . '/stubs/reports.config.stub';
        $entryStub = $this->files->get(__DIR__ . '/stubs/reports.config.entry.stub');

        if (!$this->files->exists($path)) {
            $this->files->copy($fullStub, $path);
            $this->info('Dibuat: config/reports.php');
            return;
        }

        $contents = $this->files->get($path);

        if (Str::contains($contents, "'critical'") || Str::contains($contents, '"critical"')) {
            $this->comment("config/reports.php sudah punya key 'critical', dilewati.");
            return;
        }

        $this->backup($path, $contents);

        if (preg_match('/return\s*\[\s*/', $contents, $m, PREG_OFFSET_CAPTURE)) {
            $insertAt = $m[0][1] + strlen($m[0][0]);
            $contents = substr_replace($contents, ltrim($entryStub, "\n") . "\n", $insertAt, 0);
            $this->files->put($path, $contents);
            $this->info("config/reports.php diperbarui: ditambahkan key 'critical'.");
        } else {
            $this->warnManual($path, "Tidak menemukan 'return [' untuk disisipi key 'critical'. Tambahkan manual, contoh ada di: " . __DIR__ . '/stubs/reports.config.entry.stub');
        }
    }

    /**
     * Langkah: isi nama institusi (dipakai print & excel), disimpan ke .env.
     */
    protected function ensureInstitutionName(): void
    {
        $configPath = config_path('critical-report.php');
        if (!$this->files->exists($configPath)) {
            $this->files->copy(__DIR__ . '/../../config/critical-report.php', $configPath);
            $this->info('Dibuat: config/critical-report.php');
        }

        $institution = $this->option('institution');

        if ($institution === null && $this->input->isInteractive()) {
            $institution = $this->ask('Nama institusi laboratorium (boleh dikosongkan, Enter untuk skip)', '');
        }

        if (!empty($institution)) {
            $this->putEnv('CRITICAL_REPORT_INSTITUTION_NAME', $institution);
            $this->info('Nama institusi disimpan ke .env sebagai CRITICAL_REPORT_INSTITUTION_NAME.');
        } else {
            $this->comment('Nama institusi dikosongkan. Isi manual nanti di .env (CRITICAL_REPORT_INSTITUTION_NAME=...) atau config/critical-report.php.');
        }
    }

    /**
     * Langkah: timpa (publish paksa) 3 view report_critical ke resources/views.
     */
    protected function publishViews(): void
    {
        $source = __DIR__ . '/../../resources/views/report_critical';
        $target = resource_path('views/dashboard/report/report_critical');

        $this->files->ensureDirectoryExists($target);

        foreach (['index.blade.php', 'print.blade.php', 'excel.blade.php'] as $view) {
            $this->files->copy("{$source}/{$view}", "{$target}/{$view}");
        }

        $this->info('View report_critical (index, print, excel) sudah dipublish/ditimpa ke resources/views/dashboard/report/report_critical.');
    }

    protected function backup(string $path, string $contents): void
    {
        $backupPath = $path . '.bak-' . date('YmdHis');
        $this->files->put($backupPath, $contents);
        $this->comment("Backup dibuat: {$backupPath}");
    }

    protected function warnManual(string $path, string $message): void
    {
        $this->warn("Perlu tindakan manual pada {$path}: {$message}");
    }

    protected function putEnv(string $key, string $value): void
    {
        $envPath = base_path('.env');
        if (!$this->files->exists($envPath)) {
            return;
        }

        $escaped = str_contains($value, ' ') ? '"' . addslashes($value) . '"' : addslashes($value);
        $contents = $this->files->get($envPath);

        if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $contents)) {
            $contents = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', "{$key}={$escaped}", $contents);
        } else {
            $contents = rtrim($contents) . "\n{$key}={$escaped}\n";
        }

        $this->files->put($envPath, $contents);
    }
}
