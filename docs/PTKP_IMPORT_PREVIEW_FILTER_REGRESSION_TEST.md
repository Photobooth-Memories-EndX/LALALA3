# REGRESSION TEST & VERIFICATION REPORT
## PTKP Import Preview — Data Classification, Statistics & Search Filtering

- **Date:** 2026-11-11
- **Test File:** `docs/PTKP_OWL_Import_Template fix data 11 11.xlsx`
- **Feature Test:** `tests/Feature/PtkpImportPreviewFilterRegressionTest.php`
- **Result:** **ALL TESTS PASSED (100% SUCCESS)**

---

## 1. Automated Test Suites Summary

| Test Class | Assertions | Result | Description |
| :--- | :---: | :---: | :--- |
| **`PtkpImportPreviewFilterRegressionTest`** | 30 | **PASS** | Validates preview statistics, 4 target rows, and frontend filter matrix. |
| **`PtkpInvalidEmployeeIdAuditTest`** | 61 | **PASS** | Validates core employee ID validation, normalization, and alias defenses. |
| **`PtkpRemediateHistoricalBatchCommandTest`** | 22 | **PASS** | Validates historical batch remediation, idempotency, and rollback. |
| **`NonPtkpImportTest`** | 48 | **PASS** | Regression protection: ensures zero regression on Non-PTKP import logic. |

---

## 2. Test Evidence: Four Target Rows in Upload Preview

Evaluated directly against raw workbook `docs/PTKP_OWL_Import_Template fix data 11 11.xlsx`:

| Row in Excel | Employee ID | Employee Name | Course Title | Validation Status | Error Message |
| :---: | :---: | :--- | :--- | :---: | :--- |
| **380** | **`22`** | **IQBAL NUR RACHMAN** | New Employee Orientation for Steel Industry Technology Class (OJT PIPB) | **`INVALID`** | `ERROR: Employee ID '22' was not found in LMS users database.` |
| **391** | **`33`** | **RIDWAN ALFITRA** | New Employee Orientation for Steel Industry Technology Class (OJT PIPB) | **`INVALID`** | `ERROR: Employee ID '33' was not found in LMS users database.` |
| **494** | **`22`** | **IQBAL NUR RACHMAN** | NewGen Capability Development Vol. 1-2026 | **`INVALID`** | `ERROR: Employee ID '22' was not found in LMS users database.` |
| **505** | **`33`** | **RIDWAN ALFITRA** | NewGen Capability Development Vol. 1-2026 | **`INVALID`** | `ERROR: Employee ID '33' was not found in LMS users database.` |

- **False Positive Count:** `0` (None matched to KIM KYU-JE or NOVIAL).
- **Identity Preservation:** Raw Excel Employee ID (`22`, `33`) and Raw Excel Name (`IQBAL NUR RACHMAN`, `RIDWAN ALFITRA`) are 100% preserved.

---

## 3. Test Evidence: Preview Summary Statistics (HTTP API Response)

Endpoint: `POST /manage/record/offline/import-ptkp/preview`

| Metric / KPI Card | Previous (Production) | Expected | Actual (Local Preview API) | Verification |
| :--- | :---: | :---: | :---: | :--- |
| **TOTAL ROWS** | 3,679 | 3,679 | **3,679** | Matches total non-empty rows |
| **VALID** | 3,409 | 3,405 | **3,405** | Exact match (-4 rows) |
| **WARNING** | 58 | 58 | **58** | Exact match |
| **DUPLICATE** | 1 | 1 | **1** | Exact match |
| **INVALID ID & COURSE TITLE** | 211 | 215 | **215** | Exact match (+4 rows) |
| **INVALID COMPANY** | 0 | 0 | **0** | Exact match |
| **TOTAL TO IMPORT (Step 3)** | 3,467 | 3,463 | **3,463** | Matches valid (3,405) + warning (58) |
| **TOTAL NOT IMPORTED (Step 3)** | 212 | 216 | **216** | Matches invalid (215) + duplicate (1) |

---

## 4. Test Evidence: Search & Filter Matrix Simulation

Verified via `PtkpImportPreviewFilterRegressionTest::test_frontend_search_and_filter_matrix`:

| Filter / Search Combination | Expected Count | Actual Count | Details / Row Numbers Matched |
| :--- | :---: | :---: | :--- |
| **Filter ALL (Default)** | 3,679 rows | **3,679 rows** | Full dataset available. |
| **Filter VALID** | 3,405 rows | **3,405 rows** | Rows 380, 391, 494, 505 are strictly excluded. |
| **Filter INVALID** | 215 rows | **215 rows** | Rows 380, 391, 494, 505 are all included. |
| **Filter WARNING** | 58 rows | **58 rows** | Contains non-blocking empty red header fields. |
| **Filter DUPLICATE** | 1 row | **1 row** | In-file duplicate detected. |
| **Search Employee ID = `22`** | 2 rows | **2 rows** | Row 380 & Row 494 (Exact match priority active). |
| **Search Employee ID = `33`** | 2 rows | **2 rows** | Row 391 & Row 505 (Exact match priority active). |
| **Search Name = `IQBAL NUR RACHMAN`** | 3 rows | **3 rows** | Row 380 (ID 22), Row 494 (ID 22), Row 2241 (ID 131). |
| **Search Name = `RIDWAN ALFITRA`** | 3 rows | **3 rows** | Row 391 (ID 33), Row 505 (ID 33), Row 2213 (ID 117). |
| **Filter INVALID + Search ID `22`** | 2 rows | **2 rows** | Row 380 & Row 494. |
| **Filter VALID + Search ID `22`** | 0 rows | **0 rows** | Correct: ID 22 does not appear in VALID. |
| **Filter VALID + Search ID `33`** | 0 rows | **0 rows** | Correct: ID 33 does not appear in VALID. |
| **Reset Filters Button** | 3,679 rows | **3,679 rows** | Resets queries and restores view to total rows. |

---

## 5. Non-PTKP Regression Verification

Running `php artisan test tests/Feature/NonPtkpImportTest.php` confirmed:
- `✓ non ptkp import page requires auth and permission`: PASS
- `✓ non ptkp template download`: PASS
- `✓ non ptkp preview returns validation and canonical columns`: PASS
- `✓ non ptkp duplicate in same file is skipped`: PASS
- `✓ non ptkp unmatched employee produces warning and is importable`: PASS
- `✓ non ptkp all duplicates returns no new records state`: PASS
- `✓ non ptkp confirm persists valid records and cleans up`: PASS
- **Total:** 23/23 tests passed cleanly.

---

## 6. Final Acceptance Criteria Verification

- [x] Empat baris ID 22 dan 33 masuk INVALID pada preview upload baru.
- [x] Keempatnya tidak muncul pada VALID.
- [x] Search ID 22 dan 33 berfungsi dan menghasilkan tepat 2 baris masing-masing.
- [x] Search nama IQBAL dan RIDWAN berfungsi dan menampilkan status INVALID.
- [x] Filter ALL menampilkan seluruh data (3.679 baris).
- [x] Filter INVALID menampilkan seluruh data invalid (215 baris).
- [x] Statistik KPI summary cards sesuai dengan hasil evaluasi (Valid: 3.405, Invalid: 215).
- [x] Tidak ada enrollment salah yang dibuat.
- [x] Akun direktur KIM KYU-JE dan NOVIAL tidak terdampak.
- [x] Historical batch `af57a684-d72b-47a4-bab9-ffa505844bbc` tetap utuh.
- [x] Regression test PTKP dan NON-PTKP 100% lulus.
