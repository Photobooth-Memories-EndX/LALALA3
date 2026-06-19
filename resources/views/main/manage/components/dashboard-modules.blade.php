<div class="mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">Management Modules</h2>
            <div class="text-muted">
                Pusat pengelolaan seluruh fitur utama OWL LMS.
            </div>
        </div>
    </div>

    <div class="row g-3">

        @if(auth()->user()->canAccessManageModule('learning_management'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'Learning Management',
                'description' => 'Melihat data pembelajaran karyawan, progres belajar, riwayat learning, dan monitoring aktivitas learning.',
                'url' => url('manage/learning'),
                'button' => 'Manage Learning',
                'icon' => 'ti ti-school'
            ])
        @endif

        @if(auth()->user()->canAccessManageModule('user_management'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'User Management',
                'description' => 'Mengelola akun karyawan, data user, status akun, reset password, dan profil pengguna LMS.',
                'url' => url('manage/user'),
                'button' => 'Manage Users',
                'icon' => 'ti ti-users'
            ])
        @endif

        @if(auth()->user()->canAccessManageModule('admin_management'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'Admin Management',
                'description' => 'Mengatur akses administrator, super admin, dan hak kelola pengguna yang memiliki role pengelola sistem.',
                'url' => url('manage/admin'),
                'button' => 'Manage Admin',
                'icon' => 'ti ti-shield-lock'
            ])
        @endif

        @if(auth()->user()->canAccessManageModule('page_management'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'Page Management',
                'description' => 'Mengelola halaman statis, informasi platform, konten halaman, dan kebutuhan informasi umum LMS.',
                'url' => url('manage/pages'),
                'button' => 'Manage Pages',
                'icon' => 'ti ti-layout'
            ])
        @endif

        @if(auth()->user()->canAccessManageModule('news_announcement'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'News & Announcement',
                'description' => 'Membuat dan mengelola berita, pengumuman, informasi training, serta komunikasi internal perusahaan.',
                'url' => url('manage/news'),
                'button' => 'Manage News',
                'icon' => 'ti ti-news'
            ])
        @endif

        @if(auth()->user()->canAccessManageModule('content_repository'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'Content Repository',
                'description' => 'Mengelola materi pembelajaran seperti video, YouTube, dokumen, SOP, thumbnail, author, dan status publish.',
                'url' => url('manage/content'),
                'button' => 'Manage Content',
                'icon' => 'ti ti-book-2'
            ])
        @endif

        @if(auth()->user()->canAccessManageModule('category_classification'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'Category & Classification',
                'description' => 'Mengatur kategori materi, klasifikasi learning, pengelompokan konten, dan struktur pencarian content.',
                'url' => url('manage/classification'),
                'button' => 'Manage Category',
                'icon' => 'ti ti-category'
            ])
        @endif

        @if(auth()->user()->canAccessManageModule('assessment_management'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'Assessment Management',
                'description' => 'Mengelola quiz, assessment, passing grade, remedial, assign user, link assessment, dan hasil penilaian.',
                'url' => url('manage/assesment'),
                'button' => 'Manage Assessment',
                'icon' => 'ti ti-clipboard-check'
            ])
        @endif

        @if(auth()->user()->canAccessManageModule('question_bank'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'Question Bank',
                'description' => 'Mengelola bank soal, pilihan jawaban, gambar soal, cloning soal, sinkronisasi soal, dan reusable question set.',
                'url' => url('manage/question-bank'),
                'button' => 'Manage Question Bank',
                'icon' => 'ti ti-help-circle'
            ])
        @endif

        @if(auth()->user()->canAccessManageModule('playlist_learning_path'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'Playlist & Learning Path',
                'description' => 'Menyusun alur pembelajaran, playlist training, urutan materi, learning path, dan paket pembelajaran terstruktur.',
                'url' => url('manage/trainingplan'),
                'button' => 'Manage Playlist',
                'icon' => 'ti ti-route'
            ])
        @endif

        @if(auth()->user()->canAccessManageModule('event_documentation'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'Event Documentation',
                'description' => 'Mengelola dokumentasi event, gallery foto training, kegiatan perusahaan, dan arsip dokumentasi pembelajaran.',
                'url' => url('manage/eventphoto'),
                'button' => 'Manage Gallery',
                'icon' => 'ti ti-photo'
            ])
        @endif

        @if(auth()->user()->canAccessManageModule('certificate_setting'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'Certificate Setting',
                'description' => 'Mengatur syarat pembuatan sertifikat seperti minimum progress, minimum score, issuer name, logo penerbit, dan status sertifikat.',
                'url' => route('certificate.setting.index'),
                'button' => 'Manage Certificate',
                'icon' => 'ti ti-award'
            ])
        @endif

    </div>
</div>

@if(
    auth()->user()->canManageRole() ||
    auth()->user()->canAccessManageModule('monitoring') ||
    auth()->user()->isSuperAdmin()
)

    <div class="mt-5 mb-5">

        <hr class="my-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Super Administrator Access</h2>
                <div class="text-muted">
                    Akses khusus untuk pengaturan role, permission, dan monitoring sistem LMS.
                </div>
            </div>
        </div>

        <div class="row g-3">

            @if(auth()->user()->canManageRole())
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Role & Permission Management',
                    'description' => 'Mengelola role, permission, hak akses menu, pembatasan fitur, serta kontrol otorisasi seluruh pengguna LMS.',
                    'url' => route('manage.role.index'),
                    'button' => 'Manage Role',
                    'icon' => 'ti ti-user-shield'
                ])
            @endif

            @if(auth()->user()->canAccessManageModule('monitoring'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => auth()->user()->isSubadminDivision()
                        ? 'Division Monitoring'
                        : 'Learning Monitoring',
                    'description' => auth()->user()->isSubadminDivision()
                        ? 'Monitoring progress learning karyawan berdasarkan division yang menjadi tanggung jawab Anda.'
                        : 'Monitoring aktivitas learning, progress user, assessment, sertifikat, dan statistik pembelajaran secara keseluruhan.',
                    'url' => route('monitoring.index'),
                    'button' => auth()->user()->isSubadminDivision()
                        ? 'Open Division Monitoring'
                        : 'Open Monitoring',
                    'icon' => 'ti ti-chart-bar'
                ])
            @endif

            @if(auth()->user()->isSuperAdmin())
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Access Permission',
                    'description' => 'Mengatur akses permission berdasarkan role, pembatasan menu, dan kontrol hak akses fitur pada sistem OWL LMS.',
                    'url' => route('manage.access-permission.index'),
                    'button' => 'Manage Permission',
                    'icon' => 'ti ti-lock-access'
                ])
            @endif

        </div>
    </div>
@endif
