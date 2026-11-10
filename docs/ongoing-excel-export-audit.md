# Audit export Excel My Learning Ongoing

Tanggal: 13 Oktober 2026.

## Akar masalah dan acuan

Pada audit awal, export My Learning yang tersedia adalah `LearningRecordExportController` dan `ExportMyLearningRecord`: sumbernya content/playlist selesai, filter Record, serta 12 kolom hingga Certificate. Alur tersebut tidak dapat digunakan sebagai export dataset Ongoing. Filter Content dan Playlist Ongoing berada di controller dan service tersendiri.

File `MyLearningRecord_20261013_054913.xlsx` dan screenshot tidak tersedia dalam sesi/workspace yang diperiksa. Acuan visual yang dapat diverifikasi adalah implementasi Learning Record existing, khususnya warna header D9EAF7 dan nama kolom.

## File yang diperiksa

- `app/Http/Controllers/LearningOngoingController.php`, `LearningRecordExportController.php`.
- `app/Exports/ExportMyLearningRecord.php`, `LearningExport.php`, dan exporter Ongoing.
- `app/Services/Learning/OngoingContentService.php`, `OngoingPlaylistService.php`, `LearningRecordLearningHourService.php`, `LearningProgressResolver.php`, `ResolvedProgress.php`.
- `app/Services/CertificateEligibilityService.php`, `CertificateEligibilityResult.php`.
- Model `ContentUser`, `PlaylistContentUser`, `TrainingPlan`, `Assesment`, `AssesmentResult`, `CertificateSetting`.
- `routes/web.php`; partial, section, filter Content/Playlist; `resources/views/user/learning/partials/dashboard-script.blade.php`; `public/js/learning-record.js`.
- Test existing Ongoing, Record, export, filter, dan konfigurasi PHPUnit.

## File implementasi

- `app/Exports/ExportMyLearningOngoing.php`: exporter 13 kolom, pemetaan database, nilai Excel dan styling.
- `app/Http/Controllers/LearningOngoingController.php`: download memakai filter, sorting, dan service Ongoing existing sebelum pagination.
- `app/Services/Learning/OngoingPlaylistService.php`: ekstraksi `consumedSeconds()` dari kalkulasi existing agar card dan child export menggunakan urutan fallback yang sama.
- `routes/web.php`: endpoint terautentikasi `/my-learning/ongoing/export`.
- `resources/views/components/learning/ongoing-export.blade.php`: tombol export dengan parameter Ongoing aktif.
- `resources/views/components/learning/ongoing-content-filter.blade.php` dan `ongoing-playlist-filter.blade.php`: integrasi tombol.
- `tests/Feature/MyLearningOngoingExportTest.php` dan `OngoingExcelDownloadTest.php`: pengujian service/export serta download workbook melalui HTTP.

## Perilaku Content, Playlist, dan Attendance

Urutan A–M: No, Course, Type, Category, Classification, Duration, Learning Hour, Learning Progress, Start Date, Finish Date, Assessment Score, Certificate, Attendance.

Content menggunakan judul, type, classification, durasi course, timestamp belajar dan progress terkait user. Actual learning hour diambil langsung dari card service. Duration mengikuti durasi course yang tampil di kartu; snapshot durasi belajar hanya menjadi fallback jika durasi course tidak tersedia.

Playlist menggunakan progress resolver existing dan actual hours parent dari service, dengan child berdasarkan `playlist_content_users` untuk user dan playlist yang tepat. Child memiliki progress/duration/hour sendiri, indent Course dan outline level 1, tanpa nomor urut. Total hanya menjumlahkan parent. Content standalone tidak digunakan untuk menimpa progress playlist.

Baris belum selesai memperoleh Attendance `Absent` dan Finish Date kosong, termasuk bila terdapat timestamp completion lama. Child yang sudah selesai tetap mempertahankan data completion miliknya; Attendance dibiarkan kosong karena bukan representasi ongoing. Filter completed yang memang didukung halaman juga tetap mengikuti service existing.

Assessment Score menggunakan hasil final user yang relevan; sertifikat menggunakan `CertificateEligibilityService`. Export tidak menyimpan perubahan progress, attendance, atau completion. Tidak ada migration/perubahan schema.

## Format Excel dan scope

- Header bold, biru D9EAF7, wrap text dan tinggi/lebar yang memadai.
- Duration berupa nilai waktu Excel dengan format `[hh]:mm:ss`, mendukung total lebih dari 24 jam.
- Learning Hour numerik dengan tampilan dua desimal; Progress numerik berformat persen.
- Timestamp Excel berformat `yyyy-mm-dd hh:mm`; field belum tersedia kosong.
- Freeze A2; nomor rata tengah; border tipis; parent playlist berwarna abu-abu.
- AutoFilter untuk tabel datar; tidak diterapkan pada hierarki agar parent dan child tetap terkait.
- Teks course yang diawali `=` tetap string, bukan formula.
- Dataset seluruh hasil filter Ongoing, bukan halaman aktif. Filter `record_*`, source Record, dan identitas user dari query tidak menentukan dataset.

## Verifikasi

PHPUnit dijalankan menggunakan PHP 8.1.10 yang tersedia di Laragon. Test memakai `DatabaseTransactions`; tidak menjalankan migration. Workbook hasil HTTP dan `Excel::raw` dibaca ulang menggunakan PhpSpreadsheet, dengan binder pembaca normal.

Rangkaian utama mencakup `OngoingExcelDownloadTest`, `MyLearningOngoingExportTest`, `LearningOngoingContentTest`, `OngoingPlaylistServiceResolverTest`, `LearningRecordExportDurationAndLearningHourFormatTest`, `LearningRecordFilterCardExportTest`, `MyLearningRecordAllStandardizationTest`, serta `MyLearningRecordModesTest::test_export_downloads_work_for_all_sources`. Hasil mesin disimpan di `storage/logs/ongoing-final.xml` dan `storage/logs/ongoing-final.txt`.

Hasil akhir rangkaian utama: **61 test lulus, 557 assertion**, tanpa failure/error (32,154 detik). Pemeriksaan sintaks PHP exporter, controller dan playlist service juga lulus.

Pengujian mencakup header A–M, nilai numerik/persen/tanggal, Attendance, kosongnya Finish Date, score final, certificate status, hierarki, total tanpa penjumlahan ganda, filter, user isolation, pagination independence, dataset kosong, workbook round-trip, dan tidak berubahnya record belajar setelah export.

Rangkaian lebih luas menemukan kegagalan di area yang tidak diubah: dua assertion label `All KP OWL` pada Record Modes; fixture `content.status = OK` yang ditolak database dan assertion pada Unified Filter Audit; serta respons 403 pada test management Offline Global Export dan CSV/PDF Export Center. Kegagalan tersebut tidak diselesaikan dengan memperluas scope perubahan. Hasil ini bukan klaim seluruh suite lulus.

## Keterbatasan

Microsoft Excel desktop dan kecocokan visual dengan screenshot/file referensi belum diverifikasi langsung. Validitas file diuji melalui round-trip XLSX. PDF/CSV belum terverifikasi end-to-end karena test yang dicoba berhenti pada pemeriksaan akses 403. Perhitungan serta aturan filter existing sengaja dipertahankan; export menggunakan koleksi penuh sehingga kebutuhan memori bertambah sesuai jumlah hasil.

## Standardisasi tombol Download (pembaruan 13 Oktober 2026)

Tombol Export Excel yang sebelumnya terpisah dari toolbar dipindah ke ujung kanan action filter. Kini ia menggunakan gaya My Learning Record (`btn-outline-success`, `btn-learning-success`, ikon Tabler download, tinggi 40px, radius 8px), dengan menu Bootstrap untuk XLSX/PDF/CSV. Komponen `ongoing-export.blade.php` dipakai Content, Playlist, dan MOOC. Tombol MOOC berada di samping mode switch karena mode tersebut tidak memiliki toolbar Type/Status/Year/Month/Refresh.

Route `user.learning.ongoing.export` tetap menjadi endpoint tunggal. Parameter `format` memilih XLSX, PDF landscape, atau CSV UTF-8 dengan BOM; setiap format menggunakan ExportMyLearningOngoing yang sama dan mengonversi duration, persen, serta tanggal untuk tampilan CSV/PDF. CSV melindungi nilai teks yang dapat ditafsirkan sebagai formula. Mode MOOC memakai `OngoingContentService::moocCards()`.

Verifikasi pembaruan UI/format: **64 test lulus, 602 assertion**, termasuk route download, isi CSV/PDF, menu mode/filter, MOOC, dan Learning Record Online/Offline/All. Syntax PHP dan registrasi route juga diperiksa. Pemeriksaan UI dilakukan lewat render Blade dan assertion markup; browser visual/E2E mobile belum dijalankan.
