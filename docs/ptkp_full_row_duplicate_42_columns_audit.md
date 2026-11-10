# PTKP Full Row Duplicate Detection — 42 Canonical Columns Audit Report

**Module:** `/manage/record/offline/import-ptkp`  
**Date:** November 4, 2026  
**Status:** IMPLEMENTED, AUDITED, AND FULLY VERIFIED  
**Test Suite:** 49/49 Scenarios Passing (`PtkpFullRowDuplicateDetectionTest`)  

---

## 1. Executive Summary & Objective

The duplicate detection mechanism in the PTKP Offline Training Import module (`/manage/record/offline/import-ptkp`) has been audited and updated to strictly enforce the **Official 42 Canonical Column Specification**.

Prior implementations relied either on a 6-field training fingerprint (which inadvertently flagged participants taking distinct training sessions with different classifications or scores as duplicates) or an unaligned 35/37 column list. 

Under the canonical 42-column specification:
1. **Column `No` is strictly excluded** and never used in duplicate evaluation.
2. **Exactly 42 official business columns** are evaluated for row uniqueness.
3. **In-File Rule:** Two rows within an import file are marked as `DUPLICATE` if and only if **all 42 columns** have identical normalized values. If at least one column differs, both rows remain `VALID`.
4. **Database Comparison Rule & Scenario 48:** When comparing an imported row against existing database records:
   - Only columns physically stored in the database (40 columns) are evaluated.
   - Unstored columns (`progress_status`, `month`) are documented in `unverified_fields` metadata and do not block database duplicate evaluation.
   - **Database NULL is never assumed to match a non-empty Excel value.** If an existing database record has `NULL` or empty for a stored column while the import row has a populated value, the system refuses to claim a full row match (`database_null_unverified`), preventing false positive duplicate detection on legacy records.

---

## 2. Official 42 Canonical Columns

The comparison evaluates exactly 42 columns grouped into 7 logical business sections:

| # | Section | Column Name | Internal Key | Data Type | Database Stored? |
|---|---|---|---|---|:---:|
| 1 | Employee Information | Employee ID | `employee_id` | String | **Yes** (`offline_training_users.employee_id`) |
| 2 | Employee Information | Employee Name | `name` | String | **Yes** (`offline_training_users.name`) |
| 3 | Employee Information | Grade | `grade` | String | **Yes** (`offline_training_users.grade`) |
| 4 | Employee Information | Job Position | `job_position` | String | **Yes** (`offline_training_users.job_position`) |
| 5 | Employee Information | Team | `team` | String | **Yes** (`offline_training_users.team`) |
| 6 | Employee Information | Department | `department` | String | **Yes** (`offline_training_users.department`) |
| 7 | Employee Information | Company | `company` | String | **Yes** (`offline_training_users.company`) |
| 8 | Training Information | Course Title | `course_name` | String | **Yes** (`offline_trainings.course_name`) |
| 9 | Training Information | Sub Course Title | `sub_course_name` | String | **Yes** (`offline_trainings.sub_course_name`) |
| 10 | Training Information | Type | `type` | String | **Yes** (`offline_trainings.type`) |
| 11 | Training Information | Category | `category` | String | **Yes** (`offline_trainings.category`) |
| 12 | Training Information | Level | `level` | String | **Yes** (`offline_trainings.level`) |
| 13 | Training Information | Batch | `batch` | String | **Yes** (`offline_trainings.batch`) |
| 14 | Classification | Main Classification | `main_classification` | String | **Yes** (`offline_training_users.main_classification`) |
| 15 | Classification | Sub Classification | `sub_classification` | String | **Yes** (`offline_training_users.sub_classification`) |
| 16 | Delivery & Provider | Delivery Method | `delivery_method` | String | **Yes** (`offline_trainings.delivery_method`) |
| 17 | Delivery & Provider | Sub Delivery Method | `sub_delivery_method` | String | **Yes** (`offline_trainings.sub_delivery_method`) |
| 18 | Delivery & Provider | Provider Name | `provider_name` | String | **Yes** (`offline_trainings.provider_name`) |
| 19 | Delivery & Provider | Venue | `venue` | String | **Yes** (`offline_trainings.venue`) |
| 20 | Delivery & Provider | Instructor | `instructor` | String | **Yes** (`offline_trainings.instructor`) |
| 21 | Learning Details | Course Duration | `course_duration` | Numeric (Seconds) | **Yes** (`offline_trainings.duration_seconds`) |
| 22 | Learning Details | Learning Hours | `learning_hours` | Decimal | **Yes** (`offline_training_users.learning_hours`) |
| 23 | Learning Details | Progress | `progress` | Decimal | **Yes** (`offline_training_users.progress`) |
| 24 | Learning Details | Progress Status | `progress_status` | String | **No** (Virtual / spreadsheet only) |
| 25 | Learning Details | Start Date | `start_date` | Date (YYYY-MM-DD) | **Yes** (`offline_training_users.start_date`) |
| 26 | Learning Details | End Date | `end_date` | Date (YYYY-MM-DD) | **Yes** (`offline_training_users.end_date`) |
| 27 | Learning Details | Start Time | `start_time` | Time (HH:MM) | **Yes** (`offline_training_users.start_time`) |
| 28 | Learning Details | End Time | `end_time` | Time (HH:MM) | **Yes** (`offline_training_users.end_time`) |
| 29 | Learning Details | Record Month / Month | `month` | Integer (1-12) | **No** (Administrative report period) |
| 30 | Assessment, Attendance & Bond | Requirement | `requirement` | String | **Yes** (`offline_trainings.requirement`) |
| 31 | Assessment, Attendance & Bond | Attendance | `attendance` | Boolean ('1'/'0') | **Yes** (`offline_training_users.attendance`) |
| 32 | Assessment, Attendance & Bond | Attendance Reason | `attendance_reason` | String | **Yes** (`offline_training_users.attendance_reason`) |
| 33 | Assessment, Attendance & Bond | Bond Status | `bond_status` | String | **Yes** (`offline_training_users.bond_status`) |
| 34 | Assessment, Attendance & Bond | Bond Duration | `bond_duration` | Integer (Months) | **Yes** (`offline_training_users.bond_duration`) |
| 35 | Assessment, Attendance & Bond | Bond End Date | `bond_end_date` | Date (YYYY-MM-DD) | **Yes** (`offline_training_users.bond_end_date`) |
| 36 | Assessment, Attendance & Bond | Bond Amount | `bond_amount` | Decimal (IDR) | **Yes** (`offline_training_users.bond_amount_idr`) |
| 37 | Assessment, Attendance & Bond | L1 Program | `l1_program` | String | **Yes** (`offline_training_users.l1_program`) |
| 38 | Assessment, Attendance & Bond | Assessment Score | `assessment_score` | Decimal | **Yes** (`offline_training_users.assessment_score`) |
| 39 | Assessment, Attendance & Bond | L2 Verbal | `l2_verbal` | String | **Yes** (`offline_training_users.l2_verbal`) |
| 40 | Assessment, Attendance & Bond | L3 Numerical | `l3_numerical` | Decimal | **Yes** (`offline_training_users.l3_numerical`) |
| 41 | Assessment, Attendance & Bond | L3 Verbal | `l3_verbal` | String | **Yes** (`offline_training_users.l3_verbal`) |
| 42 | Person in Charge | PIC | `pic` | String | **Yes** (`offline_training_users.pic`) |

---

## 3. Columns Strictly Excluded

The following columns are explicitly excluded from duplicate detection:

1. **`No` / `row_no`**: Preview row index or row sequence number in the spreadsheet.
2. **`Provider`**: Broad provider category ('Internal' vs 'External'). *Provider Name is compared.*
3. **`Sub Provider`**: Provider subcategory ('Vendor', 'Internal-Training Team').
4. **`Certificate Availability` / `Certificate`**: Certificate availability indicator.
5. **`Certificate Institution`**: Issuing institution for external certificate.
6. **`Certificate Issued Date`**: Date the certificate was issued.
7. **`Certificate Expired Date`**: Date the certificate expires.
8. **System Timestamps & Metadata**: `created_at`, `updated_at`, `deleted_at`, `user_id`, `id`.

---

## 4. Database Schema Audit: Stored vs Unstored Columns

An exhaustive audit of MySQL tables `offline_trainings` and `offline_training_users` confirms:

- **Physically Stored Columns: 40 Columns**
  - Stored in `offline_training_users`: `employee_id`, `name`, `grade`, `job_position`, `team`, `department`, `company`, `main_classification`, `sub_classification`, `learning_hours`, `progress`, `start_date`, `end_date`, `start_time`, `end_time`, `attendance`, `attendance_reason`, `bond_status`, `bond_duration`, `bond_end_date`, `bond_amount_idr`, `l1_program`, `assessment_score`, `l2_verbal`, `l3_numerical`, `l3_verbal`, `pic`.
  - Stored in `offline_trainings`: `course_name`, `sub_course_name`, `type`, `category`, `level`, `batch`, `main_classification`, `sub_classification`, `delivery_method`, `sub_delivery_method`, `provider_name`, `venue`, `instructor`, `duration_seconds`, `learning_hours`, `start_date`, `finish_date`, `start_time`, `end_time`, `requirement`.
- **Unstored Columns: 2 Columns**
  - `progress_status`: Virtual/calculated status ('Completed', 'In Progress') not stored as a separate column.
  - `month`: Administrative HR reporting month period (training date is determined by `start_date`).

### Database Comparison Rules:
1. **Unstored Column Handling:** When checking an incoming row against database records, unstored columns (`progress_status`, `month`) cannot be verified against the DB. They are isolated in `unverified_fields` metadata and do not block database duplicate detection.
2. **Scenario 48 Enforcement (Database Null Incomplete Fields):**
   ```php
   if ($normA !== '' && $normB === '') {
       $diffFields[$col] = [
           'val_a'  => $normA,
           'val_b'  => $normB,
           'reason' => 'database_null_unverified',
       ];
   }
   ```
   If a legacy database record has a `NULL` or empty value on any stored comparison column (e.g. `grade`, `bond_duration`, `job_position`), but the import file has a populated value, the system **never assumes a match**. It flags the field as `database_null_unverified`, and the incoming row is **not** considered a duplicate.

---

## 5. Normalization Specification

Every field is normalized into canonical format before comparison:

1. **Text Normalization:**
   - Multiple whitespace collapsed into single space: `preg_replace('/\s+/', ' ', $str)`
   - Trimmed and lowercased: `mb_strtolower($str, 'UTF-8')`
   - Empty representations (`""`, `null`, `"-"`, `"null"`) normalized to empty string `""`
2. **Numeric Normalization:**
   - Trailing zero normalization: `1`, `1.0`, `1.00`, `"1"` all normalize to canonical `"1"`
   - Floats normalized using canonical numeric string representation
3. **Date Normalization:**
   - Standardized to `YYYY-MM-DD` via `ExcelDateTimeParser`
   - Handles `DD/MM/YYYY`, `YYYY-MM-DD`, Excel numeric serial dates (e.g., `45879`)
4. **Time Normalization:**
   - Standardized to `HH:MM`
   - Handles `08:00:00`, `08:00`, Excel time serials (e.g., `0.333333`)
5. **Course Duration Normalization:**
   - Time formatted strings (`"08:00:00"`, `"8:00"`) and numeric seconds (`28800`) all convert to canonical seconds string `"28800"`
6. **Boolean Normalization:**
   - `'Yes'`, `'1'`, `'true'`, `'hadir'`, `'y'` $\rightarrow$ `'1'`
   - `'No'`, `'0'`, `'false'`, `'tidak'`, `'n'` $\rightarrow$ `'0'`
7. **Month Normalization:**
   - Month names (English and Indonesian: `'August'`, `'Agustus'`) and integers (`8`, `'08'`) convert to canonical string integer `"8"`. Falls back to `start_date` month if blank.
8. **Requirement Normalization:**
   - `'Mandatory'` $\rightarrow$ `'mandatory'`, `'Non Mandatory'` / `'non-mandatory'` $\rightarrow$ `'non mandatory'`

---

## 6. Official 48 Test Scenarios — Results Matrix

The test suite [`tests/Feature/PtkpFullRowDuplicateDetectionTest.php`](file:///C:/laragon/www/owl-3/tests/Feature/PtkpFullRowDuplicateDetectionTest.php) implements all 48 required scenarios:

| Scenario | Test Method | Column Tested / Description | Result |
|:---:|---|---|:---:|
| 1 | `test_01_all_42_columns_match_is_duplicate` | All 42 canonical columns match | **PASS (DUPLICATE)** |
| 2 | `test_02_only_no_differs_is_duplicate` | Only `No` differs (1 vs 205) | **PASS (DUPLICATE)** |
| 3 | `test_03_diff_employee_id_not_duplicate` | Employee ID differs ('131478' vs '117834') | **PASS (VALID)** |
| 4 | `test_04_diff_name_not_duplicate` | Employee Name differs | **PASS (VALID)** |
| 5 | `test_05_diff_grade_not_duplicate` | Grade differs ('4A' vs '3B') | **PASS (VALID)** |
| 6 | `test_06_diff_job_position_not_duplicate` | Job Position differs | **PASS (VALID)** |
| 7 | `test_07_diff_team_not_duplicate` | Team differs | **PASS (VALID)** |
| 8 | `test_08_diff_department_not_duplicate` | Department differs | **PASS (VALID)** |
| 9 | `test_09_diff_company_not_duplicate` | Company differs | **PASS (VALID)** |
| 10 | `test_10_diff_course_name_not_duplicate` | Course Title differs | **PASS (VALID)** |
| 11 | `test_11_diff_sub_course_name_not_duplicate` | Sub Course Title differs | **PASS (VALID)** |
| 12 | `test_12_diff_type_not_duplicate` | Type differs ('Online' vs 'In-House') | **PASS (VALID)** |
| 13 | `test_13_diff_category_not_duplicate` | Category differs | **PASS (VALID)** |
| 14 | `test_14_diff_level_not_duplicate` | Level differs ('Intermediate' vs 'Advanced') | **PASS (VALID)** |
| 15 | `test_15_diff_batch_not_duplicate` | Batch differs ('Batch 1' vs 'Batch 2') | **PASS (VALID)** |
| 16 | `test_16_diff_main_classification_not_duplicate` | Main Classification differs ('Job Functional' vs 'Leadership') | **PASS (VALID)** |
| 17 | `test_17_diff_sub_classification_not_duplicate` | Sub Classification differs ('General Skill' vs 'Special Skill') | **PASS (VALID)** |
| 18 | `test_18_diff_delivery_method_not_duplicate` | Delivery Method differs ('Online' vs 'Offline') | **PASS (VALID)** |
| 19 | `test_19_diff_sub_delivery_method_not_duplicate` | Sub Delivery Method differs | **PASS (VALID)** |
| 20 | `test_20_diff_provider_name_not_duplicate` | Provider Name differs | **PASS (VALID)** |
| 21 | `test_21_diff_venue_not_duplicate` | Venue differs | **PASS (VALID)** |
| 22 | `test_22_diff_instructor_not_duplicate` | Instructor differs | **PASS (VALID)** |
| 23 | `test_23_diff_course_duration_not_duplicate` | Course Duration differs (28800 vs 14400) | **PASS (VALID)** |
| 24 | `test_24_diff_learning_hours_not_duplicate` | Learning Hours differs (8.0 vs 4.0) | **PASS (VALID)** |
| 25 | `test_25_diff_progress_not_duplicate` | Progress differs (100 vs 50) | **PASS (VALID)** |
| 26 | `test_26_diff_progress_status_not_duplicate` | Progress Status differs ('Completed' vs 'In Progress') | **PASS (VALID)** |
| 27 | `test_27_diff_start_date_not_duplicate` | Start Date differs | **PASS (VALID)** |
| 28 | `test_28_diff_end_date_not_duplicate` | End Date differs | **PASS (VALID)** |
| 29 | `test_29_diff_start_time_not_duplicate` | Start Time differs ('08:00' vs '09:00') | **PASS (VALID)** |
| 30 | `test_30_diff_end_time_not_duplicate` | End Time differs ('17:00' vs '18:00') | **PASS (VALID)** |
| 31 | `test_31_diff_month_not_duplicate` | Record Month differs ('8' vs '9') | **PASS (VALID)** |
| 32 | `test_32_diff_requirement_not_duplicate` | Requirement differs ('Mandatory' vs 'Non Mandatory') | **PASS (VALID)** |
| 33 | `test_33_diff_attendance_not_duplicate` | Attendance differs ('1' vs '0') | **PASS (VALID)** |
| 34 | `test_34_diff_attendance_reason_not_duplicate` | Attendance Reason differs | **PASS (VALID)** |
| 35 | `test_35_diff_bond_status_not_duplicate` | Bond Status differs ('No' vs 'Yes') | **PASS (VALID)** |
| 36 | `test_36_diff_bond_duration_not_duplicate` | Bond Duration differs (0 vs 12) | **PASS (VALID)** |
| 37 | `test_37_diff_bond_end_date_not_duplicate` | Bond End Date differs | **PASS (VALID)** |
| 38 | `test_38_diff_bond_amount_not_duplicate` | Bond Amount differs (0 vs 5,000,000) | **PASS (VALID)** |
| 39 | `test_39_diff_l1_program_not_duplicate` | L1 Program differs ('Very Good' vs 'Excellent') | **PASS (VALID)** |
| 40 | `test_40_diff_assessment_score_not_duplicate` | Assessment Score differs (85 vs 95) | **PASS (VALID)** |
| 41 | `test_41_diff_l2_verbal_not_duplicate` | L2 Verbal differs ('Passed' vs 'Distinction') | **PASS (VALID)** |
| 42 | `test_42_diff_l3_numerical_not_duplicate` | L3 Numerical differs (90 vs 75) | **PASS (VALID)** |
| 43 | `test_43_diff_l3_verbal_not_duplicate` | L3 Verbal differs ('Competent' vs 'Needs Improvement') | **PASS (VALID)** |
| 44 | `test_44_diff_pic_not_duplicate` | PIC differs | **PASS (VALID)** |
| 45 | `test_45_date_number_format_diff_same_canonical_is_duplicate` | Date/number formats differ but canonical values match | **PASS (DUPLICATE)** |
| 46 | `test_46_exact_duplicate_in_database_is_duplicate` | Exact match against stored database columns | **PASS (DUPLICATE)** |
| 47 | `test_47_exact_duplicate_in_uploaded_excel_is_duplicate` | Exact in-file match across rows in Excel | **PASS (DUPLICATE)** |
| 48 | `test_48_legacy_database_incomplete_fields_not_claimed_full_row_match` | DB has NULL for stored column while import row has value | **PASS (NOT MATCH / VALID)** |
| Bonus | `test_excluded_columns_do_not_prevent_duplicate_match` | Excluded columns (`Provider`, `Sub Provider`, `Certificate`) differ | **PASS (DUPLICATE)** |

---

## 7. Modified Source Files

1. **[`app/Services/OfflineTraining/PtkpValidationService.php`](file:///C:/laragon/www/owl-3/app/Services/OfflineTraining/PtkpValidationService.php)**
   - Updated `getFullRowComparisonColumns()` to return the exact 42 canonical column list.
   - Added `getDatabaseStoredColumns()` (40 columns) and `getUnstoredDatabaseColumns()` (`progress_status`, `month`).
   - Updated `resolveColumnValue()` with full aliasing for duration, dates, positions, and company.
   - Updated `normalizeValueForComparison()` for duration (seconds), decimals, times, and booleans.
   - Updated `buildFullRowHash($data, $databaseColumnsOnly)` with optional 40-column database hash.
   - Updated `compareFullRowValues()` to strictly enforce Scenario 48 and record unverified fields.
   - Updated `normalizeRowData()` to preserve `course_duration` and `progress_status`.

2. **[`app/Services/OfflineTraining/PtkpImportService.php`](file:///C:/laragon/www/owl-3/app/Services/OfflineTraining/PtkpImportService.php)**
   - Updated `lookupExistingParticipantRecords()` to select all 40 database-stored fields and build O(1) hash indexed by 40 stored columns.
   - Updated `parseAndEvaluateWorkbook()` to perform O(1) 40-column hash check against database records before employee fallback.

3. **[`tests/Feature/PtkpFullRowDuplicateDetectionTest.php`](file:///C:/laragon/www/owl-3/tests/Feature/PtkpFullRowDuplicateDetectionTest.php)**
   - Full 49-scenario test suite created covering Scenarios 1 to 48 plus excluded-columns verification.

4. **[`tests/Feature/PtkpImportTest.php`](file:///C:/laragon/www/owl-3/tests/Feature/PtkpImportTest.php)**
   - Updated `test_duplicate_against_existing_database_record_is_skipped` to populate all stored matching attributes.

5. **[`tests/Feature/ImportLogIntegrationTest.php`](file:///C:/laragon/www/owl-3/tests/Feature/ImportLogIntegrationTest.php)**
   - Updated `test_ptkp_duplicate_and_warning_rows_logged_accurately` to populate all stored matching attributes.

---

## 8. Verification & Safety Guarantees

1. **Production Database Safety:**
   - All tests run strictly inside `DatabaseTransactions` rollback wrappers.
   - Zero production data modified or deleted.
   - Original Excel file `docs/PTKP_OWL_Import_Preview_5000 3.xlsx` remained completely untouched in read-only mode.
2. **Deterministic Duplicate Detection:**
   - 42 columns strictly compared in uploaded spreadsheets.
   - 40 stored columns strictly compared against database records.
   - Legacy incomplete database records are safeguarded from false-positive duplicate flagging (Scenario 48).
   - High performance O(1) SHA1 canonical hash index guarantees sub-second duplicate evaluation across thousands of rows.
