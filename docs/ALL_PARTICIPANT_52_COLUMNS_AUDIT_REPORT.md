# ALL KP OWL PARTICIPANT DETAIL (52 COLUMNS) AUDIT REPORT

**Project:** OWL LMS – PT Krakatau Posco  
**Target Page:** `/manage/record/all/participant/129458?classification=basic-common-duties` & `/manage/record/all/participant/125728?classification=basic-common-duties`  
**Table ID:** `#all-participant-learning-table`  
**Audit Date:** November 10, 2026  
**Auditor:** Antigravity AI Engineering Assistant  
**Status:** **AUDIT COMPLETED — FULL PARITY ACHIEVED**

---

## 1. EXECUTIVE SUMMARY & OBJECTIVE

The ALL KP OWL module combines two disparate training ecosystems:
1. **KP OWL (Online Learning):** Standalone digital content (`content`, `content_users`) and curricula playlists (`trainingplan`, `trainingplandetail`, `playlist_content_users`).
2. **Non KP OWL (Offline Training):** Classroom and vendor-led trainings (`offline_trainings`, `offline_training_users`).

Previously, the Participant Detail page (`#all-participant-learning-table`) rendered a truncated ~30-column table structure, while the Excel export class (`AllParticipantExport`) only exported an outdated 45-column layout with inconsistent column order and omitted canonical attributes (such as Total Recognized Duration, Start/End Times, Requirement, Bond Duration, and Certificate details).

This audit established the official **52-column standard**, mapped every attribute across both online and offline database sources, and eliminated column shifting, missing attributes, and N+1 queries.

---

## 2. DATABASE SOURCE MAPPING AUDIT

### A. Online Learning Sources (KP OWL)
* **Tables:**
  * `content_users` (`cu`): Individual progress, watch duration, completion timestamp, AFK engagement duration.
  * `playlist_content_users` (`pcu`): Playlist-scoped progress and watch metrics.
  * `content` (`c`): Title, slug, format type (`Video`, `Document`), duration, author, mandatory requirement status.
  * `trainingplan` (`tp`): Curricula title, slug, total learning hours.
  * `trainingplandetail` (`tpd`): Relational bridge linking content to playlists.
  * `users` (`u`): Employee ID (`id_emp`), full name, job grade, position, department, company name.
  * `certificates` (`cert`): Joined with `certificate_settings` for credential availability and issuer name.

### B. Offline Training Sources (Non KP OWL)
* **Tables:**
  * `offline_trainings` (`ot`): Training course title, sub-course, level, batch, provider, venue, instructor, start/end dates & times, duration, requirement status.
  * `offline_training_users` (`otu`): Employee ID, name, grade, job position, department, attendance, scores (L1/L2/L3), bond duration/amount/end date, certificate issuer & dates, PIC.
  * `users` (`u`): Fallback employee demographic attributes.

---

## 3. CANONICAL 52-COLUMN SPECIFICATION & MAPPING MATRIX

The table below outlines the 52 canonical columns in exact sequence, detailing the authoritative data mapping for Online and Offline records:

| # | Column Name | Online Learning (KP OWL) Mapping | Offline Training (Non KP OWL) Mapping | Fallback / Missing Handling |
|---|---|---|---|---|
| 1 | **NO** | Incremental row index (1, 2, 3...) | Incremental row index (1, 2, 3...) | Sequential integer |
| 2 | **Source** | `'KP OWL'` | `'Non KP OWL'` | Canonical source badge / string |
| 3 | **Employee ID** | `u.id_emp` (regex stripped of NIP suffix) | `COALESCE(otu.employee_id, u.id_emp)` | Clean alphanumeric ID |
| 4 | **Employee Name** | `u.name` | `COALESCE(otu.name, u.name)` | Employee name or `'-'` |
| 5 | **Grade** | `u.jobgrade` | `COALESCE(otu.grade, u.jobgrade)` | Grade string or `'-'` |
| 6 | **Job Position** | `u.jobposition` | `COALESCE(otu.job_position, u.jobposition)` | Position string or `'-'` |
| 7 | **Team** | `u.team` | `COALESCE(otu.team, u.team)` | Team string or `'-'` |
| 8 | **Department** | `u.department` | `COALESCE(otu.department, u.department)` | Department string or `'-'` |
| 9 | **Company** | `u.company_name` | `COALESCE(otu.company, u.company_name)` | Company string or `'-'` |
| 10 | **Course Title** | `c.title` / `tp.title` | `COALESCE(ot.title, ot.course_name)` | Course name or `'-'` |
| 11 | **Sub Course Title** | `c.status` ('Mandatory'/'Non-Mandatory') | `ot.sub_course_name` | Sub-course string or `'-'` |
| 12 | **Type** | Content format (`Video`, `Document`, `Playlist`) | `'-'` (N/A for offline) | Type string or `'-'` |
| 13 | **Category** | `'All Content'` or `'Content Playlist'` | `'Non KP-OWL'` | Category badge or string |
| 14 | **Level** | `'-'` (N/A for online) | `ot.level` | Level string or `'-'` |
| 15 | **Batch** | `'-'` (N/A for online) | `ot.batch` | Batch number or `'-'` |
| 16 | **Main Classification** | `cl.classification_name` | `COALESCE(otu.main_classification, ot.main_classification)` | Master classification or `'Unclassified'` |
| 17 | **Sub Classification** | `'-'` (N/A for online) | `COALESCE(otu.sub_classification, ot.sub_classification)` | Sub-classification or `'-'` |
| 18 | **Delivery Method** | `'Online'` | `'Offline'` | Fixed canonical string |
| 19 | **Sub Delivery Method** | `'KP OWL'` | `ot.sub_delivery_method` (e.g. `E-learning`, `Classroom`) | Sub-method string or `'-'` |
| 20 | **Provider** | `'Internal'` | `ot.provider` | Provider string or `'-'` |
| 21 | **Sub Provider** | `'-'` (N/A for online) | `ot.sub_provider` | Sub-provider string or `'-'` |
| 22 | **Provider Name** | `'KP-OWL'` | `ot.provider_name` | Name string or `'-'` |
| 23 | **Venue** | `'Online LMS'` | `ot.venue` | Venue string or `'-'` |
| 24 | **Instructor** | `c.author` | `ot.instructor` | Instructor string or `'-'` |
| 25 | **Course Duration** | Formatted `HH:MM:SS` from `c.duration` | Formatted `HH:MM:SS` from `ot.duration` | Formatted duration or `'-'` |
| 26 | **Total Recognized Duration** | Canonical calculation via `LearningRecordRecognizedDurationService` | `'-'` (N/A for offline) | Formatted `HH:MM:SS` or `'-'` |
| 27 | **Learning Hours** | `cu.learning_hours` / `c.learning_hours` | `ot.learning_hours` / `otu.learning_hours` | Numeric float format |
| 28 | **% Progress** | `cu.progress` (0% - 100%) | `100%` if attended, else progress | Formatted percentage string |
| 29 | **Progress Status** | `'Finished'`, `'Ongoing'`, `'Not Started'` | `'Finished'`, `'Ongoing'`, `'Not Started'` | Status badge / text |
| 30 | **Start Date** | `DATE(cu.created_at)` | `DATE(ot.start_date)` | `YYYY-MM-DD` or `'-'` |
| 31 | **End Date** | `DATE(cu.completed_at)` | `DATE(ot.end_date)` / `DATE(ot.finish_date)` | `YYYY-MM-DD` or `'-'` |
| 32 | **Start Time** | `'-'` (Self-paced) | `ot.start_time` | `HH:MM:SS` or `'-'` |
| 33 | **End Time** | `'-'` (Self-paced) | `ot.end_time` | `HH:MM:SS` or `'-'` |
| 34 | **Record Month** | Month of completion / start date | Month of start date / record date | Full month name (e.g. `'September'`) |
| 35 | **Requirement** | `c.status` ('Mandatory'/'Non-Mandatory') | `ot.requirement` ('Mandatory'/'Non-Mandatory') | Requirement badge / text |
| 36 | **Attendance** | `'Yes'` (if progress >= 100%), else `'No'` | `'Yes'` (if attended=1/present), else `'No'` | Strictly `'Yes'` or `'No'` |
| 37 | **Attendance Reason** | `'-'` (N/A for online) | `otu.attendance_reason` | Reason string or `'-'` |
| 38 | **Bond Status** | `'No'` (N/A for online) | `otu.bond_status` | `'Yes'` / `'No'` |
| 39 | **Bond Duration** | `'-'` (N/A for online) | `otu.bond_duration` + ' Months' | Duration string or `'-'` |
| 40 | **Bond End Date** | `'-'` (N/A for online) | `DATE(otu.bond_end_date)` | `YYYY-MM-DD` or `'-'` |
| 41 | **Bond Amount** | `'-'` (N/A for online) | Formatted IDR `Rp #,##0` from `otu.bond_amount_idr` | IDR formatted or `'-'` |
| 42 | **Score (L1) - Course** | `cu.assessment_score` / `'-'` | `otu.l1_program` | Score numeric or `'-'` |
| 43 | **Score (L2)** | `cu.assessment_score` | `otu.l2_numerical` / `otu.assessment_score` | Score numeric or `'-'` |
| 44 | **L2 Status** | `'Passed'` (if >= 70) / `'Failed'` | `otu.l2_verbal` / (`Passed`/`Failed`) | Status string or `'-'` |
| 45 | **Score (L3)** | `'-'` (N/A for online) | `otu.l3_numerical` | Score numeric or `'-'` |
| 46 | **L3 Status** | `'-'` (N/A for online) | `otu.l3_verbal` | Status string or `'-'` |
| 47 | **Certificate Availability** | `'Available'` (if finished or certified) | `'Available'` (if certified) | `'Available'` or `'-'` |
| 48 | **Certificate Institution** | `'PT Krakatau Posco'` / issuer name | `otu.certificate` / issuer name | Institution string or `'-'` |
| 49 | **Certificate Issued Date** | Date of completion / issued date | `DATE(otu.certificate_issued_date)` | `YYYY-MM-DD` or `'-'` |
| 50 | **Certificate Expired Date** | `'-'` / expired date from certificates | `DATE(otu.certificate_expired_date)` | `YYYY-MM-DD` or `'-'` |
| 51 | **Person in Charge (PIC)** | `'-'` (N/A for online) | `otu.pic` | PIC name or `'-'` |
| 52 | **Total Engagement Time (LH)** | `cu.afk_duration_seconds` (`HH:MM:SS`) | `otu.training_hours * 3600` (`HH:MM:SS`) | Formatted `HH:MM:SS` |

*(Note: In the DataTables web UI, Column 53 is reserved for the Action dropdown menu, ensuring existing administrative features remain intact without disrupting the 52 standard data columns).*

---

## 4. FIELD AVAILABILITY AUDIT ACROSS SOURCES

To avoid synthetic or fabricated values, non-applicable attributes are strictly populated with standard placeholder `'-'` (or `'No'` for bond status on online courses), guaranteeing that column indices remain invariant:

1. **Online-Only Fields (Set to `'-'` for Offline Records):**
   * Total Recognized Duration (Column 26) — Rewatch capping logic is only applicable to asynchronous digital media.
2. **Offline-Only Fields (Set to `'-'` for Online Records):**
   * Level (Column 14)
   * Batch (Column 15)
   * Sub Classification (Column 17)
   * Sub Provider (Column 21)
   * Start Time (Column 32) & End Time (Column 33) — Online self-paced learning does not have scheduled classroom time slots.
   * Attendance Reason (Column 37)
   * Bond Status (Column 38), Duration (Column 39), End Date (Column 40), and Amount (Column 41)
   * Score L3 (Column 45) & L3 Status (Column 46)
   * Person in Charge (PIC) (Column 51)
