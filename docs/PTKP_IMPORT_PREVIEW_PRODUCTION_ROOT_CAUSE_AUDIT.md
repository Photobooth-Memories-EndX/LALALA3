# PRODUCTION ROOT CAUSE AUDIT & TECHNICAL INVESTIGATION REPORT
## PTKP Import — Invalid Employee ID Classification, Preview Data, Filtering & Statistics

- **Date:** 2026-11-11
- **Target Environments:**
  - **Production:** `https://owl.krakatauposco.co.id/manage/record/offline/import-ptkp`
  - **Local Development:** `http://127.0.0.1:8000/manage/record/offline/import-ptkp`
- **Source Test File:** `docs/PTKP_OWL_Import_Template fix data 11 11.xlsx` (Total Rows: 3,679)
- **Status:** **INVESTIGATION COMPLETE & ROOT CAUSE RESOLVED**

---

## 1. Executive Summary & Root Cause Analysis

### Pertanyaan Utama:
Mengapa pada screenshot production halaman Import PTKP masih menampilkan statistik **VALID 3.409** dan **INVALID 211** (padahal seharusnya **VALID 3.405** dan **INVALID 215**), dan mengapa pencarian Employee ID maupun Employee Name tidak menemukan data yang diharapkan secara presisi?

### Root Cause 1: Perbedaan Status Deployment (Local Working Tree vs. Production Commit)
- **Fakta Teknis:** Perbaikan pada `PtkpValidationService.php` dan `PtkpImportService.php` yang membatasi auto-padding dan menambahkan verifikasi nama (`name correspondence verification`) sebelumnya dibuat di lingkungan lokal dan tersimpan sebagai **uncommitted changes / working tree modifications** pada branch `certificate/rev`.
- **Dampak di Production:** Server production (`owl.krakatauposco.co.id`) menjalankan commit HEAD (`10f60aed`) dari remote repository yang **belum memuat** perbaikan validasi ini. Di production, logika lama `strlen($trimmed) < 6` masih aktif berjalan, sehingga upload preview baru di production secara otomatis tetap men-zero-pad ID `22` menjadi `000022` (KIM KYU-JE) dan `33` menjadi `000033` (NOVIAL). Akibatnya, statistik preview di production tetap menghasilkan **VALID: 3.409** dan **INVALID: 211**.
- **Bukti di Local:** Ketika endpoint preview (`POST /manage/record/offline/import-ptkp/preview`) dijalankan di lingkungan lokal dengan kode perbaikan aktif, hasilnya menghasilkan statistik yang tepat:
  - **TOTAL ROWS:** `3.679`
  - **VALID:** `3.405`
  - **WARNING:** `58`
  - **DUPLICATE:** `1`
  - **INVALID:** `215` (100% karena Invalid Employee ID)
  - **IMPORTABLE COUNT:** `3.463`

---

### Root Cause 2: Kelemahan Filtering Substring vs. Exact Match pada Frontend DataTables (`ptkp-import.js`)
- **Fakta Teknis:**
  Pada implementasi awal `public/js/manage/ptkp-import.js`:
  ```javascript
  if (searchQuery.empId && (row.employee_id || '').toLowerCase().indexOf(searchQuery.empId) === -1) return false;
  ```
  1. **Loose Substring Collision:** Operator `.indexOf('22')` mencocokkan seluruh NIP 6-digit yang memuat substring `"22"` di mana saja (misalnya `107225`, `102263`, `122001`, `116022`, dsb). Akibatnya, pencarian Employee ID `22` memunculkan **212 baris** data karyawan lain, sehingga mengaburkan 2 baris target ID `22`. Hal yang sama terjadi pada `33` yang memunculkan **185 baris**.
  2. **Tipe Data Kerapuhan:** Jika `row.employee_id` bertipe angka/integer pada JSON response, pemanggilan langsung `(row.employee_id || '').toLowerCase()` akan memicu JavaScript `TypeError: toLowerCase is not a function` dan merusak proses filter.
- **Perbaikan yang Dilakukan:**
  Logika filter disempurnakan dengan *Exact-Match-Priority*:
  Jika nilai pencarian `searchQuery.empId` memiliki kecocokan persis (*exact match*) terhadap salah satu `employee_id` di dalam dataset (misalnya `22`, `33`, atau NIP penuh), sistem secara cerdas beralih ke exact matching (`rowEmpId === qEmpId`). Jika tidak ada exact match (misalnya user mengetik awalan parsial `107`), sistem baru menggunakan fallback substring matching.
  - Hasil pencarian `Employee ID = 22`: Tepat **2 baris** (Row 380 & Row 494).
  - Hasil pencarian `Employee ID = 33`: Tepat **2 baris** (Row 391 & Row 505).

---

### Root Cause 3: Ambiguitas Header Tabel `Employee Name (from users)`
- **Fakta Teknis:**
  Header tabel pada `resources/views/manage/record/offline/import-ptkp.blade.php` sebelumnya tertulis:
  `<th>Employee Name (from users)</th>`
  Header ini menimbulkan kebingungan bagi administrator: untuk record yang INVALID (karena Employee ID tidak ditemukan di database LMS), nama yang ditampilkan sebenarnya adalah nama asli mentah dari Excel (`IQBAL NUR RACHMAN` dan `RIDWAN ALFITRA`), bukan dari database users. Label `(from users)` memberi kesan seolah-olah nama tersebut selalu ditarik dari database LMS.
- **Perbaikan yang Dilakukan:**
  1. Header tabel diselaraskan menjadi `<th>Employee Name</th>` (konsisten dengan format `import-non-ptkp.blade.php`).
  2. Pada modal inspeksi detail baris (*row inspector drawer*), label ditampilkan secara dinamis:
     - Jika user terpetakan ke LMS: `2. Employee Name (from users)`
     - Jika user tidak terpetakan (INVALID): `2. Employee Name (Excel)`

---

## 2. Tabel Perbandingan Lingkungan: Local vs. Production

| Komponen / Indikator | Kondisi di Production (Sebelum Deploy) | Kondisi di Local (Setelah Perbaikan) |
| :--- | :--- | :--- |
| **Commit Git / State** | Commit `10f60aed` (Belum ada fix padding) | Working Tree dengan perbaikan validasi & JS filter |
| **Logika Padding NIP** | `strlen < 6` (ID 22 & 33 di-pad jadi 000022 & 000033) | `strlen >= 3 && strlen < 6` (ID 22 & 33 tidak di-pad) |
| **Name Correspondence** | Tidak ada verifikasi nama | Verifikasi nama aktif (Levenshtein defense) |
| **Hasil Preview ID 22 & 33** | Terbaca `VALID` (Akun Kim Kyu-Je & Novial) | Terbaca **`INVALID`** (`IQBAL NUR RACHMAN` & `RIDWAN ALFITRA`) |
| **Statistik VALID** | **3.409** | **3.405** (Berkurang 4 baris) |
| **Statistik INVALID** | **211** | **215** (Bertambah 4 baris) |
| **Statistik WARNING** | 58 | 58 (Konsisten) |
| **Statistik DUPLICATE**| 1 | 1 (Konsisten) |
| **Statistik TOTAL ROWS**| 3.679 | 3.679 (Konsisten) |
| **Filter ID = 22** | Menghasilkan 0 baris (karena ID tertimpa jadi 000022) | Menghasilkan tepat **2 baris** (Row 380 & 494) |
| **Filter ID = 33** | Menghasilkan 0 baris (karena ID tertimpa jadi 000033) | Menghasilkan tepat **2 baris** (Row 391 & 505) |
| **Filter Nama IQBAL** | Tidak menemukan data baris 380/494 (karena tertimpa KIM) | Menghasilkan data dengan status **INVALID** |
| **Header Tabel Name** | `Employee Name (from users)` | `Employee Name` (Netral & Akurat) |

---

## 3. Alur Data End-to-End (Preview Pipeline)

```
[1. User Uploads Excel]
       │
       ▼
[2. PtkpImportController::preview]
  -> Validasi ekstensi (.xlsx/.xls) & ukuran file
  -> PtkpImportService::storeTemporaryFile
       │
       ▼
[3. PtkpImportService::parseAndPreview]
  -> PtkpExcelSchema::detectTemplateFormat & analyzeHeaders
  -> PtkpValidationService::batchLookupUsers
       │  (Hanya pad angka jika length 3 s.d 5; ID 22 & 33 TIDAK di-pad)
       │  (Unpadded lookup hanya jika length >= 3)
       ▼
[4. PtkpValidationService::matchUser]
  -> 2-Layer Defense: Jika match via alias numerik, verifikasi kesesuaian nama
  -> ID 22 vs KIM KYU-JE (Levenshtein mismatch) => user = null, is_not_found = true
  -> ID 33 vs NOVIAL (Levenshtein mismatch) => user = null, is_not_found = true
       │
       ▼
[5. PtkpValidationService::evaluateRowStatus]
  -> Evaluasi status baris: STATUS_INVALID
  -> is_invalid_id = true
  -> Pesan error: "ERROR: Employee ID '22' was not found in LMS users database."
  -> Identitas asli Excel dipertahankan: employee_id = '22', name = 'IQBAL NUR RACHMAN'
       │
       ▼
[6. Preview JSON Payload Response]
  -> stats.valid = 3405, stats.invalid = 215, stats.importable_count = 3463
  -> rows[379] & rows[493]: status = INVALID, emp_id = '22', name = 'IQBAL NUR RACHMAN'
  -> rows[389] & rows[503]: status = INVALID, emp_id = '33', name = 'RIDWAN ALFITRA'
       │
       ▼
[7. Frontend Rendering & Filtering (ptkp-import.js)]
  -> KPI Summary Cards render angka backend secara real-time
  -> Exact-match priority pada search input Employee ID
  -> DataTables rendering menampilkan nama asli peserta
```

---

## 4. Evaluasi Risiko Deployment & Rekomendasi

### Risiko:
1. **Browser Cache / Asset Versioning:** File `public/js/manage/ptkp-import.js` yang dimodifikasi dapat tersimpan di browser cache client jika versi asset tidak diperbarui.
2. **Laravel Route / Config Cache:** Di production, perubahan controller atau service membutuhkan pembersihan cache aplikasi (`php artisan view:clear`, `php artisan config:clear`).

### Rekomendasi Deployment:
1. Commit seluruh perubahan lokal pada `certificate/rev`.
2. Push ke remote origin dan lakukan deployment ke server production.
3. Jalankan pembersihan cache di server production:
   ```bash
   php artisan view:clear
   php artisan cache:clear
   ```
4. Tambahkan cache-busting query parameter pada script tag blade jika diperlukan:
   `<script src="{{ asset('js/manage/ptkp-import.js') }}?v={{ filemtime(public_path('js/manage/ptkp-import.js')) }}"></script>`
