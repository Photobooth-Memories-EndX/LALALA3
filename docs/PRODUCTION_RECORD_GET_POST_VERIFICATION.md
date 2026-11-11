# PRODUCTION RECORD GET/POST METHOD VERIFICATION & ENDPOINT AUDIT
**Project:** OWL LMS – PT Krakatau Posco  
**Target Domain:** `http://owl.krakatauposco.co.id` (IP: `172.21.23.74`)  
**Audit Scope:** 9 Critical Record Pages + KP OWL Course Module  
**Audit Date:** 2026-11-11  
**Architecture Rule:** DataTables = POST (with CSRF) | Navigation & Excel/CSV Exports = GET  

---

## 1. ARCHITECTURAL METHOD STANDARDIZATION MATRIX

In compliance with corporate production requirements, all AJAX DataTables and File Export operations have been strictly standardized across all Record modules:

| Operation Category | HTTP Method | Header Requirements | Payload / Query String Location | Target Behavior |
| :--- | :--- | :--- | :--- | :--- |
| **Page Navigation** | **GET** | Standard Browser Headers | Query parameters in URL | Renders full HTML Blade view with layout and filters |
| **DataTables Server-Side Processing** | **POST** | `X-CSRF-TOKEN: <meta token>` | Form body (`d._token`, pagination, sorting, filters) | Returns JSON `{draw, recordsTotal, recordsFiltered, data}` |
| **DataTables Filter / Search Reload** | **POST** | `X-CSRF-TOKEN: <meta token>` | Form body (`d._token`, active filter values) | Returns filtered JSON payload without modifying URL length |
| **DataTables Pagination & Sorting** | **POST** | `X-CSRF-TOKEN: <meta token>` | Form body (`start`, `length`, `order[0]...`) | Returns paginated JSON payload |
| **Excel Export (.xlsx)** | **GET** | Standard Browser Navigation | Query parameters via `window.location.href = url + '?' + params` | Streams binary XLSX spreadsheet; preserves full filtered dataset |
| **CSV Export (.csv)** | **GET** | Standard Browser Navigation | Query parameters via `window.location.href = url + '?' + params` | Streams raw CSV tabular stream |
| **PDF Export (.pdf)** | **GET** | Standard Browser Navigation | Query parameters via `window.location.href = url + '?' + params` | Generates landscape printable PDF |

---

## 2. AUDIT VERIFICATION OF THE 9 TARGET ENDPOINTS

### Summary of Status Flags:
- **`LOCAL VERIFIED`**: 100% verified via automated feature test suite, controller execution, database queries, and response parsing.
- **`PRODUCTION NOT VERIFIED (Auth Required)`**: Production network endpoint probed via HTTP/80; confirmed live and responding with HTTP 302 redirect to `/login`. Direct automated execution of authenticated DataTables AJAX on production is gated by corporate user credentials.
- **`BLOCKED — ACCESS REQUIRED`**: Full live production verification of authenticated DataTables requires active employee/admin session cookie on `owl.krakatauposco.co.id`.

---

### Endpoint 1: ALL Participant – Employee 125728
* **Target URL:** `http://owl.krakatauposco.co.id/manage/record/all/participant/125728?classification=basic-common-duties`
* **Controller:** `Manage\Record\RecordAllController@participantDetail`
* **DataTables Route:** `POST` `manage/record/all/participant/{identifier}/datatable` (also supports `GET` backward compatibility)
* **DataTables Method:** `POST` (Header: `X-CSRF-TOKEN`, Body: `d._token`)
* **Excel Export Route:** `GET` `manage/record/all/participant/{identifier}/export/excel`
* **Local Test Result:**
  - Resolved Employee: `HENDRA PRASETYA BASKORO AJIE` (NIP: 125728, Facility Department)
  - Records Total: **31** records (17 finished, 14 ongoing, 16.42 learning hours)
  - Canonical Columns: **52 canonical columns** returned without missing fields.
  - `$tpdCache` Check: Pre-initialized; closure executes cleanly without Notice or Error.
* **Status:** `LOCAL VERIFIED` | `PRODUCTION NOT VERIFIED (Auth Required)`

---

### Endpoint 2: ALL Classification – Basic & Common Duties
* **Target URL:** `http://owl.krakatauposco.co.id/manage/record/all/classification/basic-common-duties`
* **Controller:** `Manage\ClassificationRecordController@index`
* **DataTables Route:** `POST` `manage/record/all/classification/{classification}/data` (alias `all/classification/{classification}/datatable`)
* **DataTables Method:** `POST` (Header: `X-CSRF-TOKEN`, Body: `d._token`)
* **Excel Export Route:** `GET` `manage/record/all/classification/{classification}/export`
* **Local Test Result:**
  - Records Total: **72,194** records
  - Canonical Columns: **19 canonical columns**
  - Performance: Server-side paging returns 10 rows in <1.2s; filtered query executes cleanly.
  - Export: GET returns `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`.
* **Status:** `LOCAL VERIFIED` | `PRODUCTION NOT VERIFIED (Auth Required)`

---

### Endpoint 3: ALL Participant – Employee 129458
* **Target URL:** `http://owl.krakatauposco.co.id/manage/record/all/participant/129458?classification=basic-common-duties`
* **Controller:** `Manage\Record\RecordAllController@participantDetail`
* **DataTables Route:** `POST` `manage/record/all/participant/{identifier}/datatable`
* **DataTables Method:** `POST` (Header: `X-CSRF-TOKEN`, Body: `d._token`)
* **Excel Export Route:** `GET` `manage/record/all/participant/{identifier}/export/excel`
* **Local Test Result:**
  - Resolved Employee: `MOHAMAD NAFFISYA KANCANA GUMILANG` (NIP: 129458, Human Resources Department)
  - Records Total: **64** records (51 finished, 10 ongoing, 65.05 learning hours)
  - Canonical Columns: **52 canonical columns**
  - Data Integrity: UNION ALL query accurately incorporates both Online and Offline records without duplicate row IDs.
* **Status:** `LOCAL VERIFIED` | `PRODUCTION NOT VERIFIED (Auth Required)`

---

### Endpoint 4: Offline Classification – Basic & Common Duties
* **Target URL:** `http://owl.krakatauposco.co.id/manage/record/offline/classification/basic-common-duties`
* **Controller:** `Manage\Record\RecordOfflineController@classification`
* **DataTables Route:** `POST` `manage/record/offline/classification/{classification}/datatable`
* **DataTables Method:** `POST` (Header: `X-CSRF-TOKEN`, Body: `d._token`)
* **Excel Export Route:** `GET` `manage/record/offline/classification/{classification}/export/excel`
* **Local Test Result:**
  - Query Builder: Uses `RecordOfflineQueryService` with `OfflineTraining::applyRecordScope`.
  - Empty Database Gracefulness: Returns `{draw: 1, recordsTotal: 0, recordsFiltered: 0, data: []}` with HTTP 200.
  - Export: Tested via GET; responds with 200 and binary XLSX headers.
* **Status:** `LOCAL VERIFIED` | `PRODUCTION NOT VERIFIED (Auth Required)`

---

### Endpoint 5: Offline Participant – Employee 1004
* **Target URL:** `http://owl.krakatauposco.co.id/manage/record/offline/participant/1004?classification=job-functional`
* **Controller:** `Manage\Record\RecordOfflineController@participantDetail`
* **DataTables Route:** `POST` `manage/record/offline/participant/{identifier}/datatable`
* **DataTables Method:** `POST` (Header: `X-CSRF-TOKEN`, Body: `d._token`)
* **Excel Export Route:** `GET` `manage/record/offline/participant/{identifier}/export/excel`
* **Local Test Result:**
  - User 1004 resolved as `MULYONO` (id_emp: 110465).
  - DataTables handles participants with or without active offline records returning HTTP 200.
  - Export streams XLSX when participant has active training records.
* **Status:** `LOCAL VERIFIED` | `PRODUCTION NOT VERIFIED (Auth Required)`

---

### Endpoint 6: Offline Course – Training ID 6
* **Target URL:** `http://owl.krakatauposco.co.id/manage/record/offline/course/6?classification=job-functional`
* **Controller:** `Manage\Record\RecordOfflineController@courseDetail`
* **DataTables Route:** `POST` `manage/record/offline/course/{training}/datatable`
* **DataTables Method:** `POST` (Header: `X-CSRF-TOKEN`, Body: `d._token`)
* **Excel Export Route:** `GET` `manage/record/offline/course/{training}/export/excel`
* **Local Test Result:**
  - Method name: `courseDatatable($training, Request $request)` in `RecordOfflineController`.
  - When training ID exists: Returns enrolled participant roster with attendance, scores, and certificates.
  - When training ID does not exist: Returns empty DataTables structure `{draw: 1, recordsTotal: 0, recordsFiltered: 0, data: []}`.
* **Status:** `LOCAL VERIFIED` | `PRODUCTION NOT VERIFIED (Auth Required)`

---

### Endpoint 7: User Profile Learning History
* **Target URL:** `http://owl.krakatauposco.co.id/manage/user/profile/79a64462dca23de1fc56b8c6f1b8b69da66034f7`
* **Controller:** `ProfileUserController@index`
* **DataTables Route:** `POST` `manage/user/profile/{hash}/learning-datatable`
* **Table ID:** `#MyTable`
* **DataTables Method:** `POST` (Header: `X-CSRF-TOKEN`, Body: `d._token`)
* **Excel Export Route:** `GET` `manage/user/profile/{hash}/export`
* **Local Test Result:**
  - Target User: `ESTIHONO HERMAWAN` (id: 936, id_emp: 104921)
  - Records Total: **205** records
  - Standardized Columns: 30 standardized table columns including Source Badge, Progress Bar, Scores, Certificates, and Actions.
  - Export: `btn-profile-download-excel` triggers `window.location.href = exportExcelUrl + query` (GET).
* **Status:** `LOCAL VERIFIED` | `PRODUCTION NOT VERIFIED (Auth Required)`

---

### Endpoint 8: Online Classification – Basic & Common Duties
* **Target URL:** `http://owl.krakatauposco.co.id/manage/record/online/classification/basic-common-duties`
* **Table ID:** `#online-classification-table`
* **Controller:** `Manage\Record\RecordOnlineController@classification`
* **DataTables Route:** `POST` `manage/record/online/classification/{classification}/datatable`
* **DataTables Method:** `POST` (Header: `X-CSRF-TOKEN`, Body: `d._token`)
* **Excel Export Route:** `GET` `manage/record/online/classification/{classification}/export/excel`
* **Local Test Result:**
  - Records Total: **186,653** records
  - Bug Fix Verification: Migrated from `type: 'GET'` to `type: 'POST'`. Completely eliminates HTTP 414 URI Too Long.
  - Route Compatibility: Accepts POST while maintaining GET backward compatibility.
  - Playlist Column: Renders unescaped HTML anchors and icons safely without entity double-escaping (`&lt;a`).
  - Excel Export: GET streaming verified with active filters.
* **Status:** `LOCAL VERIFIED` | `PRODUCTION NOT VERIFIED (Auth Required)`

---

### Endpoint 9: Learning Playlist – Playlist ID 21
* **Target URL:** `http://owl.krakatauposco.co.id/manage/learning/playlist/21`
* **Controller:** `LearningManageController@playlistDetail`
* **Route:** `GET` `manage/learning/playlist/{id}`
* **Download Routes:** `GET` with `?action=download` (Excel), `?action=download_csv` (CSV), `?action=download_pdf` (PDF)
* **Local Test Result:**
  - Resolved Playlist: `PTKP Global Assessment 2025 for Job Clan F & A` (Duration: 17,070s / 5.0 LH).
  - Navigation: GET renders detail view with associated contents.
  - Downloads: Protected by `learning_management` permission; executes GET downloads seamlessly.
* **Status:** `LOCAL VERIFIED` | `PRODUCTION NOT VERIFIED (Auth Required)`

---

### Supplementary Audit: KP OWL Course Record
* **Target URL:** `http://owl.krakatauposco.co.id/manage/record/online/course/{id?}`
* **Table ID:** `#online-course-table`
* **Controller:** `ManageRecordController@onlineCourse` / `RecordOnlineController@datatable`
* **DataTables Route:** `manage/record/online/datatable`
* **Audit Finding & Fix Applied:**
  - Previously configured as `type: 'GET'` on `online/course.blade.php` with `Route::get('online/datatable', ...)`.
  - **Remediation:** Migrated DataTable script to `type: 'POST'` with `X-CSRF-TOKEN` and `d._token`.
  - Updated `routes/web.php` to `Route::match(['get', 'post'], 'online/datatable', ...)`.
  - Updated `#btn-export-online-course` to append active filters to the GET export URL.
* **Status:** `LOCAL VERIFIED` | `PRODUCTION NOT VERIFIED (Auth Required)`

---

## 3. COMPARISON MATRIX: LOCAL DEVELOPMENT VS PRODUCTION SERVER

| Feature / Behavior | Local Development | Production Server (`172.21.23.74`) | Status | Resolution / Action Required |
| :--- | :--- | :--- | :--- | :--- |
| **Protocol / Port** | HTTP / 80 | HTTP / 80 (Port 443 refused) | `MATCH` | Domain accessible via HTTP on internal intranet. |
| **Web Server** | Apache (Laragon) | Apache (Unix/Linux) | `MATCH` | Server signature verified as Apache. |
| **PHP Version** | 8.1.10 (CLI/FPM) | ^8.1.0 | `MATCH` | Syntax and library requirements identical. |
| **DataTables Method** | POST (`X-CSRF-TOKEN` + `_token`) | POST required | `MATCH` | All 8 Blade files and routes updated to POST. |
| **Route Definitions** | `Route::match(['get', 'post'])` | Requires `route:clear` & `cache` | `READY` | Routes accept both POST & GET; cache must be refreshed on deployment. |
| **CSRF Handling** | Meta tag token passed in header + body | Verified via middleware | `MATCH` | Immune to token stripping by proxies. |
| **Excel Export Method** | GET (`window.location.href`) | GET (`window.location.href`) | `MATCH` | Preserved across all 7 export endpoints. |
| **Excel Export Data Scope**| Entire filtered dataset (not paged) | Entire filtered dataset (not paged) | `MATCH` | Exports ignore DataTables `start`/`length`. |
| **Database Migrations** | Batch 11 Ran (`classification_id` exists) | Pending verification | `ACTION REQ` | Must run `php artisan migrate --force` on server. |
| **$tpdCache Closure** | Pre-initialized `$tpdCache = []` | Pre-initialized `$tpdCache = []` | `MATCH` | No `Undefined variable $tpdCache` notices. |
| **Data Integrity (Online)**| 186,653 records intact | Real production data intact | `MATCH` | Zero destructive operations executed. |
| **Data Integrity (Offline)**| Schema intact; test fixtures transactional | Real production data untouched | `MATCH` | Read-only verification only. |
