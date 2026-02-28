# Manajemen Activity Logs

## Ringkasan

Sistem activity logs mencatat semua tindakan user di aplikasi (login, create, update, delete, dll). Dokumen ini menjelaskan cara mengelola logs tersebut agar tidak memberatkan database.

## Struktur Tabel

**Tabel:** `activity_logs`

- Menyimpan semua aktivitas user dengan metadata lengkap
- Menggunakan primary key UUID
- Kolom JSONB untuk data yang fleksibel
- Terindeks pada: `user_id`, `created_at`, `event`, `subject_type`, `subject_id`

## Masalah Potensial

Tanpa manajemen yang baik, activity logs bisa:

1. **Membesarkan Ukuran Database** - Setiap aksi membuat record baru
2. **Memperlambat Query** - Tabel besar dengan jutaan baris
3. **Meningkatkan Biaya Storage** - Data JSONB bisa besar
4. **Mempengaruhi Performance Backup** - Database lebih besar butuh waktu lebih lama

## Solusi yang Sudah Diimplementasi

### 1. Pembersihan Otomatis (Scheduler)

**Lokasi:** `routes/console.php`

**Jadwal:** Setiap hari Minggu jam 02:00 pagi

```php
Schedule::command('activity-logs:cleanup --days=90 --force')
    ->weekly()
    ->sundays()
    ->at('02:00');
```

**Fungsinya:**
- Otomatis menghapus logs lebih lama dari 90 hari
- Berjalan di background tanpa intervensi manual
- Mencatat status completion di log

### 2. Perintah Pembersihan Manual

**Perintah:**
```bash
php artisan activity-logs:cleanup --days=90 --force
```

**Opsi:**
- `--days=90` - Simpan logs dari N hari terakhir (default: 90)
- `--force` - Lewati konfirmasi
- `--archive` - Arsipkan ke tabel `activity_logs_archive` sebelum menghapus

**Contoh:**
```bash
# Hapus logs lebih lama dari 30 hari
php artisan activity-logs:cleanup --days=30 --force

# Arsipkan logs lebih lama dari 180 hari, lalu hapus
php artisan activity-logs:cleanup --days=180 --archive --force
```

### 3. API Monitoring Storage

**Endpoint:** `POST /Admin/Logs/Activities/Storage-Stats`

**Response:**
```json
{
  "total_records": 50000,
  "table_size_mb": 125.5,
  "data_size_mb": 80.3,
  "indexes_size_mb": 45.2,
  "oldest_record": "2024-01-15 10:30:00",
  "newest_record": "2025-02-27 15:30:00",
  "records_last_30_days": 15000,
  "records_last_7_days": 3500,
  "avg_records_per_day": 500,
  "projected_records_in_90_days": 45000,
  "projected_size_in_90_days_mb": 250.5,
  "retention_policy": {
    "lama_hari_penyimpanan": 90,
    "pembersihan_otomatis_diaktifkan": true,
    "pembersihan_berikutnya": "2025-03-02 02:00:00"
  },
  "recommendations": [
    "Pertimbangkan untuk mengurangi masa penyimpanan dari 90 menjadi 30-60 hari untuk mengontrol ukuran tabel."
  ],
  "warnings": [],
  "health_status": "healthy"
}
```

**Gunakan endpoint ini untuk:**
- Memantau ukuran tabel saat ini
- Melacak laju pertumbuhan
- Mendapatkan rekomendasi berdasarkan data aktual
- Menerima peringatan sebelum masalah terjadi

### 4. Konfigurasi

**Lokasi:** `.env`

```env
# Konfigurasi Activity Logs
LAMA_HARI_PENYIMPANAN_LOG=90          # Berapa hari logs disimpan
PEMBERSIHAN_OTOMATIS_LOG=true         # Aktifkan pembersihan otomatis
JADWAL_PEMBERSIHAN_LOG=weekly         # Jadwal: daily, weekly, monthly
WAKTU_PEMBERSIHAN_LOG=02:00           # Waktu pembersihan dijalankan
ARSIP_LOG_DIAKTIFKAN=false            # Arsipkan alih-alih menghapus
ARSIP_LOG_SETELAH_HARI=180            # Arsipkan logs lebih lama dari ini
```

**Lokasi:** `config/logging.php`

## Praktik Terbaik

### 1. **Tetapkan Masa Penyimpanan yang Tepat**

- **Development:** 7-30 hari (logging minimal)
- **Staging:** 30-60 hari (logging moderat untuk testing)
- **Production:** 30-90 hari (keseimbangan antara compliance dan storage)

### 2. **Pantau Secara Teratur**

Panggil endpoint storage stats mingguan atau bulanan untuk melacak pertumbuhan:
```bash
curl -X POST http://api-anda.com/Admin/Logs/Activities/Storage-Stats \
  -H "Authorization: Bearer TOKEN_ANDA"
```

### 3. **Gunakan Logging Selektif**

Hanya log event penting:
- ✅ Autentikasi user (login, logout)
- ✅ Modifikasi data (create, update, delete)
- ✅ Operasi sensitif (ubah role, permissions)
- ❌ Setiap page view
- ❌ Setiap API request
- ❌ Operasi baca (read)

### 4. **Arsipkan Sebelum Hapus** (Opsional)

Untuk kebutuhan compliance atau audit:

```bash
# Arsipkan ke tabel terpisah alih-alih menghapus
php artisan activity-logs:cleanup --days=365 --archive --force
```

Ini memindahkan logs lama ke tabel `activity_logs_archive`:
- Menyimpan data historis untuk audit
- Tidak mempengaruhi performance query production
- Bisa diekspor ke cold storage nanti

### 5. **Optimasi Database**

Setelah penghapusan besar, jalankan:

```sql
-- Reclaim space setelah penghapusan
VACUUM FULL activity_logs;

-- Rebuild indexes
REINDEX TABLE activity_logs;

-- Analisa untuk optimasi query
ANALYZE activity_logs;
```

## Pertimbangan Performa

### Dampak dari Berbagai Jumlah Record

| Records | Ukuran Tabel | Waktu Query | Dampak |
|---------|-------------|------------|--------|
| 10,000 | ~50 MB | < 10ms | Minimal |
| 100,000 | ~500 MB | 10-50ms | Rendah |
| 1,000,000 | ~5 GB | 50-200ms | Moderate |
| 10,000,000 | ~50 GB | 200ms-1s | Tinggi |

### Rekomendasi Threshold

- **< 100K records:** Tidak perlu tindakan
- **100K - 500K records:** Monitor bulanan, pertimbangkan retensi 60 hari
- **500K - 1M records:** Monitor mingguan, kurangi ke retensi 30 hari
- **\> 1M records:** Pembersihan segera diperlukan, investigasi event volume tinggi

## Troubleshooting

### Masalah: Tabel logs terlalu besar

**Solusi 1:** Jalankan cleanup segera
```bash
php artisan activity-logs:cleanup --days=30 --force
```

**Solusi 2:** Arsipkan dan cleanup
```bash
php artisan activity-logs:cleanup --days=90 --archive --force
```

**Solusi 3:** Kurangi masa penyimpanan
```env
LAMA_HARI_PENYIMPANAN_LOG=30
```

### Masalah: Pembersihan otomatis tidak jalan

**Cek 1:** Verifikasi cron berjalan
```bash
# Cek scheduler
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

**Cek 2:** Verifikasi konfigurasi
```bash
php artisan config:clear
php artisan schedule:list
```

**Cek 3:** Jalankan manual untuk test
```bash
php artisan activity-logs:cleanup --days=90 --force
```

## Ringkasan Endpoint API

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/Admin/Logs/Activities/LoadData` | POST | Dapatkan daftar logs dengan pagination |
| `/Admin/Logs/Activities/View/{id}` | POST | Dapatkan detail log tertentu |
| `/Admin/Logs/Activities/Dashboard` | POST | Dapatkan statistik dashboard |
| `/Admin/Logs/Activities/Storage-Stats` | POST | Dapatkan statistik storage & rekomendasi |
| `/Admin/Logs/User/View/{id}` | POST | Dapatkan aktivitas user tertentu |

## Kesimpulan

Activity logs sangat berharga untuk auditing dan debugging, tetapi memerlukan manajemen yang baik. Sistem sudah terkonfigurasi dengan:

- ✅ Pembersihan otomatis mingguan (retensi 90 hari)
- ✅ Perintah pembersihan manual tersedia
- ✅ Monitoring storage dengan rekomendasi
- ✅ Kebijakan retensi yang dapat dikonfigurasi
- ✅ Opsi arsip untuk kebutuhan compliance

**Rekomendasi:** Cek storage stats bulanan dan sesuaikan masa penyimpanan berdasarkan laju pertumbuhan sistem Anda.


# Membersihkan Activity Logs dengan Seeder

## Perintah untuk Membersihkan Activity Logs

### Cara 1: Jalankan Seeder Langsung

```bash
php artisan db:seed --class=TruncateActivityLogsSeeder
```

### Cara 2: Jalankan dengan Force (Non-interactive)

```bash
php artisan db:seed --class=TruncateActivityLogsSeeder --force
```

### Cara 3: Panggil dari DatabaseSeeder

Jika ingin menjalankan bersama seeder lain, tambahkan ke `DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call([
        TruncateActivityLogsSeeder::class,  // Tambahkan baris ini
        PermissionSeeder::class,
        AdminSeeder::class,
        SIMLKDMasterDataSeeder::class,
    ]);
}
```

Lalu jalankan:
```bash
php artisan db:seed
```

## Fitur Keamanan

### Environment Production
- Akan muncul peringatan sebelum truncate
- Akan meminta konfirmasi (yes/no)
- Batal jika tidak dikonfirmasi

### Environment Local/Development
- Langsung menjalankan truncate
- Menampilkan informasi records yang dihapus

## Contoh Output

### Development/Staging:
```
INFO  Seeding database.

Memulai pembersihan activity_logs...
Jumlah records sebelum truncate: 150
✓ Tabel activity_logs berhasil dibersihkan!
Records dihapus: 150
Records tersisa: 0
```

### Production:
```
INFO  Seeding database.

PERINGATAN: Anda mencoba menjalankan truncate di PRODUCTION!
Data activity logs akan dihapus permanen.
 ⚯ Apakah Anda ingin menjalankan seeder? (yes/no) [no]:
   no

Dibatalkan.
```

## Perbedaan dengan Perintah Lain

### db:wipe
```bash
php artisan db:wipe
```
- Menghapus SEMUA tabel
- Menghapus database structure
- Perlu migrate ulang

### TruncateActivityLogsSeeder
```bash
php artisan db:seed --class=TruncateActivityLogsSeeder
```
- Hanya menghapus data activity_logs
- Tabel structure tetap ada
- Tidak perlu migrate ulang
- Lebih aman dan spesifik

## Catatan Penting

⚠️ **PERINGATAN**: Truncate akan menghapus PERMANEN semua data di tabel `activity_logs`. Data tidak bisa dikembalikan setelah truncate.

### Sebelum Menjalankan Truncate:

1. **Backup Data** (Opsional tapi disarankan untuk production):
```bash
pg_dump -U username -h localhost -t activity_logs database_name > backup_activity_logs.sql
```

2. **Cek Jumlah Records**:
```bash
php artisan tinker --execute="echo 'Total records: ' . \Modules\V1\Logging\Model\ActivityLog::count()"
```

3. **Arsipkan jika Perlu** (Alih-alih truncate):
```bash
php artisan activity-logs:cleanup --days=90 --archive --force
```

## Alternatif Lain

### Hapus dengan Kondisi Tertentu

Gunakan perintah cleanup jika ingin menghapus logs lama saja:

```bash
# Hapus logs lebih lama dari 30 hari
php artisan activity-logs:cleanup --days=30 --force
```

### Hapus Manual dengan Tinker

```bash
php artisan tinker
>>> \Modules\V1\Logging\Model\ActivityLog::where('created_at', '<', now()->subDays(30))->delete();
```

## Troubleshooting

### Error: "Table doesn't exist"
Solusi: Jalankan migration terlebih dahulu
```bash
php artisan migrate
```

### Error: "Permission denied"
Solusi: Pastikan user database memiliki permission TRUNCATE
```sql
GRANT TRUNCATE ON TABLE activity_logs TO your_user;
```

### Seeder tidak muncul di list
Solusi: Jalankan composer dump-autoload
```bash
composer dump-autoload
```

## Best Practices

1. **Development**: Boleh truncate kapan saja untuk testing
2. **Staging**: Pertimbangkan untuk backup sebelum truncate
3. **Production**: JANGAN truncate gunakan kecuali sangat perlu dan sudah backup
4. **Monitoring**: Setelah truncate, pantau apakah ada issue dengan aplikasi

## Ringkasan

✅ **TruncateActivityLogsSeeder** - Cara aman membersihkan activity logs
✅ **Konfirmasi Production** - Mencegah kecelakaan di production
✅ **Logging** - Mencatat aktivitas truncate untuk audit
✅ **Informasi Jelas** - Menampilkan jumlah records yang dihapus

Gunakan seeder ini untuk membersihkan activity logs saat development atau testing, tetapi berhati-hatilah saat menggunakannya di production environment.


# Activity Logs Severity System

## Overview

Activity logs sekarang memiliki field `severity` yang otomatis mendeteksi tingkat keparahan berdasarkan nama event. Severity digunakan untuk memberikan visualisasi yang lebih baik di frontend.

## Severity Levels

### 1. **ERROR** 🔴 (Merah)
Critical failures yang memerlukan perhatian segera.

**Event Patterns:**
- `error`, `failed`, `authentication_failed`, `login_failed`
- `unauthorized`, `forbidden`
- `out_of_stock`, `insufficient`
- `validation_error`, `api_error`, `database_error`
- `exception`, `crash`

**Contoh Events:**
- `login_failed` - Login gagal (password salah 3x)
- `stock_out_of_stock` - Stok habis saat akan membuat PO
- `payment_failed` - Pembayaran gagal
- `validation_error` - Validasi data gagal
- `api_error` - API error
- `database_error` - Database error
- `unauthorized_access` - Unauthorized access attempt

### 2. **WARNING** ⚠️ (Kuning)
Potensi masalah yang perlu diwaspadai.

**Event Patterns:**
- `warning`, `_low`, `_minimum`
- `overdue`, `late`, `pending`
- `expir` (expired/expiring), `due_soon`
- `high_return`, `unusual_activity`

**Contoh Events:**
- `stock_low` - Stok menipis (di bawah minimum)
- `po_overdue` - PO melewati tenggat waktu
- `supplier_late_confirmation` - Supplier mengkonfirmasi terlambat
- `high_return_rate` - Retur tinggi
- `invoice_due_soon` - Invoice hampir jatuh tempo

### 3. **SUCCESS** ✅ (Hijau)
Operasi yang berhasil dilakukan.

**Event Patterns:**
- `success`, `created`, `deleted`, `completed`
- `confirmed`, `received`, `delivered`
- `exported`, `imported`, `approved`
- `login`, `login_success` (tanpa kata "failed")

**Contoh Events:**
- `login` / `login_success` - Login berhasil
- `user_created` - User dibuat
- `po_created` - PO dibuat
- `po_confirmed` - PO dikonfirmasi
- `stock_received` - Stok masuk

### 4. **INFO** ℹ️ (Biru)
Informasi umum dan aktivitas rutin.

**Event Patterns:**
- `logout`, `logout_success`
- `viewed`, `search`, `filter`
- `downloaded`, `settings_updated`
- Semua event yang tidak match dengan kategori di atas

**Contoh Events:**
- `logout` - Logout
- `data_viewed` - View data
- `report_exported` - Export laporan
- `search_performed` - Filter/pencarian

## API Response

Backend akan mengirim field severity beserta metadata warna dan icon:

```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "event": "login",
  "description": "User logged in successfully",
  "severity": "success",
  "severityColor": "#10B981",
  "severityIcon": "✅",
  "severityLabel": "Success",
  "created_at": "2026-02-27T15:39:39.000000Z"
}
```

## Cara Penggunaan

### 1. **Auto-Detection (Default)**

Severity akan otomatis dideteksi dari nama event:

```php
use Modules\V1\Logging\Facades\Activity;
use Modules\V1\Logging\Enums\LogEventEnum;

// Ini otomatis severity = "success" karena event "login"
Activity::event(LogEventEnum::LOGIN)
    ->log('User logged in successfully');

// Ini otomatis severity = "error" karena event "login_failed"
Activity::event(LogEventEnum::LOGIN_FAILED)
    ->log('User failed to login');

// Ini otomatis severity = "warning" karena event "stock_low"
Activity::event('stock_low')
    ->log('Stock quantity is below minimum threshold');
```

### 2. **Manual Override**

Jika ingin override severity secara manual:

```php
use Modules\V1\Logging\Facades\Activity;
use Modules\V1\Logging\Enums\LogEventEnum;
use Modules\V1\Logging\Enums\SeverityEnum;

// Override severity ke WARNING
Activity::event(LogEventEnum::LOGIN)
    ->severity(SeverityEnum::WARNING)
    ->log('User logged in from suspicious location');

// Override severity ke ERROR
Activity::event('custom_event')
    ->severity(SeverityEnum::ERROR)
    ->log('Critical custom event occurred');
```

### 3. **Filtering by Severity**

Query activity logs berdasarkan severity:

```php
use Modules\V1\Logging\Model\ActivityLog;

// Get only errors
$errors = ActivityLog::errors()->get();

// Get only warnings
$warnings = ActivityLog::warnings()->get();

// Get only success
$success = ActivityLog::success()->get();

// Get only info
$info = ActivityLog::info()->get();

// Filter by specific severity
$custom = ActivityLog::withSeverity('error')->get();
```

### 4. **Severity Helper di Model**

ActivityLog model memiliki helper methods:

```php
$log = ActivityLog::first();

// Get severity enum instance
$severityEnum = $log->getSeverityEnum();

// Get severity color untuk UI
$color = $log->severity_color; // #10B981

// Get severity icon
$icon = $log->severity_icon; // ✅

// Get severity label
$label = $log->severity_label; // Success
```

## Severity Colors

| Severity | Hex Color | CSS Class | Icon |
|----------|-----------|-----------|------|
| Info     | #3B82F6   | text-blue-500 bg-blue-50 | ℹ️ |
| Warning  | #F59E0B   | text-yellow-500 bg-yellow-50 | ⚠️ |
| Error    | #EF4444   | text-red-500 bg-red-50 | 🔴 |
| Success  | #10B981   | text-green-500 bg-green-50 | ✅ |

## Contoh Implementasi di Frontend

### TypeScript Interface

```typescript
interface ActivityLog {
  id: string;
  event: string;
  description: string;
  severity: 'info' | 'warning' | 'error' | 'success';
  severityColor: string;
  severityIcon: string;
  severityLabel: string;
  created_at: string;
}

// Badge Component
const SeverityBadge = ({ log }: { log: ActivityLog }) => {
  return (
    <span
      className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
      style={{
        backgroundColor: log.severityColor + '20', // 20% opacity
        color: log.severityColor,
      }}
    >
      <span>{log.severityIcon}</span>
      <span>{log.severityLabel}</span>
    </span>
  );
};
```

### Table dengan Color Coding

```tsx
const ActivityTable = ({ logs }: { logs: ActivityLog[] }) => {
  return (
    <table>
      <tbody>
        {logs.map((log) => (
          <tr
            key={log.id}
            className={`border-l-4`}
            style={{ borderLeftColor: log.severityColor }}
          >
            <td>{log.event}</td>
            <td>{log.description}</td>
            <td>
              <SeverityBadge log={log} />
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};
```

## Events Reference

### Common Events dan Severity-nya

| Event | Severity | Description |
|-------|----------|-------------|
| `login` | success | User berhasil login |
| `login_failed` | error | User gagal login |
| `logout` | info | User logout |
| `user_created` | success | User baru dibuat |
| `user_updated` | success | User diupdate |
| `user_deleted` | success | User dihapus |
| `po_created` | success | PO dibuat |
| `po_confirmed` | success | PO dikonfirmasi |
| `po_failed` | error | PO gagal |
| `po_overdue` | warning | PO overdue |
| `stock_received` | success | Stok masuk |
| `stock_low` | warning | Stok menipis |
| `stock_out_of_stock` | error | Stok habis |
| `payment_success` | success | Pembayaran berhasil |
| `payment_failed` | error | Pembayaran gagal |
| `invoice_due_soon` | warning | Invoice jatuh tempo < 7 hari |
| `data_viewed` | info | Data dilihat |
| `report_exported` | success | Laporan di-export |
| `search_performed` | info | Pencarian dilakukan |

## Best Practices

1. **Gunakan nama event yang deskriptif**
    - ✅ `login_failed` (auto error)
    - ❌ `login` dengan manual severity

2. **Gunakan override severity hanya untuk special cases**
    - Contoh: Login dari lokasi suspicious → `login` dengan severity `warning`

3. **Consistent naming convention**
    - Gunakan snake_case untuk event names
    - Gunakan suffix yang konsisten: `_failed`, `_success`, `_low`, dll

4. **Testing**
    - Pastikan event names menghasilkan severity yang diharapkan
    - Gunakan `SeverityEnum::fromEvent('event_name')` untuk testing

## Migration Note

Jika ada existing activity logs sebelum penambahan severity:

```bash
# Run migration
php artisan migrate

# Update existing logs (optional - severity will default to 'info')
php artisan tinker
>>> ActivityLog::whereNull('severity')->update(['severity' => 'info']);
```

Atau biarkan default 'info' dan akan auto-update pada next log creation.
