<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--prune=14 : Days to keep backup files}';
    protected $description = 'Create a database backup and prune old backups';

    public function handle(): int
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $backupDir = storage_path('app/backups/database');

        File::ensureDirectoryExists($backupDir);

        $timestamp = now()->format('Ymd_His');
        $filename = "{$driver}_backup_{$timestamp}.sql";
        $fullPath = "{$backupDir}/{$filename}";

        if ($driver === 'sqlite') {
            $sqlitePath = config("database.connections.{$connection}.database");

            if (! File::exists($sqlitePath)) {
                $this->error("SQLite file not found: {$sqlitePath}");
                return self::FAILURE;
            }

            $copyName = "{$backupDir}/sqlite_backup_{$timestamp}.sqlite";
            File::copy($sqlitePath, $copyName);
            $this->info("SQLite backup created: {$copyName}");
        } elseif ($driver === 'mysql') {
            $host = config("database.connections.{$connection}.host");
            $port = config("database.connections.{$connection}.port");
            $database = config("database.connections.{$connection}.database");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");

            $cmd = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s %s > %s',
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($fullPath)
            );

            exec($cmd, $output, $status);

            if ($status !== 0) {
                $this->error('MySQL backup failed. Ensure mysqldump is installed and accessible.');
                return self::FAILURE;
            }

            $this->info("MySQL backup created: {$fullPath}");
        } elseif ($driver === 'pgsql') {
            $host = config("database.connections.{$connection}.host");
            $port = config("database.connections.{$connection}.port");
            $database = config("database.connections.{$connection}.database");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");

            putenv("PGPASSWORD={$password}");

            $cmd = sprintf(
                'pg_dump -h %s -p %s -U %s -F p %s > %s',
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($fullPath)
            );

            exec($cmd, $output, $status);

            if ($status !== 0) {
                $this->error('PostgreSQL backup failed. Ensure pg_dump is installed and accessible.');
                return self::FAILURE;
            }

            $this->info("PostgreSQL backup created: {$fullPath}");
        } else {
            $this->error("Unsupported database driver: {$driver}");
            return self::FAILURE;
        }

        $this->pruneBackups($backupDir, (int) $this->option('prune'));

        return self::SUCCESS;
    }

    private function pruneBackups(string $backupDir, int $days): void
    {
        $threshold = now()->subDays($days)->timestamp;

        foreach (File::files($backupDir) as $file) {
            if ($file->getMTime() < $threshold) {
                File::delete($file->getRealPath());
            }
        }

        $this->info("Backup pruning complete. Kept last {$days} days.");
    }
}