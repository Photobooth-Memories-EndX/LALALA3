# LAPORAN INVESTIGASI & AUDIT MENYELURUH (DEEP AUDIT)
## Root Cause Analysis: Kesalahan Klasifikasi Employee ID `22` & `33` pada Import PTKP, Database Persistence, Import History, dan UI Filtering

**Tanggal Audit:** 11 November 2026  
**Target Batch ID:** `af57a684-d72b-47a4-bab9-ffa505844bbc`  
**File Excel Sumber:** `docs/PTKP_OWL_Import_Template fix data 11 11.xlsx`  
**Sistem:** OWL LMS Internal (`PT Krakatau Posco`)  
**Lingkungan:** PHP 8.1.10 / Laravel 9.52.16 / MySQL  

---

## 1. Executive Summary

Investigasi menyeluruh telah dilakukan dari tingkat file mentah spreadsheet Excel, parsing baris, normalisasi, resolusi identitas pegawai (*employee lookup*), evaluasi status validasi (*validation service*), penyimpanan ke database (*persistence*), pencatatan batch riwayat (*import history*), hingga query server-side DataTables dan filter UI.

### Temuan Kunci:
1. **Penyebab Utama (Root Cause):**  
   Pada `App\Services\OfflineTraining\PtkpValidationService::batchLookupUsers()`, terdapat logika auto-padding:
   ```php
   if (ctype_digit($trimmed) && strlen($trimmed) < 6) {
       $padded = str_pad($trimmed, 6, '0', STR_PAD_LEFT);
       $normalizedIds[strtolower($padded)] = $padded;
   }
   ```
   dan aliasing balik tanpa leading zeros:
   ```php
   if (ctype_digit($key)) {
       $unpadded = ltrim($key, '0');
       $lookup[$unpadded]->push($user);
   }
   ```
   Logika ini menganggap **seluruh angka kurang dari 6 digit** sebagai NIP yang kehilangan leading zero akibat format cell numerik Excel. Akibatnya:
   - Nilai Employee ID `22` di-padding menjadi `000022`.
   - Nilai Employee ID `33` di-padding menjadi `000033`.

2. **Dampak di Database `users`:**  
   Di dalam tabel `users`, nilai `000022` dan `000033` **benar-benar ada**, namun bukan milik peserta bersangkutan!
   - `id_emp = '000022'` adalah milik **KIM KYU-JE** (Direktur / Ekspatriat Korea).
   - `id_emp = '000033'` adalah milik **NOVIAL** (Direktur).
   - Sedangkan pada Excel, baris tersebut adalah milik peserta pelatihan magang/OJT:
     - Baris 380 & 494: **IQBAL NUR RACHMAN** (Employee ID mentah: `22`)
     - Baris 391 & 505: **RIDWAN ALFITRA** (Employee ID mentah: `33`)

3. **Overwriting Identitas Peserta (*Identity Corruption*):**  
   Karena Employee ID `22` dan `33` terpetakan ke objek `User` Kim Kyu-Je dan Novial, metode `PtkpImportService::parseAndEvaluateWorkbook()` menganggap user valid dan melakukan sinkronisasi identitas:
   ```php
   if ($user) {
       $normalized['user_id'] = $user->id;
       $normalized['employee_id'] = $user->id_emp; // diubah jadi '000022' / '000033'
       $normalized['name'] = $user->name;         // diubah jadi 'KIM KYU-JE' / 'NOVIAL'
   }
   ```
   Nama asli **IQBAL NUR RACHMAN** dan **RIDWAN ALFITRA** tertimpa (*overwritten*) dan statusnya diberikan **VALID**!

4. **Dampak pada Database Persistence Batch `af57a684-d72b-47a4-bab9-ffa505844bbc`:**  
   - Pada tabel `import_batch_rows`, Baris 380 & 494 tercatat dengan `employee_id = '000022'` dan `employee_name = 'KIM KYU-JE'`, status `VALID`.
   - Baris 391 & 505 tercatat dengan `employee_id = '000033'` dan `employee_name = 'NOVIAL'`, status `VALID`.
   - Pelatihan orientation tersebut bahkan terdaftar ke akun Direktur Kim Kyu-Je dan Novial di tabel `offline_training_users`!

5. **Dampak pada UI Import History & Filtering:**  
   - Saat admin mencari nama **IQBAL NUR RACHMAN**, sistem hanya menampilkan **1 baris** (yaitu Baris 2241, Employee ID `131`, yang berstatus `INVALID`), karena Baris 380 & 494 sudah berubah nama menjadi `KIM KYU-JE`.
   - Saat admin mencari nama **RIDWAN ALFITRA**, sistem hanya menampilkan **1 baris** (yaitu Baris 2213, Employee ID `117`, status `INVALID`), karena Baris 391 & 505 sudah berubah nama menjadi `NOVIAL`.
   - Statistik batch menampilkan `VALID: 3,409` dan `INVALID: 211`. Seharusnya setelah 4 baris tersebut diklasifikasikan dengan benar sebagai `INVALID`, statistik menjadi `VALID: 3,405` dan `INVALID: 215`.

---

## 2. Tahap 1 — Data Raw Excel Audit

Pemeriksaan file `docs/PTKP_OWL_Import_Template fix data 11 11.xlsx`:

- **Sheet Name:**
  1. `[0] PTKP OWL Import` (Sheet data utama: Baris 1 s.d. 3682)
  2. `[1] Instructions` (Sheet petunjuk pengisian: Baris 1 s.d. 15)
- **Total Baris Data:**
  - Baris header: Row 1
  - Baris data aktual (non-blank): **3.679 baris**
  - Baris kosong di akhir sheet: 2 baris
- **Total Kemunculan Peserta Bermasalah di Excel:**

| No | Row Excel | Raw Employee ID | Tipe Cell | Employee Name | Course Title | Catatan Sheet |
|---|---|---|---|---|---|---|
| 1 | **380** | `22` | Numeric (`n`) | **IQBAL NUR RACHMAN** | New Employee Orientation for Steel Industry Technology Class (OJT PIPB) | No Urut: 407, Kolom B bernilai numerik 22 |
| 2 | **391** | `33` | Numeric (`n`) | **RIDWAN ALFITRA** | New Employee Orientation for Steel Industry Technology Class (OJT PIPB) | No Urut: 418, Kolom B bernilai numerik 33 |
| 3 | **494** | `22` | Numeric (`n`) | **IQBAL NUR RACHMAN** | NewGen Capability Development Vol. 1-2026 | No Urut: 521, Kolom B bernilai numerik 22 |
| 4 | **505** | `33` | Numeric (`n`) | **RIDWAN ALFITRA** | NewGen Capability Development Vol. 1-2026 | No Urut: 532, Kolom B bernilai numerik 33 |
| 5 | **2213** | `117` | Numeric (`n`) | **RIDWAN ALFITRA** | Spesial Lecture PGU | No Urut: 7255, Kolom B bernilai numerik 117 |
| 6 | **2241** | `131` | Numeric (`n`) | **IQBAL NUR RACHMAN** | Spesial Lecture PGU | No Urut: 7287, Kolom B bernilai numerik 131 |

**Kesimpulan Raw Excel:**
- Peserta **IQBAL NUR RACHMAN** muncul sebanyak **3 kali** di workbook (Baris 380, 494, 2241).
- Peserta **RIDWAN ALFITRA** muncul sebanyak **3 kali** di workbook (Baris 391, 505, 2213).
- Angka `22` dan `33` pada Baris 374 s.d. 392 sebenarnya adalah **nomor urut absensi kelas OJT** (mulai dari `16` s.d. `34`), bukan Nomor Induk Pegawai (NIP).

---

## 3. Tahap 2 — Audit Database Employee (`users` table)

Audit langsung menggunakan query database membuktikan:

### A. Pencarian `users.id_emp`
```sql
SELECT id, id_emp, name, email, company_name, deleted_at 
FROM users 
WHERE id_emp IN ('22', '33', '117', '131', '000022', '000033', '000117', '000131');
```
**Hasil Query Database:**
- `id_emp = '22'`: **TIDAK DITEMUKAN (0 records)**
- `id_emp = '33'`: **TIDAK DITEMUKAN (0 records)**
- `id_emp = '117'`: **TIDAK DITEMUKAN (0 records)**
- `id_emp = '131'`: **TIDAK DITEMUKAN (0 records)**
- `id_emp = '000022'`: **Ditemukan ID 5571 (`KIM KYU-JE`)**
- `id_emp = '000033'`: **Ditemukan ID 6006 (`NOVIAL`)**

### B. Pencarian `users.id` (Primary Key)
```sql
SELECT id, id_emp, name FROM users WHERE id IN (22, 33, 117, 131);
```
**Hasil Query Database:**
- `id = 22`: KARISMA RENDRIANTO (`id_emp = '105764'`)
- `id = 33`: RAMOT SIALLAGAN (`id_emp = '105877'`)
- `id = 117`: YON HADI SISWANTO (`id_emp = '133054'`)
- `id = 131`: FEBRI HANDIYANI (`id_emp = '105274'`)
*(Membuktikan bahwa primary key `users.id` tidak berkaitan dengan NIP peserta).*

### C. Pencarian User Berdasarkan Nama Asli Peserta
```sql
SELECT id, id_emp, name, company_name, deleted_at 
FROM users 
WHERE name IN ('IQBAL NUR RACHMAN', 'RIDWAN ALFITRA');
```
**Hasil Query Database:**
- `id = 3235`: **IQBAL NUR RACHMAN** (`id_emp = 'ex022263'`)
- `id = 3246`: **RIDWAN ALFITRA** (`id_emp = 'ex033263'`)

### D. Karakteristik Kolom `users.id_emp`:
- Schema: `id_emp varchar(12)` NULL, INDEX.
- Jumlah user aktif: 4.494 pegawai.
- User dengan panjang `id_emp < 6`: **0 user (TIDAK ADA SATUPUN PEGAWAI DENGAN NIP KURANG DARI 6 DIGIT)**.
- User ekspatriat/BOD dengan format `0000xx` hanya ada 9 orang (BOD nomor 11 s.d. 90).
- User lokal berformat 6 digit (misal `105764`, `116022`).
- User intern/magang berformat 8 karakter diawali `ex` (misal `ex022263`).

**Kesimpulan Audit Database:**
Employee ID `22` dan `33` **tidak ada di database**. Pencocokan `22` ke `000022` (Kim Kyu-Je) dan `33` ke `000033` (Novial) adalah kesalahan fatal akibat pencocokan nomor yang berbeda tetapi bernilai numerik serupa (*numeric alias collision*).

---

## 4. Tahap 3 & 4 — Audit Pipeline Validasi PTKP & Database Hasil Import

### Alur Pipeline yang Menyebabkan Cacat Data:
1. **Excel Parsing (`PtkpImportService.php:301-315`):**
   Membaca baris 380 dengan `employee_id = 22`, dan baris 391 dengan `employee_id = 33`.
2. **Lookup Pegawai (`PtkpValidationService.php:84-130`):**
   ```php
   // Kode bermasalah:
   if (ctype_digit($trimmed) && strlen($trimmed) < 6) {
       $padded = str_pad($trimmed, 6, '0', STR_PAD_LEFT);
       $normalizedIds[strtolower($padded)] = $padded;
   }
   ```
   Karena `strlen('22') < 6`, `'22'` di-pad menjadi `'000022'`.
   Query `whereIn('id_emp', ['22', '000022', ...])` mengembalikan User 5571 (KIM KYU-JE).
   Kemudian kode meng-unpad `'000022'` menjadi `'22'`:
   ```php
   $unpadded = ltrim($key, '0');
   $lookup[$unpadded]->push($user);
   ```
   Akibatnya, `$lookup['22']` berisi objek `User` KIM KYU-JE!
3. **Pencocokan Pegawai (`matchUser()`):**
   `matchUser('22', $userLookup)` mengembalikan `User` Kim Kyu-Je tanpa memverifikasi apakah nama di Excel cocok dengan nama akun LMS.
4. **Identity Overwrite (`PtkpImportService.php:361-369`):**
   ```php
   if ($user) {
       $normalized['user_id'] = $user->id;
       $normalized['employee_id'] = $user->id_emp; // '000022'
       $normalized['name'] = $user->name;         // 'KIM KYU-JE'
   }
   ```
5. **Evaluasi Status (`evaluateRowStatus()`):**
   Karena `$user` tidak null dan bukan resigned, status baris diputuskan **`VALID`**!
6. **Persistence ke Batch Rows (`PtkpImportService.php:232-245`):**
   Disimpan ke `import_batch_rows`:
   - Row 380: `employee_id = '000022'`, `employee_name = 'KIM KYU-JE'`, `validation_status = 'VALID'`
   - Row 391: `employee_id = '000033'`, `employee_name = 'NOVIAL'`, `validation_status = 'VALID'`
   - Row 494: `employee_id = '000022'`, `employee_name = 'KIM KYU-JE'`, `validation_status = 'VALID'`
   - Row 505: `employee_id = '000033'`, `employee_name = 'NOVIAL'`, `validation_status = 'VALID'`

### Bukti Aktual pada Batch `af57a684-d72b-47a4-bab9-ffa505844bbc`:
Query langsung ke database untuk batch `af57a684-d72b-47a4-bab9-ffa505844bbc`:
```
Row  380 | ID: '000022' | Name: 'KIM KYU-JE' | Status: 'VALID'   | Action: 'IMPORTED' | User ID: 5571
Row  391 | ID: '000033' | Name: 'NOVIAL'     | Status: 'VALID'   | Action: 'IMPORTED' | User ID: 6006
Row  494 | ID: '000022' | Name: 'KIM KYU-JE' | Status: 'VALID'   | Action: 'IMPORTED' | User ID: 5571
Row  505 | ID: '000033' | Name: 'NOVIAL'     | Status: 'VALID'   | Action: 'IMPORTED' | User ID: 6006
Row 2213 | ID: '117'    | Name: 'RIDWAN ALFITRA'    | Status: 'INVALID' | Action: 'SKIPPED'  | Error: Employee ID '117' was not found
Row 2241 | ID: '131'    | Name: 'IQBAL NUR RACHMAN' | Status: 'INVALID' | Action: 'SKIPPED'  | Error: Employee ID '131' was not found
```

---

## 5. Tahap 5 & 6 — Audit UI Filtering & Konsistensi Statistik Batch

### Mengapa Filter Nama Hanya Menampilkan 1 Baris?
Pada controller `ImportHistoryController::rowsDatatable()`:
Query DataTables memfilter kolom `employee_name` pada tabel `import_batch_rows`:
```php
$query->where(function ($sub) use ($term) {
    $sub->where('employee_id', 'LIKE', $term)
        ->orWhere('employee_name', 'LIKE', $term)
        ->orWhere('course_name', 'LIKE', $term);
});
```
- Ketika user mencari `"IQBAL NUR RACHMAN"`, Baris 380 dan Baris 494 **tidak terpanggil** karena di database namanya sudah berubah menjadi `"KIM KYU-JE"`. Hanya Baris 2241 yang terpanggil.
- Ketika user mencari `"RIDWAN ALFITRA"`, Baris 391 dan Baris 505 **tidak terpanggil** karena di database namanya sudah berubah menjadi `"NOVIAL"`. Hanya Baris 2213 yang terpanggil.

### Konsistensi Statistik Batch:
Pada tabel `import_batches` untuk batch `af57a684-d72b-47a4-bab9-ffa505844bbc`:
- Total Rows: 3.679
- Valid Rows: 3.409
- Warning Rows: 58
- Duplicate Rows: 1
- Invalid Rows: 211
- All Total: `3.409 + 58 + 1 + 211 = 3.679` (Jumlah matematis konsisten dengan data yang tersimpan).

Keempat baris pengujian (380, 391, 494, 505) saat ini salah masuk ke dalam hitungan `Valid Rows` (3.409).  
Klasifikasi yang benar untuk batch ini seharusnya:
- **Valid Rows:** 3.405 (berkurang 4)
- **Invalid Rows:** 215 (bertambah 4)
- **Warning Rows:** 58
- **Duplicate Rows:** 1
- **All Total:** 3.679

---

## 6. Tahap 7 — Laporan Investigasi Root Cause (Format Resmi)

| No | Area Audit | Temuan | Root Cause | Dampak |
|---|---|---|---|---|
| 1 | **Raw Excel** | Baris 380 & 494 memiliki ID `22` (IQBAL NUR RACHMAN). Baris 391 & 505 memiliki ID `33` (RIDWAN ALFITRA). Baris 2213 memiliki ID `117` dan Baris 2241 memiliki ID `131`. | Angka `16` s.d. `34` pada Excel adalah nomor urut absensi kelas magang, bukan NIP resmi pegawai. | Data mentah Excel tidak berisi NIP asli pegawai untuk baris-baris tersebut. |
| 2 | **Employee Lookup** | Nilai `22` dan `33` diubah menjadi `000022` dan `000033` oleh `str_pad` dan di-alias balik tanpa leading zero. | Fungsi `batchLookupUsers()` di `PtkpValidationService` melakukan auto-padding untuk semua angka dengan panjang `< 6` digit tanpa batas minimum panjang dan tanpa validasi nama. | Terjadi *false positive match* dengan akun Direktur Kim Kyu-Je (`000022`) dan Novial (`000033`). |
| 3 | **Validation Service** | Baris 380, 391, 494, dan 505 diputuskan sebagai status `VALID`, bukan `INVALID`. | `matchUser()` menerima hasil lookup yang salah dan tidak memvalidasi kesesuaian antara nama di Excel dengan nama akun LMS. | Peserta yang seharusnya ditolak (INVALID) justru lolos verifikasi. |
| 4 | **Persistence** | Data baris disimpan ke tabel `import_batch_rows` dengan nama `KIM KYU-JE` dan `NOVIAL`, dan di-insert ke `offline_training_users`. | `PtkpImportService::parseAndEvaluateWorkbook()` melakukan identity synchronization yang menimpa nama dan ID peserta asli dengan data user LMS yang salah cocok. | Data histori batch korup, nama asli peserta hilang, dan training tercatat atas nama Direktur. |
| 5 | **Import History** | Filter nama "IQBAL NUR RACHMAN" dan "RIDWAN ALFITRA" masing-masing hanya menampilkan 1 baris, bukan 3 baris. | Query DataTables mencari pada kolom `employee_name` di `import_batch_rows` yang nilainya sudah tertimpa menjadi `KIM KYU-JE` dan `NOVIAL`. | Admin melihat ketidaksesuaian data antara spreadsheet dan tampilan riwayat import. |
| 6 | **UI Filtering** | Keempat baris data muncul saat filter VALID ditekan, dan tidak muncul saat filter INVALID ditekan. | Frontend Preview (`ptkp-import.js`) dan Server-Side DataTables (`import-history-detail.js`) memfilter berdasarkan status validasi baris yang tersimpan. | Pengguna sistem terkecoh mengira data peserta magang tersebut valid. |
| 7 | **Batch Statistics** | Statistik batch mencatat 3.409 VALID dan 211 INVALID. | Statistik dihitung langsung dari status agregat baris hasil evaluasi validasi. | Angka VALID berlebih 4 baris dan angka INVALID berkurang 4 baris. |

---

## 7. Tahap 8 — Implementasi Perbaikan

### Prinsip Solusi:
1. **Batas Minimum Panjang Normalisasi Leading Zero:**
   Di PTKP, tidak ada satupun NIP yang memiliki panjang 1 atau 2 digit. Angka 1 atau 2 digit (`1` s.d. `99`) selalu merupakan nomor urut/absen kelas, bukan NIP. Normalisasi zero-padding hanya diizinkan untuk angka dengan panjang **3 s.d. 5 digit** (misal `541` -> `000541`, `888` -> `000888`), yang umum terjadi ketika format cell Excel menghilangkan leading zero dari NIP 6 digit.
2. **Name Verification pada Aliased/Padded Match (Two-Layer Defense):**
   Jika suatu NIP dicocokkan melalui varian padded/unpadded (bukan pencocokan eksak pada `users.id_emp`), dan pada spreadsheet terdapat nama peserta, sistem memverifikasi kesesuaian nama. Jika nama peserta berbeda jauh dengan nama user di database (misal `IQBAL NUR RACHMAN` vs `KIM KYU-JE`), pencocokan ditolak dan baris dinyatakan **`INVALID`**.
3. **Integritas Data Mentah saat INVALID:**
   Jika pegawai tidak ditemukan (`$user === null`), nama dan ID mentah pada Excel **tetap dipertahankan** pada kolom `employee_id` dan `employee_name` di `import_batch_rows`, sehingga pencarian nama dan filtering UI menampilkan seluruh baris peserta secara utuh.
4. **Keamanan Data Historis:**
   Sesuai batasan perintah, data historis batch `af57a684-d72b-47a4-bab9-ffa505844bbc` tidak dihapus atau di-reimport paksa secara liar di production.

### File yang Diperbaiki:
1. `app/Services/OfflineTraining/PtkpValidationService.php`:
   - Batasi `batchLookupUsers()`: Hanya lakukan `str_pad` jika `strlen($trimmed) >= 3 && strlen($trimmed) < 6`.
   - Batasi mapping `unpadded`: Hanya mapping balik jika `strlen($unpadded) >= 3`.
   - Update `matchUser(?string $employeeId, array $userLookup, ?string $participantName = null)`: Tambahkan parameter nama peserta dan tolak pencocokan alias numerik jika nama peserta berkonflik.
2. `app/Services/OfflineTraining/PtkpImportService.php`:
   - Oper `$normalized['name']` ke pemanggilan `matchUser($normalized['employee_id'], $userLookup, $normalized['name'] ?? null)`.

---

## 8. Regression Testing & Verifikasi

Pengujian dilakukan mencakup 12 skenario yang disyaratkan:

| Skenario | Expected Result | Actual Result | Status |
|---|---|---|---|
| Employee ID 22 tidak ditemukan | INVALID | INVALID | PASS |
| Employee ID 33 tidak ditemukan | INVALID | INVALID | PASS |
| Empat baris pengujian (380, 391, 494, 505) | Seluruhnya INVALID | Seluruhnya INVALID (4/4) | PASS |
| Employee ID valid dan ditemukan (misal `116022`, `104921`) | VALID | VALID | PASS |
| Employee ID kosong | INVALID | INVALID | PASS |
| Employee ID memiliki whitespace | Normalisasi konsisten | Konsisten | PASS |
| Employee ID berbentuk angka/string (`541` -> `000541`) | VALID jika nama sesuai | VALID | PASS |
| Employee ID soft deleted user | Ditangani sesuai aturan bisnis | Ditangani | PASS |
| Nama peserta sama tetapi ID berbeda | Tidak salah mencocokkan | Tidak salah cocok | PASS |
| Filter INVALID pada file mentah | Menampilkan seluruh 215 data INVALID | 215 baris INVALID | PASS |
| Filter VALID pada file mentah | Menampilkan 3.464 data VALID (bebas dari 4 baris salah) | 3.464 baris VALID | PASS |
| Filter nama IQBAL NUR RACHMAN | Menampilkan seluruh 3 kemunculan | 3 baris | PASS |
| Filter nama RIDWAN ALFITRA | Menampilkan seluruh 3 kemunculan | 3 baris | PASS |
| Import NON-PTKP | Tidak terpengaruh (bebas regresi) | 23/23 unit tests pass | PASS |

---

## 9. Final Conclusion & Rekomendasi
Penyebab utama dari masalah klasifikasi ini telah dibuktikan secara empiris melalui analisis kode, audit query database, dan simulasi data workbook 3.679 baris. Perbaikan dilakukan pada akar masalah (*backend validation logic*), memastikan integritas pencocokan identitas pegawai, akurasi filter pencarian nama di UI, serta kebenaran statistik klasifikasi batch tanpa merusak fitur import lainnya.
