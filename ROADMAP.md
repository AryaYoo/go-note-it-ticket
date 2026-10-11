# Go-Note IT Ticketing — Roadmap Teknis Multi-Tenant

> **Konteks:** Platform ini awalnya dibangun untuk 1 divisi internal. Setelah migrasi ke arsitektur multi-tenant (Oktober 2026), dokumen ini merekam semua pekerjaan teknis yang perlu diselesaikan sebelum dan sesudah platform diperluas ke banyak perusahaan.

---

## Status Arsitektur Saat Ini

| Komponen | Status | Keterangan |
|---|---|---|
| Tabel `companies` | [x] Ada | Dibuat Oktober 2026 |
| Hierarki role `superadmin > manager > staff` | [x] Ada | Implementasi Oktober 2026 |
| `company_id` di tabel `users` | [x] Ada | Terikat ke perusahaan user |
| `company_id` di tabel `tickets` | [x] Ada | Terikat ke perusahaan pembuat |
| Composite unique index `(company_id, nomor_tiket)` | [x] Ada | Format IT-ddmmyyyy-001 independen per company |
| `CompanyScope` (isolasi otomatis query) | [x] Ada | Aktif di Ticket, MonthlyAnalysis, Chat, Audit Log |
| Index `company_id` di database | [x] Ada | BTREE, otomatis dari foreign key |
| Composite index `(company_id, status/tanggal/created)` | [x] Ada | Performa optimal untuk data skala besar |
| `company_id` di tabel `monthly_analyses` | [x] Ada | Terisolasi per perusahaan (Sprint 1) |
| Storage path tiket per perusahaan | [x] Ada | tickets/company_{id}/{nomor_tiket} (Sprint 1) |
| Kredensial superadmin dari `.env` | [x] Ada | .env & .env.example (Sprint 1) |
| Rate limiting route login | [x] Ada | throttle:6,1 di routes/auth.php (Sprint 1) |
| Laravel Policy untuk Ticket | [x] Ada | app/Policies/TicketPolicy.php (Sprint 2) |
| `company_id` di tabel `documentation_*` | [x] Ada | Terisolasi per perusahaan (Sprint 2) |
| `company_id` di tabel `settings` | [x] Ada | Dukung override company & fallback global (Sprint 2) |
| Soft Deletes pada tabel `tickets` | [x] Ada | Kolom deleted_at & SoftDeletes trait (Sprint 2) |
| Audit Trail / Riwayat Aktivitas Tiket | [x] Ada | Tabel ticket_histories + Timeline UI (Sprint 3) |

---

## Sprint 1 — Sebelum Go-Live ke Perusahaan Kedua [SELESAI]
> **Target:** Tidak ada celah data bocor atau kerusakan data saat perusahaan kedua didaftarkan.

- [x] **1.1 Perbaiki Storage Path Tiket** (`TicketController.php`, folder `tickets/company_{id}/...`)
- [x] **1.2 Isolasi Monthly Analysis per Perusahaan** (migrasi `company_id`, `CompanyScope` di `MonthlyAnalysis`)
- [x] **1.3 Pindahkan Kredensial Superadmin ke `.env`** (`SUPERADMIN_EMAIL` & `SUPERADMIN_PASSWORD`)
- [x] **1.4 Rate Limiting Login** (`throttle:6,1` di `routes/auth.php`)
- [x] **Bonus: Composite Unique Index Nomor Tiket** (`(company_id, nomor_tiket)`)

---

## Sprint 2 — Penguatan & Tata Kelola Data [SELESAI]
> **Target:** Keamanan lapis kedua, isolasi dokumentasi, pengaturan per perusahaan, dan retensi data.

- [x] **2.1 Tambah Laravel Policy untuk Ticket** (`app/Policies/TicketPolicy.php` + `Gate::authorize()`)
- [x] **2.2 Isolasi Documentation Conversations** (`company_id` di conversations & chats + `CompanyScope`)
- [x] **2.3 Settings Per Perusahaan** (`company_id nullable` di `settings` + hierarchical fallback)
- [x] **2.4 Tambah Soft Deletes** (`deleted_at` di tabel `tickets` + trait `SoftDeletes`)

---

## Sprint 3 — Peningkatan Skala & Pengalaman Pengguna [SELESAI SEBAGIAN / AKTIF]
> **Target:** Sistem siap untuk skala puluhan perusahaan dan ratusan pengguna aktif harian.

- [x] **3.1 Audit Log (Riwayat Aktivitas & Perubahan)**
  - Tabel `ticket_histories` dengan isolasi `company_id` & `CompanyScope`
  - Tracking otomatis saat tiket dibuat, diubah (status, prioritas, PIC, solusi), dan di-soft delete
  - Visualisasi timeline aktivitas di halaman detail tiket (`tickets/show.blade.php`)
- [x] **3.2 Composite Database Indexes Tambahan**
  - Index `idx_tickets_company_status` pada `(company_id, status)`
  - Index `idx_tickets_company_tanggal` pada `(company_id, tanggal_kejadian)`
  - Index `idx_tickets_company_created` pada `(company_id, created_at)`
- [ ] **3.3 Notifikasi Email / Webhook (Opsional)**
  - Notifikasi tiket baru dibuat ke manager
  - Notifikasi status tiket berubah ke pelapor
- [ ] **3.4 API Layer (Sanctum) (Opsional)**
  - Akses integrasi pihak ketiga atau mobile app jika dibutuhkan di masa mendatang

---

## Checklist Kesiapan Go-Live

- [x] Storage path tiket sudah include `company_id`
- [x] `monthly_analyses` sudah punya `company_id` + CompanyScope
- [x] Password superadmin sudah dipindah ke `.env`
- [x] Rate limiting login sudah aktif (`throttle:6,1`)
- [x] Policy Ticket aktif (`Gate::authorize`)
- [x] Chat AI dokumentasi terisolasi per perusahaan
- [x] Settings per perusahaan mendukung override
- [x] Soft deletes tiket aktif mencegah kehilangan data tidak sengaja
- [x] Audit log & timeline aktivitas tiket aktif
- [x] Composite indexes database aktif untuk performa tinggi
- [ ] Login sebagai manager PT berbeda dan uji fungsionalitas end-to-end
- [ ] Backup snapshot database sebelum onboarding tenant production pertama