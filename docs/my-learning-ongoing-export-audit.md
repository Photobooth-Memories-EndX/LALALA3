# Audit export Excel My Learning Ongoing

Tanggal: 13 Oktober 2026.

## Temuan

Pada kode yang diaudit, Ongoing Content/Playlist belum memiliki route download dan tombol Excel sendiri. Route `user.learning.record.export` memakai export Learning Record yang mengambil pembelajaran selesai. Export online tersebut memiliki 12 kolom sampai Certificate. Menggunakannya untuk Ongoing tidak memberikan dataset atau Attendance yang diminta.

Service kartu Ongoing sudah menyediakan scope pengguna, filter, progress, dan actual learning hour. Perhitungan ini berbeda tujuan dari service total Learning Record yang menghitung pembelajaran selesai, sehingga export baru memakai hasil service kartu Ongoing.

## File yang diperiksa

- `routes/web.php`; controller `LearningController`, `LearningOngoingController`, `LearningRecordExportController`.
- Service `OngoingContentService`, `OngoingPlaylistService`, `LearningProgressResolver`, `ResolvedProgress`, `LearningHourService`, `LearningRecordLearningHourService`, `CertificateEligibilityService`.
- Export `ExportMyLearningRecord`, `ExportMyOfflineLearningRecord`, `ExportMyAllLearningRecord`.
- Model `Content`, `ContentUser`, `PlaylistContentUser`, `TrainingPlan`, `Assesment`, `AssesmentResult`; pemakaian relasi `TrainingPlanDetail`.
- Blade filter dan section Ongoing Content/Playlist, serta `user/learning/partials/dashboard-script.blade.php` yang meneruskan filter URL saat memuat ulang partial.
- Test Ongoing, resolver, filter/export Learning Record, mode Record, standardisasi All, dan download Excel/PDF/CSV existing.

## File implementasi yang berubah

| File | Perubahan |
| --- | --- |
| `app/Exports/ExportMyLearningOngoing.php` | Export khusus Ongoing, mapping 13 kolom, score/certificate, hierarchy, total, format XLSX |
| `app/Http/Controllers/LearningOngoingController.php` | Download menggunakan filter/sort dan service kartu existing, tanpa pagination |
| `app/Services/Learning/OngoingPlaylistService.php` | Mengekstrak urutan perhitungan konsumsi existing ke `consumedSeconds()`, dipakai parent dan child |
| `routes/web.php` | Route terautentikasi `/my-learning/ongoing/export` |
| `resources/views/components/learning/ongoing-content-filter.blade.php` | Tombol export Content |
| `resources/views/components/learning/ongoing-playlist-filter.blade.php` | Tombol export Playlist |
| `resources/views/components/learning/ongoing-export.blade.php` | Komponen tautan export dengan mode/filter Ongoing; mendukung alias pencarian `q` |
| `tests/Feature/MyLearningOngoingExportTest.php` | Pengujian export dan integrasi route/filter |

## Perilaku hasil

Urutan A–M: No, Course, Type, Category, Classification, Duration, Learning Hour, Learning Progress, Start Date, Finish Date, Assessment Score, Certificate, Attendance.

Content menggunakan nama, tipe, classification, dan durasi content; learning hour dan progress mengikuti kartu Ongoing. Start Date berasal dari catatan pengguna. Score final yang relevan tetap dapat diekspor tanpa certificate setting; eligibility sertifikat memakai service existing.

Playlist memakai agregat service existing. Child mengikuti urutan detail playlist, progress resolver dan catatan `playlist_content_users` milik pengguna/playlist tersebut. Riwayat standalone tidak menggantikan progress playlist. Nomor hanya diberikan pada parent; Course child diindentasi. TOTAL menjumlahkan baris utama saja, sehingga parent dan child tidak dihitung bersamaan.

Baris belum selesai memiliki Attendance `Absent` dan Finish Date kosong. Child yang sudah selesai, atau hasil filter Completed existing, mempertahankan tanggal selesai dan tidak diberi Attendance Absent; Attendance dibiarkan kosong karena tidak ada sumber attendance aktual online yang ditambahkan. Tidak ada penulisan attendance, progress, completion, atau perubahan schema/migration.

Header bold memakai warna Learning Record `D9EAF7`, freeze A2, border, wrap text, lebar kolom tetap, nomor rata tengah, dan parent playlist abu-abu. Duration disimpan sebagai angka Excel berformat `[hh]:mm:ss` agar total di atas 24 jam tidak berputar. Learning Hour berupa angka desimal; progress berupa angka persentase; tanggal berupa nilai tanggal Excel. AutoFilter hanya untuk output datar, agar hierarki tidak terpisah saat difilter. Teks course diperlakukan sebagai teks, termasuk yang diawali `=`.

## Verifikasi

Pengujian terfokus: **60 test, 549 assertion, seluruhnya lulus**:

- LearningOngoingContentTest: 6 test.
- OngoingPlaylistServiceResolverTest: 10 test.
- MyLearningOngoingExportTest: 5 test.
- OngoingExcelDownloadTest: 4 test.
- LearningRecordExportDurationAndLearningHourFormatTest: 1 test.
- LearningRecordFilterCardExportTest: 16 test.
- MyLearningRecordAllStandardizationTest: 18 test.

Cakupan meliputi 13 header, Attendance, tanggal selesai kosong, nilai actual hour/progress, parent-child, total, isolasi pengguna, filter dan semua halaman, data kosong, score tanpa sertifikat, tidak berubahnya catatan database, serta penulisan dan pembacaan ulang file XLSX. PHP lint dan `git diff --check` juga lulus.

Suite lebih luas: **85 test, 526 assertion; 10 failure dan 3 error**. Rinciannya:

- MyLearningRecordModesTest: 2 assertion tampilan Record gagal.
- MyLearningUnifiedFilterAuditTest: 2 assertion assessment gagal; 3 fixture ditolak database karena nilai `content.status = OK`.
- RecordOnlineDownloadDropdownTest: 6 test mendapat HTTP 403, termasuk endpoint Excel/PDF/CSV admin.

Kegagalan tersebut tidak diperbaiki di luar scope. File export Record, PDF/CSV, dan service jam belajar Record tidak diubah. Karena suite luas belum hijau, hasil ini tidak menyatakan seluruh regresi Record/PDF/CSV sudah terverifikasi lulus.

## Keterbatasan

File `MyLearningRecord_20261013_054913.xlsx` dan screenshot tidak tersedia dalam konteks/workspace yang diperiksa. Acuan styling menggunakan kode Learning Record existing. Workbook berhasil dibaca ulang oleh PhpSpreadsheet; pembukaan visual melalui Microsoft Excel belum diuji.
