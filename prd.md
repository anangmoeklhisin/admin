# Product Requirements Document (PRD)

**Nama Produk:** WebApps Ekstraktor & Laporan Data SIK  
**Versi Dokumen:** 1.2 (Revisi Arsitektur OS & Split Server)  
**Tanggal:** Oktober 2026  
**Target Pengguna:** Staf Internal (Intranet)

---

## 1. Ringkasan Eksekutif
Proyek ini bertujuan untuk membangun aplikasi web internal (*dashboard reporting*) untuk menarik dan mengunduh data dari database sistem informasi yang sudah ada (`sik`). Aplikasi ini beroperasi di jaringan lokal (intranet) tanpa sistem login. Sistem difokuskan pada kecepatan akses, keamanan data asli (Read-Only), efisiensi server dengan memindahkan beban pembuatan laporan ke browser pengguna, dan menggunakan arsitektur *split server* lintas sistem operasi (Windows untuk Web, Linux/Sistem lain untuk Database).

## 2. Arsitektur & Lingkungan Sistem
Sistem menggunakan arsitektur *split server* dimana aplikasi web dan database berada di mesin dan lingkungan sistem operasi yang berbeda.

*   **Lingkungan Web Server (IP: 192.168.1.36):**
    *   **Sistem Operasi:** Windows
    *   **Environment:** XAMPP (Apache)
    *   **Versi PHP:** 8.2
*   **Lingkungan Database Server (IP: 192.168.1.2):**
    *   **Database:** MySQL / MariaDB (Nama Database: `sik`)
*   **Tech Stack Frontend:** HTML5, CSS (Bootstrap 5), JavaScript (jQuery, DataTables)
*   **Tech Stack Backend:** PHP Native (PDO) / CodeIgniter 4 / Laravel 11 (Mendukung penuh PHP 8.2)

## 3. Kebutuhan Fungsional (Functional Requirements)

### 3.1. Modul Pencarian & Filter
*   **F1.01:** Sistem menyediakan form input untuk memfilter data (misal: Tanggal Mulai, Tanggal Akhir, dan Dropdown Kategori/Poli).
*   **F1.02:** Tombol "Tampilkan Data" untuk mengirimkan *request query* dari Web Server Windows (`192.168.1.36`) ke Database Server (`192.168.1.2`) berdasarkan parameter filter.

### 3.2. Modul Penampil Data (Data Viewer)
*   **F2.01:** Menampilkan data hasil *query* dalam bentuk tabel dinamis.
*   **F2.02:** Tabel mendukung fitur pencarian teks (*Live Search*) secara instan.
*   **F2.03:** Tabel mendukung pengurutan data (*Sorting*) pada setiap kolom (A-Z, Z-A, Angka).
*   **F2.04:** Tabel membagi data dalam beberapa halaman (*Pagination*) jika jumlah baris melebihi batas (misal: 10, 25, 50 baris per halaman).

### 3.3. Modul Ekspor Laporan
*   **F3.01:** Sistem menyediakan tombol *export* bawaan di atas tabel.
*   **F3.02:** Opsi *export* meliputi: **Copy ke Clipboard**, **Download CSV**, **Download Excel (.xlsx)**, **Download PDF**, dan **Print**.
*   **F3.03:** Data yang diekspor adalah data yang tampil pada tabel (sesuai dengan hasil filter dan pencarian yang sedang aktif).

## 4. Kebutuhan Non-Fungsional (Non-Functional Requirements)

*   **NF.01 Keamanan Database:** Koneksi PHP dari XAMPP (`192.168.1.36`) tidak boleh menggunakan akun `root`. Wajib membuat *user* MySQL khusus di server database `192.168.1.2` (misal: `report_viewer@192.168.1.36`) yang hanya memiliki hak akses `GRANT SELECT ON sik.*`.
*   **NF.02 Kinerja Web Server:** Pembuatan file PDF dan Excel menggunakan *library client-side* (DataTables Buttons, pdfmake, jszip) sehingga beban RAM/CPU pada server Windows `192.168.1.36` tetap minimal.
*   **NF.03 Aksesibilitas:** Webapps dikonfigurasi pada *Virtual Host* XAMPP (`httpd-vhosts.conf`) agar dapat diakses langsung menggunakan URL `http://192.168.1.36` dari jaringan lokal tanpa menambahkan *path* folder panjang (misal `http://192.168.1.36/folder/public`).
*   **NF.04 Responsivitas Antarmuka:** Tampilan antarmuka harus responsif dan menyesuaikan ukuran layar komputer staf.
*   **NF.05 Kinerja Jaringan Lintas Server:** Memastikan port 3306 terbuka di *firewall* server `192.168.1.2` hanya untuk menerima koneksi masuk dari IP `192.168.1.36` demi menjamin kelancaran transfer *query* data.

## 5. Ruang Lingkup Batasan (Out of Scope)
Hal-hal berikut **tidak** termasuk dalam pengembangan aplikasi ini:
1.  Sistem Otentikasi (Tidak ada Login / Register).
2.  Manipulasi Data (Tidak ada fitur *Create, Update, Delete* ke database `sik`).
3.  Penyimpanan File Laporan (File hasil *download* langsung tersimpan di perangkat klien, tidak ada *storage* file di server XAMPP `192.168.1.36`).
4.  Akses Publik (Tidak dapat diakses melalui internet luar, hanya melalui jaringan area lokal / LAN).

## 6. Desain Antarmuka (Wireframe Concept)

**Header:** 
- Judul Aplikasi "Portal Laporan SIK"

**Section 1: Filter Panel (Card Atas)**
- [Input: Tanggal Mulai] [Input: Tanggal Akhir] [Select: Kategori] [Tombol: Tampilkan Data]

**Section 2: Data Panel (Card Bawah)**
- [Tombol: Copy] [Tombol: CSV] [Tombol: Excel] [Tombol: PDF] [Tombol: Print] --- [Input: Live Search]
- | Kolom 1 | Kolom 2 | Kolom 3 | Kolom 4 |
- | Data 1  | Data 2  | Data 3  | Data 4  |
- [Informasi Pagination: Showing 1 to 10 of 500 entries] --- [Navigasi Halaman: < 1 2 3 >]