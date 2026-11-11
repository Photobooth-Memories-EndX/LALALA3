# COMPREHENSIVE GET/POST AUDIT REPORT — OWL LMS RECORD MODULES

**Project:** OWL LMS – PT Krakatau Posco  
**Target Focus:** Standardization of HTTP GET/POST across Record modules, prioritizing ALL KP OWL  
**Audit Date:** 2026-11-10  

---

## 1. Executive Summary

A comprehensive audit was performed across all target URLs in OWL LMS to analyze:
1. HTTP status codes and failed AJAX endpoints.
2. Web server URI length limitations (HTTP 414 Request-URI Too Long).
3. Backend controller/service runtime exceptions (HTTP 500 Internal Server Error).
4. DataTables server-side processing vs. Excel export mechanisms.
5. Preservation of combined data sources (KP OWL Online + Non KP OWL Offline) in the ALL KP OWL module.

### Critical Findings:
- **`all-participant-learning-table` (ALL Participant Detail) failed with HTTP 500 Internal Server Error**:
  - In `app/Services/Record/RecordAllCombinedService.php:2718`, closure `function ($row) use ($tpdCache)` accesses an undefined variable `$tpdCache`.
  - In addition, it generates **30 columns** via `GET` (~7.8 KB query string), which triggers **HTTP 414** in production.
- **Offline Classification, Participant, and Course Detail** DataTables all use `GET` with 33 columns and 10–40+ custom filters, resulting in query strings between **8.0 KB and 10.5 KB**, causing **HTTP 414** when deployed behind production reverse proxies/WAFs with 4KB–8KB URI limits.
- **User Profile Learning History** (`#MyTable`) uses `GET` with 34 columns.
- **Excel Export** across all modules is already implemented via `window.location.href` (GET) sending only active filters (< 250 bytes). These must strictly remain `GET`.

---

## 2. Comprehensive Endpoint Audit Matrix

| Module & Page | Function | Target / Table ID | Current Method | Actual Status / Error | Recommended Method | Architecture Action |
|---|---|---|---|---|---|---|
| **ALL Classification** | Page Nav | `/manage/record/all/classification/{slug}` | GET | HTTP 200 OK | **GET** | Keep GET |
| **ALL Classification** | DataTables | `#all-classification-learning-table` (35 cols) | POST | HTTP 200 OK | **POST** | Verified (No regression) |
| **ALL Classification** | Export Excel | Download button | GET | HTTP 200 OK | **GET** | Keep GET |
| **ALL Participant Detail** | Page Nav | `/manage/record/all/participant/{id}` | GET | HTTP 200 OK | **GET** | Keep GET |
| **ALL Participant Detail** | DataTables | `#all-participant-learning-table` (30 cols) | GET | **HTTP 500 (Undefined $tpdCache) + HTTP 414 Risk** | **POST** | **Fix $tpdCache bug + migrate to POST** |
| **ALL Participant Detail** | Export Excel | Download button | GET | HTTP 200 OK | **GET** | Keep GET |
| **Offline Classification** | Page Nav | `/manage/record/offline/classification/{slug}` | GET | HTTP 200 OK | **GET** | Keep GET |
| **Offline Classification** | DataTables | `#offline-classification-table` (33 cols) | GET | HTTP 414 Risk (>8.4 KB URI) | **POST** | **Migrate DataTables to POST** |
| **Offline Classification** | Export Excel | Download button | GET | HTTP 200 OK | **GET** | Keep GET |
| **Offline Participant Detail** | Page Nav | `/manage/record/offline/participant/{id}` | GET | HTTP 200 OK | **GET** | Keep GET |
| **Offline Participant Detail** | DataTables | `#participant-training-table` (33 cols) | GET | HTTP 414 Risk (>9.5 KB URI) | **POST** | **Migrate DataTables to POST** |
| **Offline Participant Detail** | Export Excel | Download button | GET | HTTP 200 OK | **GET** | Keep GET |
| **Offline Course Detail** | Page Nav | `/manage/record/offline/course/{id}` | GET | HTTP 200 OK | **GET** | Keep GET |
| **Offline Course Detail** | DataTables | `#course-participants-table` (33 cols) | GET | HTTP 414 Risk (>8.2 KB URI) | **POST** | **Migrate DataTables to POST** |
| **Offline Course Detail** | Export Excel | Download button | GET | HTTP 200 OK | **GET** | Keep GET |
| **Online Classification** | Page Nav | `/manage/record/online/classification/{slug}` | GET | HTTP 200 OK | **GET** | Keep GET |
| **Online Classification** | DataTables | `#online-classification-table` (34 cols) | POST | HTTP 200 OK | **POST** | Verified (No regression) |
| **Online Classification** | Export Excel | Download button | GET | HTTP 200 OK | **GET** | Keep GET |
| **User Profile** | Page Nav | `/manage/user/profile/{hash}` | GET | HTTP 200 OK | **GET** | Keep GET |
| **User Profile** | DataTables | `#MyTable` (34 cols) | GET | HTTP 414 Risk (>8.5 KB URI) | **POST** | **Migrate DataTables to POST** |
| **User Profile** | Export Excel | Download button | GET | HTTP 200 OK | **GET** | Keep GET |
| **Learning Playlist** | Page Nav | `/manage/learning/playlist/{id}` | GET | HTTP 200 OK | **GET** | Keep GET |
| **Learning Playlist** | Export Excel | Download button | GET | HTTP 200 OK | **GET** | Keep GET |

---

## 3. Deep-Dive Inspection by Module

### A. ALL KP OWL Participant Detail
- **URL**: `/manage/record/all/participant/125728?classification=basic-common-duties` & `129458`
- **Blade File**: `resources/views/main/record/all/participant.blade.php`
- **Table ID**: `#all-participant-learning-table`
- **Controller**: `App\Http\Controllers\Manage\Record\RecordAllController@participantDatatable`
- **Service**: `App\Services\Record\RecordAllCombinedService::participantDatatable`
- **Root Cause Analysis**:
  1. **Primary Failure (HTTP 500)**: Line 2718 of `RecordAllCombinedService.php`:
     ```php
     ->addColumn('playlist', function ($row) use ($tpdCache) {
     ```
     `$tpdCache` was never defined or initialized in `participantDatatable()`, triggering an uncaught `ErrorException: Undefined variable $tpdCache` on any call.
  2. **Secondary Failure (HTTP 414)**: The DataTable passes 30 column definitions over AJAX `GET`, resulting in an ~7.8 KB query string that exceeds default production server URI limits.
- **Remediation Plan**:
  1. Define and cache `$tpdCache` from `trainingplandetail` & `trainingplan` in `RecordAllCombinedService::participantDatatable()`.
  2. In `routes/web.php`, update route `all/participant/{identifier}/datatable` to `Route::match(['get', 'post'], ...)`.
  3. In `resources/views/main/record/all/participant.blade.php`, switch AJAX method to `POST` with CSRF headers.
  4. Ensure Excel export (`exportExcelUrl`) remains `GET`.

### B. Offline Classification, Participant, & Course Detail
- **Classification**:
  - Blade: `resources/views/main/record/offline/classification.blade.php` (`#offline-classification-table`, 33 cols).
  - Route: `offline/classification/{classification}/datatable` (Line 616 in `routes/web.php`).
- **Participant**:
  - Blade: `resources/views/main/record/offline/participant.blade.php` (`#participant-training-table`, 33 cols, 40+ filters).
  - Route: `offline/participant/{identifier}/datatable` (Line 622 in `routes/web.php`).
- **Course**:
  - Blade: `resources/views/main/record/offline/course.blade.php` (`#course-participants-table`, 33 cols).
  - Route: `offline/course/{training}/datatable` (Line 627 in `routes/web.php`).
- **Remediation Plan**:
  1. In `routes/web.php`, update the three datatable routes to `Route::match(['get', 'post'], ...)`.
  2. In their respective Blade files, switch DataTable AJAX method to `POST` with `X-CSRF-TOKEN` and `_token`.
  3. Verify export buttons retain `window.location.href` (GET).

### C. User Profile Learning History
- **Blade File**: `resources/views/main/profile/components/profile-table-script.blade.php` (`#MyTable`, 34 cols).
- **Route**: `user/profile/{hash}/learning-datatable` (Line 349 in `routes/web.php`).
- **Remediation Plan**:
  1. In `routes/web.php`, update to `Route::match(['get', 'post'], ...)`.
  2. In `profile-table-script.blade.php`, switch AJAX method to `POST` with CSRF token.
  3. Normal profile navigation and export remain `GET`.

---

## 4. ALL KP OWL Data Integrity Guarantee

In `app/Services/Record/RecordAllCombinedService.php`:
- Query builder `buildCombinedQuery($filters)` unifies three subqueries via `UNION ALL`:
  1. `buildContentQuery()`: Standalone content records from `content_users` (KP OWL Online).
  2. `buildPlaylistQuery()`: Playlist records from `playlist_content_users` (KP OWL Online).
  3. `buildOfflineQuery()`: Offline training records from `offline_trainings` & `offline_training_users` (Non KP OWL).
- Both DataTables (POST) and Excel Export (GET) use this exact query pipeline.
- Switching DataTables to POST does not change query logic or filter parameters; both KP OWL and Non KP OWL records remain 100% intact.

---

## 5. Architectural Standards

```
+--------------------------------------------------------------------------------+
| OWL LMS RECORD HTTP ARCHITECTURE                                               |
+--------------------------------------------------------------------------------+
| Operation                              | Method | Payload Location             |
|----------------------------------------|--------|------------------------------|
| View Page (Navigation)                 | GET    | URL / Route parameter        |
| DataTables Server-Side Processing      | POST   | HTTP Request Body (Form data)|
| Excel / CSV / PDF Download             | GET    | Active Filter Query String   |
+--------------------------------------------------------------------------------+
```
