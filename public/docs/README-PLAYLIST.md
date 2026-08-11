# Playlist Learning, Assessment, dan Certificate

Dokumen ini menjelaskan requirement bisnis, alur data, pembagian tanggung jawab,
dan acceptance criteria untuk sistem Playlist OWL. Dokumen ini menjadi acuan
ketika mengubah sidebar Playlist, Content Assessment, progress, atau Generate
Certificate.

## 1. Ruang Lingkup

Satu Playlist terdiri dari beberapa Content. Setiap Content dapat memiliki nol,
satu, atau beberapa Content Assessment aktif. Playlist juga dapat mempunyai
Playlist Assessment.

Sistem harus memastikan bahwa:

1. seluruh Content ditampilkan sesuai urutan Playlist;
2. seluruh Content Assessment aktif dapat ditemukan tanpa membuka Content satu
   per satu;
3. assessment hanya dapat dikerjakan setelah prerequisite Content terpenuhi;
4. Assessment Content Progress menunjukkan kelulusan KKM, bukan sekadar submit;
5. certificate hanya dapat dibuat setelah seluruh requirement Playlist lulus;
6. backend tetap menjadi sumber kebenaran ketika URL diakses langsung.

> Catatan: nama model dan route menggunakan ejaan existing project,
> `Assesment`, agar dokumentasi sesuai dengan kode.

## 2. Definisi

| Istilah | Arti |
|---|---|
| Content aktif | Content yang sedang dibuka dari URL Playlist. |
| Assessment aktif | Assessment dengan `status != NOT`. |
| Content Assessment | Assessment bertipe `content` dan terkait melalui `content_id`. |
| Playlist Assessment | Assessment yang terkait dengan Playlist melalui `trainingplan_id`. |
| Final result | Hasil assessment dengan `final = 1`. |
| Lulus KKM | Terdapat final result dengan score yang memenuhi effective minimum score. |
| Assessment group | Seluruh assessment aktif untuk satu `content_id`, termasuk post-test/remedial. |
| Eligible | Seluruh requirement untuk tindakan terkait sudah terpenuhi. |

Effective minimum score ditentukan dengan urutan:

```text
CertificateSetting.minimum_score
        ↓ jika kosong
Assesment.passing_grade
        ↓ jika kosong
default sistem: 80
```

## 3. Business Requirement Playlist

### 3.1 Learning Progress

Semua Content dalam Playlist harus mencapai
`CertificateSetting.minimum_progress` sebelum Playlist certificate dinyatakan
eligible.

Learning Progress dan Assessment Progress adalah dua requirement yang berbeda.
Learning Progress 100% tidak otomatis berarti Content Assessment telah lulus.

### 3.2 Tombol Assessment Content

Sidebar melakukan loop seluruh item Playlist. Untuk setiap item:

- ambil assessment dari `assessmentsByContent[$list->content_id]`;
- jangan mengambil assessment menggunakan `$content->id` milik Content aktif;
- tampilkan tombol jika item tersebut memiliki Content Assessment aktif;
- jangan mensyaratkan `$isActive` untuk merender tombol;
- gunakan slug assessment milik item tersebut untuk membentuk route.

Contoh:

```text
Content 61 → Assessment 60 → /assesment/abc
Content 64 → Assessment 76 → /assesment/def
Content 67 → Assessment 82 → /assesment/ghi
```

Setiap tombol harus menggunakan slug milik assessment-nya sendiri. Seluruh
tombol tidak boleh mengarah ke assessment Content yang sedang aktif.

### 3.3 State Tombol Assessment

Tombol tetap terlihat ketika assessment ada, tetapi belum dapat dikerjakan.

```text
Content progress memenuhi prerequisite
        → link aktif dan mempunyai href

Content progress belum memenuhi prerequisite
        → button disabled atau elemen tanpa href
```

Elemen `<a>` yang disabled tidak boleh tetap mempunyai `href`. Class
`disabled` dan `aria-disabled` saja tidak cukup karena masih dapat dibuka
melalui Ctrl+Click, middle click, keyboard, atau salin URL.

State disabled harus:

- tidak dapat dinavigasi dengan mouse;
- tidak dapat dibuka di tab baru;
- tidak dapat dijalankan dengan keyboard;
- menggunakan `aria-disabled="true"`;
- mengikuti tampilan disabled Bootstrap.

### 3.4 Multiple Assessment

Beberapa Content memiliki lebih dari satu assessment aktif. Assessment tersebut
diperlakukan sebagai satu group berdasarkan `content_id`.

Aturan existing `passAssessmentGate()` berlaku:

- post-test dan remedial dapat disimpan sebagai baris assessment terpisah;
- satu final result yang memenuhi effective minimum score membuat group lulus;
- jika belum ada yang lulus, group dinyatakan belum lulus;
- hasil terbaik digunakan untuk menjelaskan kegagalan.

Assessment yang dipakai sebagai link sidebar dipilih secara deterministik
berdasarkan ID terkecil. Pemilihan link tidak boleh memakai `first()` tanpa
ordering eksplisit.

## 4. Assessment Content Progress

Assessment Content Progress adalah persentase **assessment group yang telah
lulus KKM**.

Rumus:

```text
completed = jumlah Content Assessment group yang lulus passAssessmentGate()
total     = jumlah Content yang mempunyai Assessment aktif

percentage = round((completed / total) × 100)
```

Contoh:

```text
Content 1 → PASS
Content 2 → PASS
Content 3 → FAIL
Content 4 → PASS

completed = 3
total     = 4
progress  = 75%
```

Final submit yang nilainya belum memenuhi KKM tidak dihitung sebagai completed.
Dengan demikian, label yang paling tepat secara bisnis adalah
`Content Assessment Pass Rate` atau `Assessment Passed`.

## 5. Certificate Eligibility

Playlist certificate hanya eligible apabila semua kondisi berikut terpenuhi:

1. setiap Content mencapai minimum learning progress;
2. setiap Content Assessment group aktif lulus KKM;
3. Playlist Assessment lulus KKM apabila assessment tersebut tersedia;
4. `CertificateSetting` aktif.

Alurnya:

```text
CertificateEligibilityService::check()
        ↓
evaluatePlaylist()
        ├── validasi seluruh learning progress
        ├── passAssessmentGate() per Content Assessment group
        └── passAssessmentGate() untuk Playlist Assessment
        ↓
CertificateEligibilityResult
        ├── eligible
        ├── reason
        ├── assessmentResult
        └── minimumScore
```

`passAssessmentGate()` adalah single source of truth untuk keputusan kelulusan
assessment dalam workflow Playlist. `contentAssessmentProgress()` dan
`evaluatePlaylist()` tidak boleh mempunyai algoritma kelulusan masing-masing.

### State Generate Certificate

Generate Certificate harus selalu terlihat.

| Kondisi | State |
|---|---|
| Belum memenuhi salah satu requirement | Terlihat, disabled, tidak dapat diklik. |
| Seluruh requirement terpenuhi | Terlihat dan aktif. |

UI tidak boleh menentukan eligibility dengan aturan tambahan seperti:

```php
if ($percentage === 100) {
    // eligible
}
```

UI hanya membaca `CertificateEligibilityResult` yang dibuat oleh Service.

Endpoint generate juga wajib memanggil
`CertificateEligibilityService::check()`. Dengan begitu, salin URL atau request
POST manual tidak dapat melewati eligibility.

## 6. Authorization Content Assessment

Disabled state pada UI bukan mekanisme keamanan. Seluruh endpoint assessment
harus menggunakan `AssessmentAccessService`:

- `view`;
- `store`;
- `autosave`;
- `result`;
- endpoint start/submit/finish lain jika ditambahkan di masa depan.

Untuk Content Assessment, akses diperbolehkan setelah progress Content memenuhi
`ContentUser::ASSESSMENT_REQUIRED_PROGRESS`.

Guard backend juga mempertahankan validasi existing:

- assignment user;
- status assessment;
- periode assessment;
- prerequisite Content;
- bypass administrator existing.

Contoh:

```text
Progress 80%
        ↓
GET /assesment/{slug} atau POST langsung
        ↓
AssessmentAccessService
        ↓
403 / response penolakan existing
```

Backend harus tetap menolak request meskipun user mengetahui slug assessment.

## 7. Data Flow

### 7.1 Playlist dan Sidebar

```text
training_plan_details
        ↓
TrainingPlanDetail::with('content')
        ↓
PlaylistUserController
        ├── seluruh Content Playlist
        ├── CertificateSetting existing
        └── contentAssessmentProgress(contentIds, user, setting)
                    ↓
        CertificateEligibilityService
        ├── query massal Assessment aktif
        ├── query massal final result user
        ├── assessmentsByContent
        └── pass percentage
                    ↓
        Blade sidebar
        └── lookup menggunakan $list->content_id
                    ↓
        HTML per Content
```

### 7.2 Generate Certificate

```text
Playlist page / POST generate
        ↓
CertificateEligibilityService::check()
        ↓
evaluatePlaylist()
        ↓
CertificateEligibilityResult
        ├── UI: enabled/disabled
        └── CertificateGenerateService: allow/403
```

UI dan endpoint generate harus menggunakan hasil Service yang sama.

## 8. Pembagian Tanggung Jawab

| Layer/File | Tanggung jawab |
|---|---|
| `PlaylistUserController` | Mengambil data request dan menjadi wiring antara setting, Service, dan View. Tidak menghitung eligibility. |
| `CertificateEligibilityService` | Menghitung learning requirement, kelulusan assessment, progress assessment, dan certificate eligibility. |
| `AssessmentAccessService` | Menentukan apakah user boleh mengakses endpoint assessment. |
| `CertificateGenerateService` | Membuat certificate setelah hasil `check()` menyatakan eligible. |
| `view-playlist-sidebar.blade.php` | Merender assessment milik setiap item dari mapping existing. Tidak melakukan query. |
| `view-navigation.blade.php` | Merender state Generate Certificate dari eligibility existing. |
| `ContentUser` | Menyimpan threshold dan helper prerequisite progress Content Assessment. |

Controller dan Blade tidak boleh:

- menghitung ulang score;
- menentukan KKM;
- menentukan kelulusan assessment;
- melakukan query assessment;
- membuat aturan eligibility tambahan.

## 9. Query dan Performance Contract

`contentAssessmentProgress()` menggunakan dua query massal:

1. mengambil seluruh Assessment aktif untuk semua `content_id`;
2. mengambil seluruh final result user untuk assessment tersebut.

Ketentuan performa:

- tidak ada query di Blade;
- tidak ada query di loop per Content;
- tidak ada query assessment tunggal untuk Content aktif;
- tidak ada N+1;
- query `CertificateSetting` existing boleh dipindahkan untuk wiring, tetapi
  tidak diduplikasi;
- perubahan scope query existing diperbolehkan selama jumlah query tidak
  bertambah.

## 10. Acceptance Criteria

### Sidebar

- [ ] Semua Content yang memiliki assessment aktif menampilkan tombol.
- [ ] Content tanpa assessment tidak menampilkan tombol.
- [ ] Tombol tidak bergantung pada Content aktif.
- [ ] Setiap route menggunakan slug assessment milik Content tersebut.
- [ ] Assessment nonaktif (`status = NOT`) tidak dipetakan.
- [ ] Disabled control tidak mempunyai jalur navigasi aktif.

### Assessment Access

- [ ] GET dengan progress 1% atau 80% ditolak.
- [ ] POST store/autosave dengan progress belum cukup ditolak.
- [ ] GET dan POST dengan progress memenuhi prerequisite berjalan normal.
- [ ] Salin URL tidak dapat melewati authorization backend.

### Progress dan Certificate

- [ ] Final submit dengan nilai di bawah KKM belum dianggap completed.
- [ ] `8 PASS / 10 assessment group` menghasilkan 80%.
- [ ] Jika satu Content Assessment group belum lulus, certificate tidak eligible.
- [ ] Playlist Assessment wajib lulus jika tersedia.
- [ ] Generate Certificate tetap terlihat saat tidak eligible.
- [ ] Request generate manual ditolak ketika tidak eligible.
- [ ] Setelah seluruh requirement lulus, UI dan endpoint generate sama-sama aktif.

### Regression

- [ ] Scoring assessment tidak berubah.
- [ ] Attempt dan remedial tidak berubah.
- [ ] Route tidak berubah.
- [ ] Tidak ada migration/database schema baru.
- [ ] Tidak ada query baru atau N+1.

## 11. Contoh Skenario End-to-End

```text
Playlist memiliki:
- 10 Content, seluruh Learning Progress 100%
- 10 Content Assessment group
- 8 group PASS
- 2 group FAIL

Hasil:
- Learning Progress: 100%
- Assessment Content: 80%
- Generate Certificate: terlihat tetapi disabled
- POST Generate Certificate: ditolak backend
```

Setelah dua assessment terakhir lulus:

```text
- Learning Progress: 100%
- Assessment Content: 100%
- Generate Certificate: aktif
- POST Generate Certificate: berhasil
```

## 12. File Utama

- `app/Services/CertificateEligibilityService.php`
- `app/Services/CertificateGenerateService.php`
- `app/Services/AssessmentAccessService.php`
- `app/Http/Controllers/PlaylistUserController.php`
- `app/Http/Controllers/AssesmentUserController.php`
- `app/Models/ContentUser.php`
- `resources/views/user/playlist/components/view-playlist-sidebar.blade.php`
- `resources/views/user/playlist/components/view-navigation.blade.php`
- `public/js/learning-progress.js`

Setiap perubahan pada file-file tersebut harus mempertahankan contract dan
acceptance criteria dalam dokumen ini.
