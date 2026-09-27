# ⚡ Go-Note — IT Ticketing System

Sistem manajemen tiket operasional IT yang modern, cepat, dan terintegrasi kecerdasan buatan (**Google Gemini AI**) untuk pencatatan dan penanganan kendala teknis pada toko/cabang operasional.

---

## 🌟 Fitur Utama

### 🛠️ Untuk Staff IT & Toko
- **Pencatatan Tiket Otomatis dengan AI Vision**:
  - Cukup unggah hingga 3 tangkapan layar (*screenshot*) percakapan WhatsApp laporan kendala toko.
  - AI secara otomatis menganalisis dan mengisi kategori (*POS, Akun, Device, Jaringan/Lainnya*), prioritas kendala, deskripsi masalah, serta dampaknya pada operasional.
- **Riwayat & Monitoring Tiket**:
  - Tabel tiket interaktif dengan indikator status (*Open, In Progress, Resolved, Closed, Eskalasi*).
  - Filter modal multi-kriteria: filter berdasarkan status, kategori, prioritas, rentang tanggal, dan pencarian teks.
  - Ekspor seluruh atau sebagian data tiket ke format **Excel (.xlsx)**.

### 📊 Untuk Manager / Admin IT
- **Dashboard Ringkasan KPI**:
  - Kartu statistik metrik utama: Total Tiket, Open, Sedang Diproses, dan Selesai.
  - Grafik visual interaktif berbasis **Chart.js** (Distribusi Status & Kategori Tiket).
  - Tabel sebaran tiket per Divisi/Toko.
- **Analisis Kinerja Bulanan berbasis AI**:
  - Evaluasi performa penanganan IT bulanan yang digenerate otomatis oleh Gemini AI.
  - Menyediakan ringkasan eksekutif, identifikasi pola masalah umum, dan rekomendasi perbaikan berkala berformat Markdown.
- **Manajemen Pengguna (User Management)**:
  - Kelola akun staf dan manajer dengan hak akses terpisah.
  - Fleksibilitas login menggunakan **Email** maupun **Username** (contoh: `it`, `admin`).
  - Antarmuka pop-up modal modern dengan proteksi seleksi teks.
- **Pengaturan Sistem (Settings)**:
  - Toggle switch interaktif dengan fitur live preview untuk mengatur visibilitas info akun demo di halaman login.

---

## 🎨 Desain & UI/UX
- **Flat White Design System**: Antarmuka bersih, elegan, dan berstandar tinggi yang dibangun dengan Vanilla CSS murni dan tipografi Google Fonts (*Inter*).
- **Desain Responsif Penuh**: Mendukung perangkat desktop dan tampilan mobile dengan drawer navigasi khusus.
- **Interaksi Halus**: Menggunakan SweetAlert2 untuk feedback aksi pengguna yang ramah dan jelas.

---

## 💻 Tech Stack
- **Framework Back-End**: [Laravel](https://laravel.com/) (PHP 8.2+)
- **Database**: MySQL / MariaDB
- **Artificial Intelligence**: [Google Gemini API](https://ai.google.dev/) (`gemini-2.5-flash` Multimodal Vision & Text)
- **Front-End & Assets**: Blade Templating, Vanilla CSS, Vite, Chart.js, SweetAlert2
- **Spreadsheet Engine**: Maatwebsite / Laravel-Excel

---

## 🚀 Panduan Instalasi Lokal

### 1. Prasyarat
- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL / XAMPP

### 2. Kloning Repositori
```bash
git clone https://github.com/AryaYoo/go-note-it-ticket.git
cd go-note-it-ticket
```

### 3. Install Dependensi
```bash
composer install
npm install
```

### 4. Konfigurasi Lingkungan (.env)
Salin file `.env.example` ke `.env`:
```bash
cp .env.example .env
```
Buka file `.env` dan sesuaikan koneksi database serta API Key Gemini:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=go-note
DB_USERNAME=root
DB_PASSWORD=

# Konfigurasi Google Gemini AI
GEMINI_API_KEY=masukkan_api_key_gemini_anda_disini
```

Generate application key:
```bash
php artisan key:generate
```

### 5. Migrasi & Seeder Database
Jalankan migrasi database untuk membuat tabel dan data seeder:
```bash
php artisan migrate --seed
```

### 6. Jalankan Server
Jalankan development server Laravel dan compiler asset Vite:
```bash
# Terminal 1: Laravel Serve
php artisan serve

# Terminal 2: Vite Dev (Opsional jika ingin build asset)
npm run dev
```

Buka browser dan akses: `http://127.0.0.1:8000`

---

## 🔑 Akun Demo Default

| Role | Email / Username | Password | Keterangan Akses |
|---|---|---|---|
| **Admin / Manager** | `admin@hsitoperasional.com` | `admin` | Dashboard Manager, Users, Analisis AI, Settings |
| **Manager IT** | `manager@gonote.id` | `password` | Dashboard Manager, Users, Analisis AI, Settings |
| **Staff IT** | `it` *(atau `staff@gonote.id`)* | `it` *(atau `password`)* | Dashboard Tiket, Catat Tiket WhatsApp AI, Ekspor Excel |

---

## 📄 Lisensi
Sistem ini bersifat Open Source di bawah lisensi [MIT License](LICENSE).
