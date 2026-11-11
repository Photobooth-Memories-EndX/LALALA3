# FINAL PRODUCTION SAFETY AUDIT — PTKP IMPORT FALSE POSITIVE IMPACT & HISTORICAL DATA INTEGRITY

**Audit Date:** 11 November 2026  
**Target Batch UUID:** `af57a684-d72b-47a4-bab9-ffa505844bbc` (Batch ID: 1)  
**Excel Source File:** `docs/PTKP_OWL_Import_Template fix data 11 11.xlsx`  
**System:** OWL LMS Internal (`PT Krakatau Posco`)  
**Audit Scope:** Database Persistence Impact, Director Account Integrity (`000022` & `000033`), Fresh Preview Regression, and Safe Remediation Plan  

---

## 1. Executive Summary

Audit keselamatan produksi tahap akhir (*Final Production Safety Audit*) ini dilakukan untuk menginvestigasi dampak konkret dari kesalahan import pada batch `af57a684-d72b-47a4-bab9-ffa505844bbc` terhadap data di tabel `offline_training_users`, `offline_trainings`, akun direktur, serta catatan kehadiran (*attendance*), progres (*progress*), dan sertifikat (*certificates*).

### Ringkasan Temuan Utama:
1. **Dampak Nyata pada Akun Direktur:**  
   Keempat baris mentah peserta magang/OJT (Rows 380, 391, 494, 505) **benar-benar telah tersimpan** ke dalam tabel `offline_training_users` atas nama Direktur:
   - **KIM KYU-JE** (User ID: 5571, NIP: `000022`)
   - **NOVIAL** (User ID: 6006, NIP: `000033`)
2. **Kondisi Data Training Existing:**  
   Kedua direktur tersebut **sebelumnya tidak pernah memiliki riwayat training offline apapun** di LMS. Seluruh 4 record training di bawah User ID 5571 dan 6006 berasal 100% secara eksklusif dari batch bermasalah ini.
3. **Training 27 Adalah Training Orphan (Kosong):**  
   Pelatihan dengan ID 27 (*"New Employee Orientation for Steel Industry Technology Class (OJT PIPB)"*) hanya memiliki **2 peserta** di seluruh tabel, yaitu Kim Kyu-Je (OTU ID 373) dan Novial (OTU ID 374). Jika kedua record tersebut diremediasi, Training 27 memiliki 0 peserta.
4. **Training 37 Memiliki 25 Peserta Valid Lainnya:**  
   Pelatihan dengan ID 37 (*"NewGen Capability Development Vol. 1-2026"*) memiliki **27 peserta**, di mana 25 peserta adalah karyawan sah dengan NIP valid. Pelatihan ini **tidak boleh dihapus**, hanya 2 record salah (OTU ID 470 & 471) yang harus dibersihkan.
5. **Dampak Sertifikat & downstream records:**  
   - Tabel `certificates`: **0 record** (tidak ada sertifikat yang diterbitkan untuk direktur).
   - Tabel `offline_training_registers`: **0 record**.
   - Kolom `attendance` bernilai `true` (1) dan `progress` bernilai `1.0000` (100%) akibat nilai default import pada spreadsheet.
6. **Fresh Preview Verifikasi:**  
   Uji coba preview terbaru pada file Excel yang sama membuktikan bahwa implementasi baru 100% mengklasifikasikan Baris 380, 391, 494, dan 505 sebagai **`INVALID`** (`INVALID_ID`), mempertahankan nama asli peserta tanpa tertimpa, dan tidak ada yang masuk ke akun direktur.
7. **Tersedia Safe Remediation Command:**  
   Perintah Artisan `php artisan ptkp:remediate-historical-batch` telah dibuat dengan mode default **`--dry-run`** (read-only), dukungan backup snapshot otomatis, transaksi database atomik, dan mekanisme rollback instan.

---

## 2. Previous Root Cause Recap

Kesalahan klasifikasi berawal dari metode `PtkpValidationService::batchLookupUsers()`:
- Kode auto-padding melakukan `str_pad($trimmed, 6, '0', STR_PAD_LEFT)` untuk semua nomor `< 6` digit tanpa batas minimal panjang.
- Nomor absen kelas OJT `22` diubah menjadi `000022` (KIM KYU-JE) dan `33` diubah menjadi `000033` (NOVIAL).
- Mapping unpadded me-reverse `000022` menjadi key `22`, sehingga `matchUser('22', ...)` mengembalikan objek `User` Kim Kyu-Je.
- Sistem menganggap pegawai ditemukan, melakukan sinkronisasi identitas yang menimpa nama **IQBAL NUR RACHMAN** menjadi **KIM KYU-JE** dan **RIDWAN ALFITRA** menjadi **NOVIAL**, lalu memberinya status `VALID` dan mengimpornya.

---

## 3. Four Incorrect Imported Rows Audit

Berikut adalah 4 baris yang salah terimpor pada batch `af57a684-d72b-47a4-bab9-ffa505844bbc`:

| Row Excel | Raw ID | Raw Name | Course Title | Stored in `import_batch_rows` (PK) | Assigned User (LMS) | Enrolled `offline_training_users` (PK) |
|---|---|---|---|---|---|---|
| **380** | `22` | IQBAL NUR RACHMAN | New Employee Orientation for Steel Industry Technology Class (OJT PIPB) | PK: **379** (ID: `000022`, Name: `KIM KYU-JE`, Status: `VALID`, Action: `IMPORTED`) | User 5571 (`KIM KYU-JE`) | OTU ID: **373** (OT ID: 27) |
| **391** | `33` | RIDWAN ALFITRA | New Employee Orientation for Steel Industry Technology Class (OJT PIPB) | PK: **390** (ID: `000033`, Name: `NOVIAL`, Status: `VALID`, Action: `IMPORTED`) | User 6006 (`NOVIAL`) | OTU ID: **374** (OT ID: 27) |
| **494** | `22` | IQBAL NUR RACHMAN | NewGen Capability Development Vol. 1-2026 | PK: **493** (ID: `000022`, Name: `KIM KYU-JE`, Status: `VALID`, Action: `IMPORTED`) | User 5571 (`KIM KYU-JE`) | OTU ID: **470** (OT ID: 37) |
| **505** | `33` | RIDWAN ALFITRA | NewGen Capability Development Vol. 1-2026 | PK: **504** (ID: `000033`, Name: `NOVIAL`, Status: `VALID`, Action: `IMPORTED`) | User 6006 (`NOVIAL`) | OTU ID: **471** (OT ID: 37) |

---

## 4. Database Impact Analysis

Pemeriksaan komprehensif pada database MySQL membuktikan:

### A. Tabel `offline_training_users`
Query audit:
```sql
SELECT id, offline_training_id, user_id, employee_id, name, progress, attendance, created_at 
FROM offline_training_users 
WHERE user_id IN (5571, 6006);
```
**Hasil:**
- Ditemukan tepat **4 record**.
- Seluruhnya dibuat pada `2026-11-11 17:24:16` saat batch `af57a684-d72b-47a4-bab9-ffa505844bbc` dijalankan.
- **Tidak ada data training pra-eksisting** milik Direktur Kim Kyu-Je atau Novial sebelum tanggal tersebut.

### B. Tabel `offline_trainings`
1. **Training ID 27 (`New Employee Orientation for Steel Industry Technology Class (OJT PIPB)`):**
   - Dibuat pada `2026-11-11 17:24:21` oleh User 936 (admin pelaksana import).
   - Total peserta terdaftar: **2 orang** (OTU 373 & 374).
   - Seluruh peserta di kelas ini adalah hasil salah cocok. Tidak ada peserta lain.
2. **Training ID 37 (`NewGen Capability Development Vol. 1-2026`):**
   - Dibuat pada `2026-11-11 17:24:22` oleh User 936.
   - Total peserta terdaftar: **27 orang**.
   - Sebanyak **25 peserta** adalah karyawan sah (misal NIP `703748`, `703750`, `703761`, `703772`, dsb).
   - Sebanyak **2 peserta** adalah akun direktur yang salah masuk (OTU 470 & 471).

---

## 5. Attendance, Completion, and Certificate Impact

| Aspek | Nilai pada Record 373, 374, 470, 471 | Dampak pada Sistem |
|---|---|---|
| **Attendance** | `attendance = 1` (true) | Direktur tercatat hadir pada kelas OJT / New Employee Orientation. |
| **Attendance Reason** | `NULL` | Tidak ada keterangan khusus. |
| **Progress** | `progress = 1.0000` (100%) | Training muncul sebagai *Completed* pada profil learning direktur. |
| **Score (L1, L2, L3)** | `score_l1 = NULL`, `l2_numerical = NULL`, `l3_numerical = NULL` | Tidak ada penilaian kuantitatif yang tersimpan. |
| **Certificates Table** | `0 records` (KOSONG) | Tidak ada sertifikat digital yang dibuat ataupun di-generate. |
| **Certificate Setting** | `certificate_id = NULL` | Tidak ada relasi ke pengaturan sertifikat. |
| **Learning Hours Batch** | Belum diproses | Jam belajar belum dikalkulasi ke dalam batch akumulasi tahunan. |

---

## 6. Fresh Preview Verification (Uji Coba Ulang File Mentah)

File `docs/PTKP_OWL_Import_Template fix data 11 11.xlsx` diuji ulang melalui pipeline `parseAndEvaluateWorkbook()` dengan logika validasi terbaru (tanpa melakukan persistence ke database).

### Hasil Evaluasi Baris Uji:
```
Row  380 | Employee ID: '22'  | Name: 'IQBAL NUR RACHMAN' | Status: 'INVALID' | Messages: ["ERROR: Employee ID '22' was not found in LMS users database."]
Row  391 | Employee ID: '33'  | Name: 'RIDWAN ALFITRA'    | Status: 'INVALID' | Messages: ["ERROR: Employee ID '33' was not found in LMS users database."]
Row  494 | Employee ID: '22'  | Name: 'IQBAL NUR RACHMAN' | Status: 'INVALID' | Messages: ["ERROR: Employee ID '22' was not found in LMS users database."]
Row  505 | Employee ID: '33'  | Name: 'RIDWAN ALFITRA'    | Status: 'INVALID' | Messages: ["ERROR: Employee ID '33' was not found in LMS users database."]
Row 2213 | Employee ID: '117' | Name: 'RIDWAN ALFITRA'    | Status: 'INVALID' | Messages: ["ERROR: Employee ID '117' was not found in LMS users database."]
Row 2241 | Employee ID: '131' | Name: 'IQBAL NUR RACHMAN' | Status: 'INVALID' | Messages: ["ERROR: Employee ID '131' was not found in LMS users database."]
```

### Simulasi Filter Nama di Frontend Preview:
- Pencarian `"IQBAL NUR RACHMAN"`: Ditemukan **3 baris** (Row 380, 494, 2241). Seluruhnya `INVALID`.
- Pencarian `"RIDWAN ALFITRA"`: Ditemukan **3 baris** (Row 391, 505, 2213). Seluruhnya `INVALID`.
- Pencarian `"KIM KYU-JE"`: **0 baris**.
- Pencarian `"NOVIAL"`: **0 baris**.

---

## 7. Historical Batch Remediation Plan

Untuk membersihkan data batch lama secara aman dan terkontrol tanpa merusak data lain, telah disiapkan Artisan Command khusus:  
[`app/Console/Commands/PtkpRemediateHistoricalBatchCommand.php`](file:///C:/laragon/www/owl-internal/app/Console/Commands/PtkpRemediateHistoricalBatchCommand.php)

### Cara Kerja Command:
1. **Mode Default (Safe Dry-Run):**
   ```bash
   php artisan ptkp:remediate-historical-batch
   ```
   Menampilkan tabel perbandingan sebelum vs sesudah tanpa melakukan perubahan database sama sekali.
2. **Mode Eksekusi (Transaction-Safe):**
   ```bash
   php artisan ptkp:remediate-historical-batch --execute
   ```
   - Menyimpan backup snapshot JSON ke `storage/app/ptkp_remediation_backup_af57a684-d72b-47a4-bab9-ffa505844bbc.json`.
   - Menjalankan DB Transaction:
     - `Soft-delete` pada `offline_training_users` PK: **373, 374, 470, 471**.
     - `Soft-delete` pada training orphan `offline_trainings` PK: **27**.
     - Mempertahankan training aktif `offline_trainings` PK: **37** (25 peserta sah tetap utuh).
     - Mengoreksi `import_batch_rows` PK: **379, 390, 493, 504**:
       - Status: `VALID` -> `INVALID`
       - Action: `IMPORTED` -> `SKIPPED`
       - ID: dikembalikan ke `22` dan `33`
       - Name: dikembalikan ke `IQBAL NUR RACHMAN` dan `RIDWAN ALFITRA`
       - Menghapus link `offline_training_id` dan `offline_training_user_id`
     - Mengoreksi counter `import_batches`:
       - `valid_rows`: 3.409 -> 3.405 (-4)
       - `invalid_rows`: 211 -> 215 (+4)
       - `imported_rows`: 3.467 -> 3.463 (-4)
       - `skipped_rows`: 212 -> 216 (+4)
       - `trainings_created`: 197 -> 196 (-1)
3. **Mekanisme Rollback Instan:**
   ```bash
   php artisan ptkp:remediate-historical-batch --rollback
   ```
   Jika diperlukan, perintah ini membaca backup JSON snapshot dan memulihkan seluruh data dan counter ke kondisi semula 100%.

### Tabel Rencana Perubahan Sebelum vs Sesudah:

| Target Tabel | Primary Key | Kolom | Nilai Sebelum (Salah) | Nilai Sesudah (Koreksi) |
|---|---|---|---|---|
| `offline_training_users` | 373, 374, 470, 471 | `deleted_at` | `NULL` | Timestamp saat ini (Soft-deleted) |
| `offline_trainings` | 27 | `deleted_at` | `NULL` | Timestamp saat ini (Soft-deleted orphan) |
| `offline_trainings` | 37 | Status | Aktif (27 peserta) | Tetap Aktif (25 peserta sah) |
| `import_batch_rows` | 379 (Row 380) | `employee_id`, `employee_name`, `validation_status`, `action` | `000022`, `KIM KYU-JE`, `VALID`, `IMPORTED` | `22`, `IQBAL NUR RACHMAN`, `INVALID`, `SKIPPED` |
| `import_batch_rows` | 390 (Row 391) | `employee_id`, `employee_name`, `validation_status`, `action` | `000033`, `NOVIAL`, `VALID`, `IMPORTED` | `33`, `RIDWAN ALFITRA`, `INVALID`, `SKIPPED` |
| `import_batch_rows` | 493 (Row 494) | `employee_id`, `employee_name`, `validation_status`, `action` | `000022`, `KIM KYU-JE`, `VALID`, `IMPORTED` | `22`, `IQBAL NUR RACHMAN`, `INVALID`, `SKIPPED` |
| `import_batch_rows` | 504 (Row 505) | `employee_id`, `employee_name`, `validation_status`, `action` | `000033`, `NOVIAL`, `VALID`, `IMPORTED` | `33`, `RIDWAN ALFITRA`, `INVALID`, `SKIPPED` |
| `import_batches` | 1 | `valid_rows`, `invalid_rows`, `imported_rows`, `skipped_rows` | 3409, 211, 3467, 212 | 3405, 215, 3463, 216 |

---

## 8. Regression Test Results

Semua test suite fitur terkait telah dijalankan dan lulus 100%:

1. **[`PtkpInvalidEmployeeIdAuditTest.php`](file:///C:/laragon/www/owl-internal/tests/Feature/PtkpInvalidEmployeeIdAuditTest.php):**  
   `10 tests, 61 assertions` — **PASS (100%)**
2. **[`PtkpImportTest.php`](file:///C:/laragon/www/owl-internal/tests/Feature/PtkpImportTest.php):**  
   `41 tests, 412 assertions` — **PASS (100%)**
3. **[`PtkpValidationClassificationStrictMatrixTest.php`](file:///C:/laragon/www/owl-internal/tests/Feature/PtkpValidationClassificationStrictMatrixTest.php):**  
   `35 tests, 317 assertions` — **PASS (100%)**
4. **[`PtkpFullRowDuplicateDetectionTest.php`](file:///C:/laragon/www/owl-internal/tests/Feature/PtkpFullRowDuplicateDetectionTest.php):**  
   `49 tests, 159 assertions` — **PASS (100%)**
5. **[`NonPtkpImportTest.php`](file:///C:/laragon/www/owl-internal/tests/Feature/NonPtkpImportTest.php):**  
   `23 tests, 178 assertions` — **PASS (100%)**

---

## 9. Production Safety Recommendations

1. **Persetujuan Eksekusi Remediasi:**  
   Jalankan perintah eksekusi remediasi:
   ```bash
   php artisan ptkp:remediate-historical-batch --execute
   ```
   Perintah ini akan secara atomik membersihkan akun direktur dan memulihkan data historis batch `af57a684-d72b-47a4-bab9-ffa505844bbc`.
2. **SOP Import Mandiri:**  
   Tidak perlu mengimpor ulang spreadsheet mentah. Remediasi command di atas secara otomatis menyelesaikan ketidaksesuaian data historis tanpa resiko duplikasi atau re-import yang memicu beban server.
3. **Verifikasi Pasca-Eksekusi:**  
   Setelah perintah di atas dieksekusi, periksa kembali halaman Batch Detail:
   `http://127.0.0.1:8000/manage/record/offline/import-history/af57a684-d72b-47a4-bab9-ffa505844bbc/detail`
   - Filter nama "IQBAL NUR RACHMAN" akan menampilkan ketiga baris (Row 380, 494, 2241).
   - Filter nama "RIDWAN ALFITRA" akan menampilkan ketiga baris (Row 391, 505, 2213).
   - Seluruh baris tersebut akan berada di tab **INVALID**.
   - Tab **VALID** akan bersih dari akun direktur untuk kedua pelatihan tersebut.
   - Angka statistik akan menampilkan: **VALID: 3.405**, **INVALID: 215**, **WARNING: 58**, **DUPLICATE: 1**, **ALL TOTAL: 3.679**.
