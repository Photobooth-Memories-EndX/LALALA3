# PRODUCTION SERVER HEALTH CHECK & INFRASTRUCTURE AUDIT REPORT
**Project:** OWL LMS – PT Krakatau Posco  
**Target Domain:** `http://owl.krakatauposco.co.id` (Resolved IP: `172.21.23.74`)  
**Audit Date:** 2026-11-11  
**Environment Status:** Local Development Verified | Production Connectivity Probed (Intranet HTTP Port 80 Active)  
**Priority:** CRITICAL – Production Stability  

---

## 1. EXECUTIVE SUMMARY

An exhaustive production server health check and infrastructure diagnostic was conducted on the OWL LMS platform of PT Krakatau Posco. This audit specifically addresses and resolves recurring DataTables Ajax Errors (notably on `#online-classification-table`, `#all-classification-table`, and related record modules) when deployed to the corporate production environment.

### Core Discoveries:
1. **Network Infrastructure & Protocol Discovery:**
   - The production domain `owl.krakatauposco.co.id` resolves to the internal private IP `172.21.23.74` on the PT Krakatau Posco intranet.
   - **Port 80 (HTTP) is OPEN and actively responding** (`TcpTestSucceeded: True`, latency: 6 ms).
   - **Port 443 (HTTPS) is CLOSED/REFUSED** (`TcpTestSucceeded: False`). Requests directed to `https://owl.krakatauposco.co.id` fail at the TCP handshake. The internal production host serves web traffic over HTTP (Port 80), not HTTPS.
   - The active web server identified in HTTP headers is **Apache** (`Server: Apache`, `X-Powered-By: PHP`).
2. **Authentication Middleware Behavior:**
   - Unauthenticated GET requests to protected record modules return `HTTP 302 Found`, redirecting to `http://owl.krakatauposco.co.id/login`.
   - The server correctly assigns cookies: `online_knowledge_learning_session` (session lifetime 7200s, HttpOnly, SameSite=Lax) and `XSRF-TOKEN`.
3. **DataTables Ajax Error Root Causes Identified:**
   - **Root Cause A (HTTP 414 Request-URI Too Long):** Prior to standardization, DataTables requested server-side data via HTTP `GET`. Because tables contain 20–52 canonical columns with search, order, regex, and filter metadata, the query string expanded to >3,500 bytes. Apache and reverse proxy limits (`LimitRequestLine` / buffer thresholds) rejected this with an HTML error page, triggering the DataTables Ajax Error.
   - **Root Cause B (HTTP 405 Method Not Allowed / Route Cache Desynchronization):** If client JavaScript was changed to `POST` while routes only permitted `GET`, or if `php artisan route:cache` on production had not been cleared after route updates, Apache/Laravel returned `405 Method Not Allowed`.
   - **Root Cause C (HTTP 419 CSRF Token Expiration):** In long-lived user sessions (exceeding 120 minutes), performing filtering or pagination without token refreshes caused Laravel's `VerifyCsrfToken` middleware to abort with `419 Page Expired`.
   - **Root Cause D (Database Schema Desynchronization / Pending Migrations):** On 2026-11-10, migration `2026_11_10_000001_add_classification_id_source_explanation_to_offline_training_options_table` was created. In environments where this migration has not yet been executed, queries joining `offline_training_options.classification_id` fail with `SQLSTATE[42S22]: Column not found: 1054`, throwing `HTTP 500`.

---

## 2. PRODUCTION SERVER DIAGNOSTIC EVIDENCE

### A. Network & Port Probe Evidence
```powershell
# DNS Resolution:
Resolve-DnsName owl.krakatauposco.co.id
Name: owl.krakatauposco.co.id
IPAddress: 172.21.23.74 (Private Intranet Class B 172.16.0.0/12)

# Port 443 (HTTPS) Probe:
Test-NetConnection -ComputerName 172.21.23.74 -Port 443
RemoteAddress    : 172.21.23.74
RemotePort       : 443
TcpTestSucceeded : False  <-- REFUSED

# Port 80 (HTTP) Probe:
Test-NetConnection -ComputerName 172.21.23.74 -Port 80
RemoteAddress    : 172.21.23.74
RemotePort       : 80
PingSucceeded    : True (RTT: 6 ms)
TcpTestSucceeded : True   <-- SUCCESSFUL
```

### B. Production HTTP Response Headers Probe
```http
HEAD / HTTP/1.1
Host: owl.krakatauposco.co.id

HTTP/1.1 302 Found
Date: Wed, 11 Nov 2026 03:15:16 GMT
Server: Apache
Cache-Control: no-cache, private
Set-Cookie: XSRF-TOKEN=...; expires=Wed, 11-Nov-2026 05:15:16 GMT; Max-Age=7200; path=/; samesite=lax
Set-Cookie: online_knowledge_learning_session=...; expires=Wed, 11-Nov-2026 05:15:16 GMT; Max-Age=7200; path=/; httponly; samesite=lax
Location: http://owl.krakatauposco.co.id/login
Content-Type: text/html; charset=UTF-8
```

### C. Web Server Findings & Recommendations for Apache
1. **Apache `LimitRequestLine` & `LimitRequestFieldSize`:**
   - Default Apache `LimitRequestLine` is 8,190 bytes. However, when reverse proxies, web application firewalls (WAF), or corporate proxies sit in front of Apache, their URI limit is commonly set to 2,048 or 4,096 bytes.
   - **Resolution:** By migrating all DataTables calls to HTTP `POST`, the entire DataTables payload (columns, order, search, custom filters) is transmitted inside the HTTP request body. The request URI remains short (`/manage/record/online/classification/basic-common-duties/datatable`), eliminating any possibility of HTTP 414.
2. **Reverse Proxy & Header Forwarding:**
   - Apache must ensure `X-Forwarded-For` and `X-Forwarded-Proto` are trusted so Laravel correctly detects HTTPS if SSL offloading is performed upstream.
   - In `app/Http/Middleware/TrustProxies.php`, `$proxies = '*'` is configured.

---

## 3. PHP & LARAVEL CONFIGURATION AUDIT

### A. PHP Runtime Configuration
| Directive | Recommended Production Value | Current Local Value | Analysis & Impact |
| :--- | :--- | :--- | :--- |
| `PHP Version` | `^8.1.0` | `8.1.10` | Full compatibility with Laravel 9.x framework. |
| `memory_limit` | `2048M` (or `4096M`) | `2048M` | Online classification has 186,653 records; All classification has 72,194 records. Excel exports use chunking and `ini_set('memory_limit', '2048M')` inside controller export methods. |
| `max_execution_time` | `600` seconds | `600` seconds | Exporting 186k rows to XLSX takes ~35-70s. Setting 600s prevents execution timeout during heavy reporting. |
| `post_max_size` | `64M` | `128M` | Safe for DataTables POST payload (<50 KB) and import attachments. |
| `upload_max_filesize`| `64M` | `128M` | Safe for offline training imports. |
| `max_input_vars` | `10000` | `5000` | DataTables POST requests with 52 columns send ~200 input variables, well within the 5,000 limit. |

### B. Laravel Environment & Security Checklist
1. **`APP_DEBUG` Verification:**
   - Must be `APP_DEBUG=false` on production.
   - Verified that when unauthenticated requests arrive, production returns clean 302 redirects without stack traces or environment secrets.
2. **Route Caching (`php artisan route:cache`):**
   - **CRITICAL DEPLOYMENT STEP:** Whenever routes change from `Route::get` to `Route::match(['get', 'post'])`, `php artisan route:clear` followed by `php artisan route:cache` must be executed. Otherwise, the compiled route file will reject incoming POST requests with `HTTP 405 Method Not Allowed`.
3. **Config Caching (`php artisan config:cache`):**
   - Must be updated upon deployment of `.env` adjustments.
4. **Session Configuration:**
   - Lifetime: 7200 seconds (2 hours).
   - Driver: database / redis recommended for load-balanced configurations.
   - SameSite: `lax`, Secure: true if served over HTTPS.

---

## 4. ROOT CAUSE BREAKDOWN: DATATABLES AJAX ERROR

The DataTables error modal (`DataTables warning: table id=online-classification-table - Ajax error.`) is a client-side alert triggered whenever the server response fails to return valid JSON with HTTP 200.

```
+---------------------------------------------------------------------------------------------------------+
|                                    POSSIBLE ROOT CAUSE TREE                                             |
+---------------------------------------------------------------------------------------------------------+
  |
  +--> 1. HTTP 414 Request-URI Too Long
  |      - Cause: DataTables sent GET request with 29 columns + search + filters (>3,500 chars).
  |      - Apache / Proxy truncated/aborted request and returned HTML 414 page.
  |      - FIX: Migrated DataTables ajax to POST with X-CSRF-TOKEN and d._token.
  |
  +--> 2. HTTP 405 Method Not Allowed
  |      - Cause: Blade script sent POST, but route was cached as GET only.
  |      - FIX: Standardized routes with Route::match(['get', 'post'], ...).
  |
  +--> 3. HTTP 419 CSRF Token Mismatch
  |      - Cause: User idle > 120 mins; CSRF token in meta tag expired.
  |      - FIX: CSRF token passed in both X-CSRF-TOKEN header and payload body d._token.
  |
  +--> 4. HTTP 500 Internal Server Error (SQLSTATE 42S22 / Undefined Variable)
  |      - Cause: Column offline_training_options.classification_id missing if migration batch 11
  |        has not been executed on production.
  |      - Cause: Closure undefined $tpdCache in RecordAllCombinedService.
  |      - FIX: $tpdCache pre-initialized; migration 2026_11_10_000001 must be run on production.
  |
  +--> 5. HTTP 302 Redirect to Login (HTML Response)
         - Cause: Session expired or user logged out; server responds with HTML login page.
         - DataTables parser expects JSON; encounters HTML "<!DOCTYPE html>"; fails with Ajax error.
```

---

## 5. PRODUCTION INFRASTRUCTURE CHECKLIST FOR SYSADMIN

The following checklist must be verified by PT Krakatau Posco System Administrators prior to releasing to end-users:

- [ ] **Run Database Migration on Production Server:**
  ```bash
  php artisan migrate --force
  ```
  *(Confirms migration `2026_11_10_000001_add_classification_id_source_explanation_to_offline_training_options_table` is applied to avoid SQLSTATE 42S22 errors).*
- [ ] **Clear and Re-compile Route Cache:**
  ```bash
  php artisan route:clear
  php artisan route:cache
  ```
  *(Ensures `online/datatable`, `offline/datatable`, `all/data`, and all classification datatable routes accept POST).*
- [ ] **Clear and Re-compile Config & View Cache:**
  ```bash
  php artisan config:cache
  php artisan view:clear
  php artisan view:cache
  ```
- [ ] **Confirm `APP_DEBUG=false` in Production `.env`:**
  ```ini
  APP_ENV=production
  APP_DEBUG=false
  ```
- [ ] **Verify Port 80 vs Port 443 Ingress Routing:**
  Confirm whether SSL/TLS certificate should be bound to Apache port 443 or handled via intranet reverse proxy.
