<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class TruncateActivityLogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Membersihkan (truncate) tabel activity_logs
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->warn('PERINGATAN: Anda mencoba menjalankan truncate di PRODUCTION!');
            $this->command->warn('Data activity logs akan dihapus permanen.');

            if (!$this->command->confirm('Lanjutkan truncate activity logs? (yes/no)', false)) {
                $this->command->info('Dibatalkan.');
                return;
            }
        }

        try {
            $this->command->info('Memulai pembersihan activity_logs...');

            // Hitung jumlah records sebelum truncate
            $countBefore = DB::table('activity_logs')->count();
            $this->command->info("Jumlah records sebelum truncate: {$countBefore}");

            // Truncate tabel
            DB::statement('TRUNCATE TABLE activity_logs CASCADE');

            // Hitung jumlah records setelah truncate
            $countAfter = DB::table('activity_logs')->count();

            $this->command->info("✓ Tabel activity_logs berhasil dibersihkan!");
            $this->command->info("Records dihapus: {$countBefore}");
            $this->command->info("Records tersisa: {$countAfter}");

            Log::info('Activity logs truncated', [
                'deleted_records' => $countBefore,
                'environment' => app()->environment(),
            ]);

        } catch (\Exception $e) {
            $this->command->error("Gagal melakukan truncate: {$e->getMessage()}");
            Log::error('Failed to truncate activity logs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
