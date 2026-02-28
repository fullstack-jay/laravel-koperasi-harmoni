# UPDATE

# Database Schema Documentation

## Overview

Dokumentasi ini menjelaskan struktur database lengkap untuk sistem Koperasi Harmoni yang mencakup modul: User Management, Supplier, Stock/Inventory, Purchase Order, Kitchen, Finance, QR Code, dan Activity Logs.

---

## Entity Relationship Diagram (ERD)

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                           KOPERASI HARMONI - DATABASE ERD                                │
└─────────────────────────────────────────────────────────────────────────────────────────┘

┌──────────────┐         ┌──────────────┐         ┌──────────────┐
│    roles     │         │   users      │         │  suppliers   │
├──────────────┤         ├──────────────┤         ├──────────────┤
│ id (PK)      │         │ id (PK)      │    ┌────│ id (PK)      │
│ name         │         │ first_name   │    │    │ code         │
│ slug         │         │ last_name    │    │    │ name         │
│ description  │    ┌────│ role_id (FK) │    │    │ contact_person│
│ created_at   │    │    │ email        │    │    │ phone        │
│ updated_at   │    │    │ password     │    │    │ email        │
└──────────────┘    │    │ supplier_id  │────┘    │ address      │
                     │    │ dapur_id     │         │ status       │
                     │    └──────────────┘         └──────────────┘
                     │
┌──────────────┐    │    ┌──────────────┐         ┌──────────────┐
│ system_users │    │    │   dapurs     │         │ stock_items  │
├──────────────┤    │    ├──────────────┤         ├──────────────┤
│ id (PK)      │    │    │ id (PK)      │         │ id (PK)      │
│ first_name   │    │    │ code         │         │ code         │
│ last_name    │    │    │ name         │         │ name         │
│ email        │    │    │ location     │         │ category     │
│ password     │    │    │ pic_name     │         │ unit         │
│ super_admin  │    │    │ pic_phone    │         │ min_stock    │
│ status       │    │    │ status       │         │ buy_price    │
└──────────────┘    │    └──────────────┘         │ sell_price   │
                     │                             │ current_stock│
                     │                             └──────────────┘
                     │                                      │
                     │                                      │
┌─────────────────────────────────────────────────────────┘
│                    PURCHASE ORDERS                        │
├─────────────────────────────────────────────────────────┤

┌──────────────┐      ┌──────────────────┐      ┌──────────────┐
│purchase_orders│      │purchase_order_items│     │po_status_histories│
├──────────────┤      ├──────────────────┤      ├──────────────┤
│ id (PK)      │      │ id (PK)          │      │ id (PK)      │
│ po_number    │      │ purchase_order_id│───┐  │ purchase_order_id│───┐
│ po_date      │      │ item_id          │   │  │ from_status  │   │
│ supplier_id  │──────│ estimated_qty    │   │  │ to_status    │   │
│ status       │      │ estimated_price  │   │  │ notes        │   │
│ estimated_total│     │ actual_qty       │   │  │ changed_by   │   │
│ actual_total │      └──────────────────┘   │  └──────────────┘   │
│ created_by   │                               │                     │
│ updated_by   │      ┌──────────────┐        │                     │
└──────────────┘      │ stock_batches│        │                     │
                      ├──────────────┤        │                     │
                      │ id (PK)      │        │                     │
                      │ item_id      │────────┘                     │
                      │ batch_number │                              │
                      │ quantity     │                              │
                      │ remaining_qty│                              │
                      │ expiry_date  │                              │
                      │ po_id        │──────────────────────────────┘
                      └──────────────┘

┌─────────────────────────────────────────────────────────┐
│                    KITCHEN ORDERS                        │
├─────────────────────────────────────────────────────────┤

┌──────────────┐      ┌──────────────────┐      ┌──────────────┐
│kitchen_orders │      │kitchen_order_items│     │ surat_jalans │
├──────────────┤      ├──────────────────┤      ├──────────────┤
│ id (PK)      │      │ id (PK)          │      │ id (PK)      │
│ order_number │      │ kitchen_order_id │───┐  │ sj_number    │
│ order_date   │      │ item_id          │   │  │ kitchen_order_id│──┐
│ dapur_id     │──────│ requested_qty    │   │  │ dapur_id     │  │
│ status       │      │ approved_qty     │   │  │ driver_name  │  │
│ estimated_total│    │ unit_price       │   │  │ delivered_at │  │
│ actual_total │      └──────────────────┘   │  └──────────────┘  │
│ qr_code      │                               │                  │
└──────────────┘                               │                  │
                                               │                  │
┌─────────────────────────────────────────────┘                  │
│                   FINANCE                                  │    │
├─────────────────────────────────────────────────────────┤    │
                                                              │    │
┌──────────────┐      ┌──────────────────┐                   │    │
│ transactions │      │ transaction_items│                   │    │
├──────────────┤      ├──────────────────┤                   │    │
│ id (PK)      │      │ id (PK)          │                   │    │
│ date         │      │ transaction_id   │───┐               │    │
│ type         │      │ item_id          │   │               │    │
│ category     │      │ qty              │   │               │    │
│ amount       │      │ buy_price        │   │               │    │
│ reference    │      │ sell_price       │   │               │    │
│ reference_id │──────│ subtotal         │   │               │    │
│ supplier_id  │      │ profit           │   │               │    │
│ dapur_id     │──────│ margin           │   │               │    │
│ items        │      └──────────────────┘   │               │    │
│ payment_status│                            │               │    │
└──────────────┘                            └───────────────┘    │
                                                                 │
┌────────────────────────────────────────────────────────────────┘
│                    SUPPORTING TABLES                           │
├────────────────────────────────────────────────────────────────┤

┌──────────────┐      ┌──────────────┐      ┌──────────────┐
│  stock_cards │      │ stock_alerts │      │   qr_codes   │
├──────────────┤      ├──────────────┤      ├──────────────┤
│ id (PK)      │      │ id (PK)      │      │ id (PK)      │
│ item_id      │──────│ item_id      │──────│ type         │
│ batch_id     │──────│ batch_id     │      │ reference_id │
│ date         │      │ alert_type   │      │ qr_string    │
│ type         │      │ severity     │      │ data (JSON)  │
│ reference    │      │ message      │      │ is_active    │
│ qty_in       │      │ current_qty  │      │ scanned_at   │
│ qty_out      │      │ is_resolved  │      └──────────────┘
│ balance      │      └──────────────┘
└──────────────┘

┌────────────────────────────────────────────────────────────────┐
│                    ACTIVITY LOGS                               │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐                                              │
│  │activity_logs │                                              │
│  ├──────────────┤                                              │
│  │ id (PK)      │                                              │
│  │ log_name     │                                              │
│  │ description  │                                              │
│  │ event        │                                              │
│  │ user_id      │──────┬───────────────────┐                   │
│  │ properties   │      │                   │                   │
│  │ old_values   │      │                   │                   │
│  │ new_values   │      │                   │                   │
│  │ subject_type │      │                   │                   │
│  │ subject_id   │      │                   │                   │
│  │ ip_address   │      │                   │                   │
│  │ request_id   │      │                   │                   │
│  └──────────────┘      │                   │                   │
│                        │                   │                   │
└────────────────────────┴───────────────────┴───────────────────┘
```

---

## Table Details

### 1. Authentication & Authorization

#### 1.1 `roles`
Tabel untuk menyimpan data role/permission user.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | Primary Key, Auto Increment | ID Role |
| name | VARCHAR | Unique | Nama role |
| slug | VARCHAR | Unique | Slug role |
| description | TEXT | Nullable | Deskripsi role |
| created_at | BIGINT | useCurrent() | Timestamp pembuatan |
| updated_at | BIGINT | useCurrent() | Timestamp update |

**Default Roles:**
- Super Admin - Full system access
- Admin - Administrative access
- Keuangan - Finance department access
- Gudang - Warehouse management access
- Dapur - Kitchen management access
- Procurement - Procurement and purchasing access

#### 1.2 `users`
Tabel untuk menyimpan data user/karyawan koperasi.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID User |
| first_name | VARCHAR | - | Nama depan |
| last_name | VARCHAR | - | Nama belakang |
| username | VARCHAR | - | Username login |
| avatar | VARCHAR | Nullable | Path foto profil |
| phone | VARCHAR | Nullable | Nomor telepon |
| email | VARCHAR | Unique | Email user |
| email_verified_at | TIMESTAMP | Nullable | Email verification timestamp |
| password | VARCHAR | - | Hashed password |
| role_id | BIGINT | Foreign Key → roles.id | Role user |
| oauth | BOOLEAN | Default: false | OAuth login flag |
| provider_name | VARCHAR | Nullable | OAuth provider name |
| provider_id | VARCHAR | Nullable | OAuth provider ID |
| provider_type | VARCHAR | Nullable | OAuth provider type |
| google_access_token_json | TEXT | Nullable | Google access token |
| verification_token | TEXT | Nullable | Verification token |
| verification_token_expiry | DATETIME | Nullable | Token expiry |
| supplier_id | UUID | Nullable, Foreign Key → suppliers.id | Supplier terkait (jika user adalah supplier) |
| dapur_id | UUID | Nullable, Foreign Key → dapurs.id | Dapur terkait (jika user adalah dapur) |
| remember_token | VARCHAR | Nullable | Remember me token |
| created_at | BIGINT | - | Timestamp pembuatan |
| updated_at | BIGINT | - | Timestamp update |
| deleted_at | BIGINT | Nullable | Soft delete timestamp |

**Relationships:**
- `role` → `roles` (Many-to-One)
- `supplier` → `suppliers` (Many-to-One, nullable)
- `dapur` → `dapurs` (Many-to-One, nullable)

#### 1.3 `system_users`
Tabel untuk menyimpan data admin sistem.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID System User |
| first_name | VARCHAR | - | Nama depan |
| last_name | VARCHAR | - | Nama belakang |
| avatar | VARCHAR | Nullable | Path foto profil |
| phone | VARCHAR | Nullable | Nomor telepon |
| email | VARCHAR | Unique | Email admin |
| email_verified_at | TIMESTAMP | Nullable | Email verification timestamp |
| password | VARCHAR | Nullable | Hashed password |
| verification_token | TEXT | Nullable | Verification token |
| super_admin | BOOLEAN | Default: false | Super admin flag |
| verification_token_expiry | DATETIME | Nullable | Token expiry |
| status | VARCHAR | Default: 'PENDING' | Status admin |
| remember_token | VARCHAR | Nullable | Remember me token |
| created_at | BIGINT | useCurrent() | Timestamp pembuatan |
| updated_at | BIGINT | useCurrent() | Timestamp update |

**Relationships:**
- `roles` → `roles` (Many-to-Many via `role_user`)

#### 1.4 `role_user`
Tabel pivot untuk relasi many-to-many antara system_users dan roles.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | Primary Key, Auto Increment | ID |
| user_id | UUID | Foreign Key → system_users.id | User ID |
| role_id | BIGINT | Foreign Key → roles.id | Role ID |
| created_at | BIGINT | useCurrent() | Timestamp pembuatan |
| updated_at | BIGINT | useCurrent() | Timestamp update |

**Unique Constraint:** (user_id, role_id)

#### 1.5 `permissions`
Tabel untuk menyimpan permissions.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | Primary Key, Auto Increment | ID Permission |
| name | VARCHAR | Unique | Nama permission |
| slug | VARCHAR | Unique | Slug permission |
| description | TEXT | Nullable | Deskripsi permission |
| created_at | BIGINT | useCurrent() | Timestamp pembuatan |
| updated_at | BIGINT | useCurrent() | Timestamp update |

**Relationships:**
- `roles` → `roles` (Many-to-Many via `permission_role`)

#### 1.6 `permission_role`
Tabel pivot untuk relasi many-to-many antara permissions dan roles.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | Primary Key, Auto Increment | ID |
| permission_id | BIGINT | Foreign Key → permissions.id | Permission ID |
| role_id | BIGINT | Foreign Key → roles.id | Role ID |
| created_at | BIGINT | useCurrent() | Timestamp pembuatan |
| updated_at | BIGINT | useCurrent() | Timestamp update |

#### 1.7 `personal_access_tokens`
Tabel untuk menyimpan API tokens (Laravel Sanctum).

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | Primary Key, Auto Increment | ID Token |
| tokenable_type | VARCHAR | - | Model type (polymorphic) |
| tokenable_id | VARCHAR | - | Model ID (polymorphic) |
| name | VARCHAR | - | Token name |
| token | VARCHAR | Unique | Token hash |
| abilities | TEXT | Nullable | JSON abilities |
| last_used_at | TIMESTAMP | Nullable | Last used timestamp |
| expires_at | TIMESTAMP | Nullable | Expiry timestamp |
| refresh_token | VARCHAR | Nullable | Refresh token |

#### 1.8 `sessions`
Tabel untuk menyimpan session data.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | VARCHAR | Primary Key | Session ID |
| user_id | BIGINT | Nullable, Foreign Key → users.id | User ID |
| ip_address | VARCHAR(45) | Nullable | IP address |
| user_agent | TEXT | Nullable | User agent string |
| payload | LONGTEXT | - | Session payload |
| last_activity | INTEGER | Indexed | Last activity timestamp |

#### 1.9 `password_reset_tokens`
Tabel untuk menyimpan password reset tokens.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| email | VARCHAR | Primary Key | Email user |
| token | VARCHAR | - | Reset token |
| created_at | TIMESTAMP | Nullable | Timestamp pembuatan |

---

### 2. Supplier Management

#### 2.1 `supplier_items`
Tabel untuk menyimpan data barang yang dimiliki oleh supplier. Supplier dapat mengupdate harga jual, yang otomatis menjadi harga beli untuk koperasi.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Item |
| supplier_id | UUID | Foreign Key → suppliers.id | ID Supplier pemilik barang |
| code | VARCHAR | Unique | Kode barang (SKU) |
| name | VARCHAR | - | Nama barang |
| category | VARCHAR | - | Kategori barang |
| unit | VARCHAR | - | Satuan (kg, liter, pcs, etc) |
| min_stock | INTEGER | Default: 0 | Minimum stok |
| max_stock | INTEGER | Default: 0 | Maximum stok |
| buy_price | DECIMAL(10,2) | Default: 0 | Harga beli (update otomatis dari sell_price supplier) |
| sell_price | DECIMAL(10,2) | Default: 0 | Harga jual (diupdate oleh supplier) |
| avg_weight | DECIMAL(5,2) | Nullable | Rata-rata berat (kg) |
| status | ENUM | Default: 'active' | Status barang (active/inactive) |
| created_by | UUID | Nullable, Foreign Key → users.id | Dibuat oleh (super_admin) |
| updated_by | UUID | Nullable, Foreign Key → users.id | Diupdate terakhir kali |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | **Timestamp update harga** |

**Status Values:**
- `active` - Barang aktif
- `inactive` - Barang tidak aktif

**Relationships:**
- `supplier` → `suppliers` (Many-to-One)
- `creator` → `users` (Many-to-One, via created_by)
- `updater` → `users` (Many-to-One, via updated_by)

**Business Logic:**
- **Super Admin** (Koperasi): CRUD lengkap (create, read, update, delete barang)
- **Supplier**: Hanya bisa update `sell_price` (harga jual)
- Ketika supplier update `sell_price`, backend otomatis update `buy_price` dengan nilai yang sama
- `updated_at` menunjukkan kapan terakhir kali harga diupdate

**Access Control:**
- Role **SUPER_ADMIN**: Full CRUD (Create, Read, Update, Delete)
- Role **PEMASOK**: Read dan Update (hanya `sell_price`)

**Indexes:**
- Composite index: (supplier_id, status)
- Index: code
- Index: updated_at (untuk sorting berdasarkan tanggal update)

---

#### Update tabel tanggal 28 Februari

Tabel `supplier_items` ditambahkan untuk mengelola barang-barang dari supplier dengan fitur:

1. **Master Data Barang Supplier**
    - Super Admin dapat menambahkan barang milik supplier
    - Setiap barang memiliki informasi lengkap: kode, nama, kategori, satuan, stok min/max, harga
    - Barang terhubung dengan supplier melalui `supplier_id`

2. **Update Harga oleh Supplier**
    - Supplier dapat mengupdate harga jual (`sell_price`) melalui interface khusus
    - Ketika supplier update harga, backend otomatis menyamakan `buy_price` dengan `sell_price`
    - `updated_at` mencatat waktu terakhir harga diupdate
    - Super Admin dan Supplier dapat melihat kapan harga terakhir diupdate

3. **Relasi dengan Tabel Lain**
    - `supplier_id` → `suppliers.id` (Setiap barang milik satu supplier)
    - Digunakan sebagai referensi harga saat membuat Purchase Order
    - Harga beli di PO akan mengambil nilai dari `buy_price`

4. **Kolom updated_at**
    - Menunjukkan timestamp terakhir kali data barang diupdate
    - Otomatis diperbarui oleh backend ketika ada perubahan data
    - Ditampilkan di UI sebagai "Tgl Update Harga"

---

#### 2.2 `suppliers`
Tabel untuk menyimpan data supplier.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Supplier |
| code | VARCHAR | Unique | Kode supplier (auto-generated) |
| name | VARCHAR | - | Nama supplier |
| contact_person | VARCHAR | Nullable | Nama kontak |
| phone | VARCHAR | Nullable | Nomor telepon |
| email | VARCHAR | Nullable | Email |
| address | TEXT | Nullable | Alamat lengkap |
| district | VARCHAR | Nullable | Kecamatan/Kabupaten |
| type | ENUM | Nullable | Tipe supplier |
| status | ENUM | Default: 'active' | Status supplier (active/inactive) |
| created_by | UUID | Nullable, Foreign Key → users.id | Dibuat oleh |
| updated_by | UUID | Nullable, Foreign Key → users.id | Diupdate oleh |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |
| deleted_at | TIMESTAMP | Nullable | Soft delete timestamp |

**Relationships:**
- `purchaseOrders` → `purchase_orders` (One-to-Many)
- `users` → `users` (One-to-Many, via supplier_id)

---

### 3. Kitchen Management

#### 3.1 `dapurs`
Tabel untuk menyimpan data dapur/kitchen.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Dapur |
| code | VARCHAR | Unique | Kode dapur (auto-generated) |
| name | VARCHAR | - | Nama dapur |
| location | VARCHAR | Nullable | Lokasi dapur |
| pic_name | VARCHAR | Nullable | Nama PIC |
| pic_phone | VARCHAR | Nullable | Telepon PIC |
| status | ENUM | Default: 'active' | Status dapur (active/inactive) |
| created_by | UUID | Nullable, Foreign Key → users.id | Dibuat oleh |
| updated_by | UUID | Nullable, Foreign Key → users.id | Diupdate oleh |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |
| deleted_at | TIMESTAMP | Nullable | Soft delete timestamp |

**Relationships:**
- `kitchenOrders` → `kitchen_orders` (One-to-Many)
- `suratJalans` → `surat_jalans` (One-to-Many)
- `users` → `users` (One-to-Many, via dapur_id)

#### 3.2 `kitchen_orders`
Tabel untuk menyimpan data pesanan dapur.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Kitchen Order |
| order_number | VARCHAR | Unique | Nomor order (auto-generated) |
| order_date | DATE | - | Tanggal order |
| dapur_id | UUID | Foreign Key → dapurs.id | ID Dapur |
| status | ENUM | Default: 'draft' | Status order |
| estimated_total | DECIMAL(10,2) | Default: 0 | Total estimasi |
| actual_total | DECIMAL(10,2) | Nullable | Total aktual |
| notes | TEXT | Nullable | Catatan |
| rejection_reason | TEXT | Nullable | Alasan penolakan |
| sent_at | TIMESTAMP | Nullable | Waktu pengiriman |
| processed_at | TIMESTAMP | Nullable | Waktu proses |
| delivered_at | TIMESTAMP | Nullable | Waktu pengantaran |
| received_at | TIMESTAMP | Nullable | Waktu penerimaan |
| qr_code | VARCHAR | Nullable | QR Code string |
| created_by | UUID | Nullable, Foreign Key → users.id | Dibuat oleh |
| updated_by | UUID | Nullable, Foreign Key → users.id | Diupdate oleh |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |
| deleted_at | TIMESTAMP | Nullable | Soft delete timestamp |

**Status Values:**
- `draft` - Draft order
- `terkirim` - Order dikirim ke gudang
- `diproses` - Order sedang diproses
- `diterima_dapur` - Order diterima dapur
- `dibatalkan` - Order dibatalkan

**Relationships:**
- `dapur` → `dapurs` (Many-to-One)
- `items` → `kitchen_order_items` (One-to-Many)
- `suratJalan` → `surat_jalans` (One-to-Many)

#### 3.3 `kitchen_order_items`
Tabel untuk menyimpan item pesanan dapur.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Item |
| kitchen_order_id | UUID | Foreign Key → kitchen_orders.id | ID Kitchen Order |
| item_id | UUID | Foreign Key → stock_items.id | ID Stock Item |
| requested_qty | INTEGER | - | Jumlah diminta |
| approved_qty | INTEGER | Nullable | Jumlah disetujui |
| unit_price | DECIMAL(10,2) | - | Harga per unit |
| subtotal | DECIMAL(10,2) | - | Subtotal |
| buy_price | DECIMAL(10,2) | Nullable | Harga beli |
| profit | DECIMAL(10,2) | Nullable | Profit |
| notes | TEXT | Nullable | Catatan |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |

**Relationships:**
- `kitchenOrder` → `kitchen_orders` (Many-to-One)
- `stockItem` → `stock_items` (Many-to-One)

#### 3.4 `surat_jalans`
Tabel untuk menyimpan data surat jalan.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Surat Jalan |
| sj_number | VARCHAR | Unique | Nomor surat jalan (auto-generated) |
| kitchen_order_id | UUID | Foreign Key → kitchen_orders.id | ID Kitchen Order |
| dapur_id | UUID | Foreign Key → dapurs.id | ID Dapur |
| sj_date | DATE | - | Tanggal surat jalan |
| driver_name | VARCHAR | Nullable | Nama driver |
| vehicle_plate | VARCHAR | Nullable | Plat nomor kendaraan |
| notes | TEXT | Nullable | Catatan |
| delivered_at | TIMESTAMP | Nullable | Waktu terkirim |
| receiver_name | VARCHAR | Nullable | Nama penerima |
| receiver_notes | TEXT | Nullable | Catatan penerima |
| created_by | UUID | Nullable, Foreign Key → users.id | Dibuat oleh |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |

**Relationships:**
- `kitchenOrder` → `kitchen_orders` (Many-to-One)
- `dapur` → `dapurs` (Many-to-One)

---

### 4. Stock & Inventory Management

#### 4.1 `stock_items`
Tabel master untuk menyimpan data item/barang.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Stock Item |
| code | VARCHAR | Unique | Kode barang (SKU) |
| name | VARCHAR | - | Nama barang |
| category | VARCHAR | Nullable | Kategori barang |
| unit | VARCHAR | Nullable | Satuan (kg, liter, pcs, etc) |
| min_stock | INTEGER | Default: 10 | Minimum stok |
| buy_price | DECIMAL(10,2) | Default: 0 | Harga beli terakhir |
| sell_price | DECIMAL(10,2) | Default: 0 | Harga jual |
| current_stock | INTEGER | Default: 0 | Stok saat ini (denormalized) |
| created_by | UUID | Nullable, Foreign Key → users.id | Dibuat oleh |
| updated_by | UUID | Nullable, Foreign Key → users.id | Diupdate oleh |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |
| deleted_at | TIMESTAMP | Nullable | Soft delete timestamp |

**Relationships:**
- `batches` → `stock_batches` (One-to-Many)
- `stockCards` → `stock_cards` (One-to-Many)
- `alerts` → `stock_alerts` (One-to-Many)

#### 4.2 `stock_batches`
Tabel untuk menyimpan data batch barang (FEFO - First Expired First Out).

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Batch |
| item_id | UUID | Foreign Key → stock_items.id | ID Stock Item |
| batch_number | VARCHAR | Unique | Nomor batch |
| quantity | INTEGER | - | Jumlah awal |
| remaining_qty | INTEGER | - | Sisa jumlah tersedia |
| buy_price | DECIMAL(10,2) | - | Harga beli per batch |
| expiry_date | DATE | - | Tanggal kadaluarsa |
| location | VARCHAR | Nullable | Lokasi penyimpanan |
| status | ENUM | Default: 'available' | Status batch |
| received_date | DATE | - | Tanggal penerimaan |
| po_id | UUID | Nullable | ID Purchase Order |
| created_by | UUID | Nullable, Foreign Key → users.id | Dibuat oleh |
| updated_by | UUID | Nullable, Foreign Key → users.id | Diupdate oleh |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |

**Status Values:**
- `available` - Tersedia untuk dijual
- `allocated` - Sudah dialokasikan untuk order
- `expired` - Sudah kadaluarsa

**Relationships:**
- `item` → `stock_items` (Many-to-One)
- `stockCards` → `stock_cards` (One-to-Many)
- `alerts` → `stock_alerts` (One-to-Many)

**Indexes:**
- Composite index: (item_id, expiry_date) untuk FEFO query
- Index: status

#### 4.3 `stock_cards`
Tabel untuk menyimpan kartu stok (movement history).

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Stock Card |
| item_id | UUID | Foreign Key → stock_items.id | ID Stock Item |
| batch_id | UUID | Nullable, Foreign Key → stock_batches.id | ID Batch |
| date | DATE | - | Tanggal transaksi |
| type | ENUM | - | Tipe transaksi |
| reference | VARCHAR | Nullable | Referensi (PO Number, Order Number, etc) |
| reference_id | UUID | Nullable | ID Referensi |
| qty_in | INTEGER | Default: 0 | Jumlah masuk |
| qty_out | INTEGER | Default: 0 | Jumlah keluar |
| balance | INTEGER | - | Saldo berjalan |
| notes | TEXT | Nullable | Catatan |
| created_by | UUID | Foreign Key → users.id | Dibuat oleh |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |

**Type Values:**
- `in` - Stok masuk (dari PO)
- `out` - Stok keluar (ke Kitchen Order)
- `adjustment` - Penyesuaian stok
- `opname` - Opname stok

**Relationships:**
- `item` → `stock_items` (Many-to-One)
- `batch` → `stock_batches` (Many-to-One, nullable)

**Indexes:**
- Composite index: (item_id, date)

#### 4.4 `stock_alerts`
Tabel untuk menyimpan alert stok.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Alert |
| item_id | UUID | Foreign Key → stock_items.id | ID Stock Item |
| batch_id | UUID | Nullable, Foreign Key → stock_batches.id | ID Batch |
| alert_type | ENUM | - | Tipe alert |
| severity | ENUM | - | Tingkat severity |
| message | TEXT | - | Pesan alert |
| current_qty | INTEGER | - | Jumlah saat ini |
| threshold | INTEGER | Nullable | Batas threshold |
| expiry_date | DATE | Nullable | Tanggal kadaluarsa |
| days_to_expiry | INTEGER | Nullable | Hari ke kadaluarsa |
| is_resolved | BOOLEAN | Default: false | Status resolved |
| resolved_at | TIMESTAMP | Nullable | Waktu resolve |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |

**Alert Type Values:**
- `low_stock` - Stok di bawah minimum
- `out_of_stock` - Stok habis
- `expired` - Sudah kadaluarsa
- `expiring_soon` - Akan kadaluarsa (dalam 7/14/30 hari)

**Severity Values:**
- `critical` - Kritis (out of stock, expired)
- `warning` - Peringatan (low stock, expiring soon)
- `info` - Informasi

**Relationships:**
- `item` → `stock_items` (Many-to-One)
- `batch` → `stock_batches` (Many-to-One, nullable)

**Indexes:**
- Index: alert_type
- Index: is_resolved

---

### 5. Purchase Order Management

#### 5.1 `purchase_orders`
Tabel untuk menyimpan data purchase order.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Purchase Order |
| po_number | VARCHAR | Unique | Nomor PO (auto-generated: PO-YYYYMMDD-XXXX) |
| po_date | DATE | - | Tanggal PO |
| supplier_id | UUID | Foreign Key → suppliers.id | ID Supplier |
| status | ENUM | Default: 'draft' | Status PO |
| estimated_total | DECIMAL(10,2) | Default: 0 | Total estimasi |
| actual_total | DECIMAL(10,2) | Nullable | Total aktual |
| invoice_number | VARCHAR | Nullable | Nomor invoice supplier |
| estimated_delivery_date | DATE | - | Perkiraan tanggal pengiriman |
| actual_delivery_date | DATE | Nullable | Tanggal pengiriman aktual |
| notes | TEXT | Nullable | Catatan |
| rejection_reason | TEXT | Nullable | Alasan penolakan/pembatalan |
| sent_to_supplier_at | TIMESTAMP | Nullable | Waktu kirim ke supplier |
| confirmed_by_supplier_at | TIMESTAMP | Nullable | Waktu konfirmasi supplier |
| confirmed_by_koperasi_at | TIMESTAMP | Nullable | Waktu konfirmasi koperasi |
| received_at | TIMESTAMP | Nullable | Waktu penerimaan barang |
| created_by | UUID | Nullable, Foreign Key → users.id | Dibuat oleh |
| updated_by | UUID | Nullable, Foreign Key → users.id | Diupdate oleh |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |
| deleted_at | TIMESTAMP | Nullable | Soft delete timestamp |

**Status Values:**
- `draft` - Draft PO
- `terkirim` - PO dikirim ke supplier
- `dikonfirmasi_supplier` - Dikonfirmasi supplier
- `dikonfirmasi_koperasi` - Dikonfirmasi koperasi
- `selesai` - Selesai (barang diterima)
- `dibatalkan` - Dibatalkan

**Relationships:**
- `supplier` → `suppliers` (Many-to-One)
- `items` → `purchase_order_items` (One-to-Many)
- `statusHistories` → `po_status_histories` (One-to-Many)

**Indexes:**
- Composite index: (supplier_id, status)
- Index: po_date

#### 5.2 `purchase_order_items`
Tabel untuk menyimpan item purchase order.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Item |
| purchase_order_id | UUID | Foreign Key → purchase_orders.id | ID Purchase Order |
| item_id | UUID | Foreign Key → stock_items.id | ID Stock Item |
| estimated_unit_price | DECIMAL(10,2) | Default: 0 | Harga estimasi per unit |
| estimated_qty | INTEGER | Default: 0 | Jumlah estimasi |
| estimated_subtotal | DECIMAL(10,2) | Default: 0 | Subtotal estimasi |
| actual_unit_price | DECIMAL(10,2) | Nullable | Harga aktual per unit |
| actual_qty | INTEGER | Nullable | Jumlah aktual |
| actual_subtotal | DECIMAL(10,2) | Nullable | Subtotal aktual |
| notes | TEXT | Nullable | Catatan |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |

**Relationships:**
- `purchaseOrder` → `purchase_orders` (Many-to-One)
- `stockItem` → `stock_items` (Many-to-One)

**Indexes:**
- Index: purchase_order_id

#### 5.3 `po_status_histories`
Tabel untuk menyimpan riwayat perubahan status PO.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID History |
| purchase_order_id | UUID | Foreign Key → purchase_orders.id | ID Purchase Order |
| from_status | VARCHAR | Nullable | Status sebelumnya |
| to_status | VARCHAR | - | Status baru |
| notes | TEXT | Nullable | Catatan perubahan |
| changed_by | UUID | Nullable, Foreign Key → users.id | Diubah oleh |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |

**Relationships:**
- `purchaseOrder` → `purchase_orders` (Many-to-One)
- `changedBy` → `users` (Many-to-One, nullable)

**Indexes:**
- Index: purchase_order_id

---

### 6. Finance & Transaction Management

#### 6.1 `transactions`
Tabel untuk menyimpan data transaksi keuangan.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Transaction |
| date | DATE | - | Tanggal transaksi |
| type | ENUM | - | Tipe transaksi |
| category | ENUM | - | Kategori transaksi |
| amount | DECIMAL(10,2) | - | Jumlah total |
| profit | DECIMAL(10,2) | Nullable | Total profit |
| margin | DECIMAL(5,2) | Nullable | Margin percentage |
| reference | VARCHAR | - | Referensi (PO Number, Order Number) |
| reference_id | UUID | - | ID Referensi |
| supplier_id | UUID | Nullable, Foreign Key → suppliers.id | ID Supplier (untuk purchase) |
| dapur_id | UUID | Nullable, Foreign Key → dapurs.id | ID Dapur (untuk sales) |
| items | JSON | - | Detail items (JSON) |
| payment_status | ENUM | Default: 'pending' | Status pembayaran |
| payment_date | DATE | Nullable | Tanggal pembayaran |
| notes | TEXT | Nullable | Catatan |
| created_by | UUID | Nullable, Foreign Key → users.id | Dibuat oleh |
| updated_by | UUID | Nullable, Foreign Key → users.id | Diupdate oleh |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |
| deleted_at | TIMESTAMP | Nullable | Soft delete timestamp |

**Type Values:**
- `purchase` - Pembelian ke supplier
- `sales` - Penjualan ke dapur

**Category Values:**
- `po` - Dari Purchase Order
- `kitchen_order` - Dari Kitchen Order
- `adjustment` - Penyesuaian

**Payment Status Values:**
- `pending` - Belum dibayar
- `paid` - Sudah dibayar
- `overdue` - Terlambat bayar

**Relationships:**
- `transactionItems` → `transaction_items` (One-to-Many)
- `supplier` → `suppliers` (Many-to-One, nullable)
- `dapur` → `dapurs` (Many-to-One, nullable)

**Indexes:**
- Composite index: (date, type)
- Index: reference
- Index: type
- Index: category

#### 6.2 `transaction_items`
Tabel untuk menyimpan detail item transaksi.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Item |
| transaction_id | UUID | Foreign Key → transactions.id | ID Transaction |
| item_id | UUID | Foreign Key → stock_items.id | ID Stock Item |
| qty | INTEGER | - | Jumlah |
| buy_price | DECIMAL(10,2) | - | Harga beli |
| sell_price | DECIMAL(10,2) | - | Harga jual |
| subtotal | DECIMAL(10,2) | - | Subtotal |
| profit | DECIMAL(10,2) | - | Profit |
| margin | DECIMAL(5,2) | - | Margin percentage |
| batch_details | JSON | Nullable | Detail batch (JSON) |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |

**Relationships:**
- `transaction` → `transactions` (Many-to-One)
- `stockItem` → `stock_items` (Many-to-One)

**Indexes:**
- Index: transaction_id

---

### 7. QR Code Management

#### 7.1 `qr_codes`
Tabel untuk menyimpan data QR Code.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID QR Code |
| qr_string | VARCHAR | Unique | QR Code string |
| type | ENUM | - | Tipe QR Code |
| reference_id | UUID | - | ID Referensi |
| reference_type | VARCHAR | - | Tipe referensi (polymorphic) |
| data | JSON | - | Data tambahan (JSON) |
| image_path | VARCHAR | - | Path gambar QR Code |
| scanned_at | TIMESTAMP | Nullable | Waktu scan |
| expires_at | TIMESTAMP | Nullable | Waktu expired |
| is_active | BOOLEAN | Default: true | Status aktif |
| created_by | UUID | Nullable, Foreign Key → users.id | Dibuat oleh |
| created_at | TIMESTAMP | - | Timestamp pembuatan |
| updated_at | TIMESTAMP | - | Timestamp update |

**Type Values:**
- `KITCHEN_DELIVERY` - QR Code untuk pengantaran ke dapur
- `PURCHASE_RECEIPT` - QR Code untuk penerimaan barang dari supplier
- `STOCK_TRANSFER` - QR Code untuk transfer stok

**Relationships:**
- Polymorphic relation ke berbagai model berdasarkan `reference_type`

**Indexes:**
- Composite index: (type, reference_id)
- Index: qr_string

---

### 8. Activity & Audit Logs

#### 8.1 `activity_logs`
Tabel untuk menyimpan log aktivitas user (Spatie Activity Log pattern).

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Log |
| log_name | VARCHAR | Nullable, Indexed | Nama log (grouping) |
| description | TEXT | - | Deskripsi aktivitas |
| event | VARCHAR | Nullable, Indexed | Event (created, updated, deleted, etc) |
| user_id | UUID | Nullable, Indexed | ID User yang melakukan aksi |
| properties | JSONB | Nullable | Data tambahan (JSON) |
| old_values | JSONB | Nullable | Nilai sebelum update |
| new_values | JSONB | Nullable | Nilai sesudah update |
| batch_uuid | VARCHAR | Nullable, Indexed | UUID untuk grouping related activities |
| ip_address | VARCHAR(45) | Nullable | IP address user |
| user_agent | TEXT | Nullable | User agent string |
| session_id | VARCHAR | Nullable | Session ID |
| request_id | VARCHAR | Nullable, Indexed | Request ID untuk tracing |
| subject_type | VARCHAR | Nullable | Tipe subject (polymorphic) |
| subject_id | UUID | Nullable | ID subject (polymorphic) |
| created_at | BIGINT | useCurrent() | Timestamp pembuatan |
| updated_at | BIGINT | useCurrent() | Timestamp update |

**Relationships:**
- `user` → `users` (Many-to-One, nullable)
- Polymorphic relation ke berbagai model via (subject_type, subject_id)

**Indexes:**
- Composite index: (subject_type, subject_id)
- Composite index: (user_id, created_at)
- Composite index: (log_name, created_at)
- Composite index: (event, created_at)

#### 8.2 `admin_activity_logs`
Tabel khusus untuk log aktivitas admin sistem.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | UUID | Primary Key | ID Log |
| admin_id | UUID | Foreign Key → system_users.id | ID Admin |
| action | VARCHAR | - | Aksi yang dilakukan |
| description | TEXT | Nullable | Deskripsi aktivitas |
| ip_address | VARCHAR(45) | Nullable | IP address |
| user_agent | TEXT | Nullable | User agent |
| created_at | TIMESTAMP | - | Timestamp pembuatan |

**Relationships:**
- `admin` → `system_users` (Many-to-One)

---

## Relationships Summary

### User & Authentication
```
users (1) ──< (N) role_user >── (N) roles
roles (1) ──< (N) permission_role >── (N) permissions
system_users (1) ──< (N) role_user >── (N) roles
users (N) ──> (1) suppliers (via supplier_id)
users (N) ──> (1) dapurs (via dapur_id)
```

### Supplier Management
```
suppliers (1) ──< (N) supplier_items
suppliers (1) ──< (N) purchase_orders
suppliers (1) ──< (N) users (via supplier_id)
suppliers (1) ──< (N) transactions (via supplier_id)

supplier_items (N) ──> (1) suppliers (via supplier_id)
supplier_items (1) ──> (1) users (via created_by)
supplier_items (1) ──> (1) users (via updated_by)
```

### Kitchen Management
```
dapurs (1) ──< (N) kitchen_orders
dapurs (1) ──< (N) surat_jalans
dapurs (1) ──< (N) users (via dapur_id)
dapurs (1) ──< (N) transactions (via dapur_id)

kitchen_orders (1) ──< (N) kitchen_order_items
kitchen_orders (1) ──< (N) surat_jalans
kitchen_orders (1) ──< (N) transactions (via reference_id)
```

### Stock Management
```
stock_items (1) ──< (N) stock_batches
stock_items (1) ──< (N) stock_cards
stock_items (1) ──< (N) stock_alerts
stock_items (1) ──< (N) purchase_order_items
stock_items (1) ──< (N) kitchen_order_items
stock_items (1) ──< (N) transaction_items

stock_batches (1) ──< (N) stock_cards (via batch_id)
stock_batches (1) ──< (N) stock_alerts

supplier_items (1) ──< (N) suppliers (via supplier_id)
supplier_items → Digunakan sebagai referensi harga untuk Purchase Orders
```

### Purchase Order Management
```
purchase_orders (1) ──< (N) purchase_order_items
purchase_orders (1) ──< (N) po_status_histories
purchase_orders (1) ──< (N) stock_batches (via po_id)
purchase_orders (1) ──< (N) transactions (via reference_id)
purchase_orders (N) ──> (1) suppliers
```

### Finance Management
```
transactions (1) ──< (N) transaction_items
transactions (N) ──> (1) suppliers (nullable)
transactions (N) ──> (1) dapurs (nullable)
```

---

## Key Design Patterns

### 1. UUID Primary Keys
Semua tabel utama menggunakan UUID sebagai primary key untuk:
- Distribusi system friendly
- Security (tidak predictable)
- Global uniqueness

### 2. Soft Deletes
Tabel-tabel utama mendukung soft delete via `deleted_at` column:
- users
- suppliers
- dapurs
- purchase_orders
- kitchen_orders
- stock_items
- supplier_items
- transactions

### 3. Audit Columns
Tabel-tabel penting memiliki audit columns:
- `created_by` - User yang membuat record
- `updated_by` - User yang terakhir mengupdate record

### 4. Status Enums
Beberapa tabel menggunakan ENUM untuk status management:
- `purchase_orders.status` - Draft → Terkirim → Dikonfirmasi → Selesai
- `kitchen_orders.status` - Draft → Terkirim → Diproses → Diterima
- `suppliers.status` - Active/Inactive
- `dapurs.status` - Active/Inactive
- `supplier_items.status` - Active/Inactive
- `stock_batches.status` - Available/Allocated/Expired

### 5. Timestamp Tracking
Multiple timestamp columns untuk tracking alur:
- `purchase_orders`: sent_to_supplier_at, confirmed_by_supplier_at, confirmed_by_koperasi_at, received_at
- `kitchen_orders`: sent_at, processed_at, delivered_at, received_at
- `stock_alerts`: resolved_at
- `qr_codes`: scanned_at, expires_at

### 6. Polymorphic Relations
Beberapa tabel menggunakan polymorphic pattern:
- `qr_codes` - reference ke berbagai tipe entity
- `activity_logs` - subject dapat berbagai tipe model

### 7. JSON Columns
Beberapa tabel menyimpan data dalam JSON format:
- `transactions.items` - Detail items transaksi
- `transaction_items.batch_details` - Detail batch allocation
- `qr_codes.data` - Data tambahan QR code
- `activity_logs.properties` - Property tambahan
- `activity_logs.old_values` - Nilai sebelum update
- `activity_logs.new_values` - Nilai sesudah update

---

## Index Strategy

### Composite Indexes
- `supplier_items`: (supplier_id, status) - **CRITICAL for supplier items filtering**
- `purchase_orders`: (supplier_id, status)
- `stock_batches`: (item_id, expiry_date) - **CRITICAL for FEFO**
- `stock_cards`: (item_id, date)
- `kitchen_orders`: (dapur_id, status)
- `transactions`: (date, type)
- `activity_logs`: (subject_type, subject_id), (user_id, created_at), (log_name, created_at), (event, created_at)

### Single Column Indexes
- All foreign keys
- All unique columns (code, number, email, etc.)
- All status columns
- All date columns for filtering

---

## Data Integrity

### Foreign Key Constraints
- ON DELETE CASCADE untuk child records yang boleh auto-delete
- ON DELETE RESTRICT untuk child records yang penting
- ON DELETE SET NULL untuk opsional relationships

### Validation
- ENUM columns untuk valid status values
- NOT NULL constraints untuk required fields
- UNIQUE constraints untuk business keys (code, number, email)

---

## Migration History

### Important Migration Files
1. `0001_01_01_000000_create_users_table.php` - Base users table
2. `2023_01_01_000000_create_admins_table.php` - System users table
3. `2026_02_26_110000_create_roles_tables.php` - Roles & permissions
4. `2025_02_26_000001_create_suppliers_table.php` - Supplier management
5. `2025_02_26_000002_create_stock_items_table.php` - Stock master
6. `2025_02_26_000003_create_stock_batches_table.php` - Batch management
7. `2025_02_26_000004_create_stock_cards_table.php` - Stock movement
8. `2025_02_26_000005_create_stock_alerts_table.php` - Stock alerts
9. `2025_02_26_000006_create_purchase_orders_table.php` - PO header
10. `2025_02_26_000007_create_purchase_order_items_table.php` - PO items
11. `2025_02_26_000008_create_po_status_histories_table.php` - PO status tracking
12. `2025_02_26_000009_create_qr_codes_table.php` - QR code management
13. `2025_02_26_000010_create_dapur_table.php` - Kitchen master
14. `2025_02_26_000011_create_kitchen_orders_table.php` - Kitchen orders
15. `2025_02_26_000012_create_kitchen_order_items_table.php` - Kitchen order items
16. `2025_02_26_000013_create_surat_jalan_table.php` - Delivery notes
17. `2025_02_26_000014_create_transactions_table.php` - Finance transactions
18. `2025_02_26_000015_create_transaction_items_table.php` - Transaction items
19. `2025_04_08_091743_create_activity_logs_table.php` - Activity logging
20. `2026_02_27_151432_fix_activity_logs_timestamps_columns.php` - Fix timestamps
21. `2026_02_27_162136_add_severity_to_activity_logs_table.php` - Add severity

---

## Best Practices

### 1. Always Use UUIDs
Untuk semua tabel utama gunakan UUID primary keys.

### 2. Soft Delete
Implement soft delete untuk data penting yang tidak boleh hilang.

### 3. Audit Trail
Selalu track `created_by` dan `updated_by` untuk accountability.

### 4. Status Management
Gunakan ENUM untuk status dengan jelas defined transitions.

### 5. Index Strategy
Create composite indexes untuk query patterns yang sering digunakan bersamaan.

### 6. JSON Usage
Gunakan JSON columns untuk flexible data tapi tetap maintain referential integrity.

### 7. Timestamp Columns
Gunakan multiple timestamp columns untuk tracking business process flow.

---

## Version History

| Version | Date | Description |
|---------|------|-------------|
| 1.0.0 | 2026-02-28 | Initial complete database schema documentation |
| 1.1.0 | 2026-02-28 | Add `supplier_items` table for supplier price management |
