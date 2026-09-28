<div class="mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">Management Modules</h2>
            <div class="text-muted">
                The central hub for managing all core OWL LMS features.
            </div>
        </div>
    </div>

    <div class="row g-3">
        
        @if(auth()->user()->canAccessManageModule('learning_management'))
        @include('main.manage.components.admin-menu-card', [
            'title' => 'Learning Management',
            'description' => 'Monitor employee learning activities, track learning progress, review learning history, and oversee training performance.',
            'url' => url('manage/learning'),
            'button' => 'Manage Learning',
            'icon' => 'ti ti-school'
            ])
            @endif

        @if(auth()->user()->canAccessManageModule('task_management'))
        @include('main.manage.components.admin-menu-card', [
            'title' => 'Task Management',
            'description' => 'Manage course tasks and assignments, set deadlines, publish tasks, and review user submissions.',
            'url' => url('manage/task'),
            'button' => 'Manage Tasks',
            'icon' => 'ti ti-clipboard-list'
            ])
            @endif
            
            @if(auth()->user()->canAccessManageModule('assessment_management'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Assessment Management',
                    'description' => 'Manage quizzes, assessments, passing scores, remedial tests, user assignments, assessment links, and assessment results.',
                    'url' => url('manage/assesment'),
                    'button' => 'Manage Assessments',
                    'icon' => 'ti ti-clipboard-check'
                ])
            @endif

            @if(auth()->user()->canAccessManageModule('content_repository'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Content Repository',
                    'description' => 'Manage learning materials, including videos, MOOC content, documents, SOPs, thumbnails, authors, and publication status.',
                    'url' => url('manage/content'),
                    'button' => 'Manage Content',
                    'icon' => 'ti ti-book-2'
                ])
            @endif

            @if(auth()->user()->canAccessManageModule('playlist_learning_path'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Playlist & Learning Path',
                    'description' => 'Create learning paths, training playlists, course sequences, and structured learning programs.',
                    'url' => url('manage/playlist'),
                    'button' => 'Manage Playlists',
                    'icon' => 'ti ti-route'
                    ])
            @endif
            
            @if(auth()->user()->canAccessManageModule('question_bank'))
            @include('main.manage.components.admin-menu-card', [
                'title' => 'Question Bank',
                'description' => 'Manage question banks, answer choices, question images, question cloning, synchronization, and reusable question sets.',
                'url' => url('manage/question-bank'),
                'button' => 'Manage Question Bank',
                'icon' => 'ti ti-help-circle'
                ])
            @endif
            
            @if(auth()->user()->canAccessManageModule('news_announcement'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'News & Announcements',
                    'description' => 'Create and manage news, announcements, training updates, and internal company communications.',
                    'url' => url('manage/news'),
                    'button' => 'Manage News',
                    'icon' => 'ti ti-news'
                ])
            @endif
            
            @if(auth()->user()->canAccessManageModule('event_documentation'))
                @include('main.manage.components.admin-menu-card', [
                        'title' => 'Event Documentation',
                        'description' => 'Manage training event galleries, company event photos, and learning documentation archives.',
                        'url' => url('manage/eventphoto'),
                        'button' => 'Manage Gallery',
                        'icon' => 'ti ti-photo'
                    ])
            @endif

            @if(auth()->user()->canAccessManageModule('category_classification'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Category & Classification',
                    'description' => 'Organize learning categories, content classifications, content grouping, and search structure.',
                    'url' => url('manage/classification'),
                    'button' => 'Manage Categories',
                    'icon' => 'ti ti-category'
                ])
            @endif

            @if(auth()->user()->canAccessManageModule('user_management'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'User Management',
                    'description' => 'Manage employee accounts, user information, account status, password resets, and LMS user profiles.',
                    'url' => url('manage/user'),
                    'button' => 'Manage Users',
                    'icon' => 'ti ti-users'
                ])
            @endif

            @if(auth()->user()->canAccessManageModule('admin_management'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Admin Management',
                    'description' => 'Manage administrator access, super admin privileges, and system management roles.',
                    'url' => url('manage/admin'),
                    'button' => 'Manage Admin',
                    'icon' => 'ti ti-shield-lock'
                ])
            @endif

            @if(auth()->user()->canAccessManageModule('page_management'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Page Management',
                    'description' => 'Manage static pages, platform information, page content, and general LMS information.',
                    'url' => url('manage/pages'),
                    'button' => 'Manage Pages',
                    'icon' => 'ti ti-layout'
                ])
            @endif


            @if(auth()->user()->canAccessManageModule('certificate_setting'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Certificate Settings',
                    'description' => 'Configure certificate requirements, including minimum progress, minimum score, issuer name, issuer logo, and certificate status.',
                    'url' => route('certificate.setting.index'),
                    'button' => 'Manage Certificates',
                    'icon' => 'ti ti-award'
                ])
            @endif

    </div>
</div>

            @if(
                auth()->user()->canManageRole() ||
                auth()->user()->canAccessManageModule('monitoring') ||
                auth()->user()->canAccessManageModule('learning_hours_management') ||
                auth()->user()->isSuperAdmin()
            )

                <div class="mt-4 mb-4">

                    <hr class="my-4 admin-section-divider">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="mb-1">Super Administrator Access</h2>
                            <div class="text-muted">
                                Special access to manage roles, permissions, and LMS system monitoring.
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">

            @if(auth()->user()->canManageRole())
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Role & Permission Management',
                    'description' => 'Manage user roles, permissions, menu access, feature restrictions, and authorization settings across the OWL LMS.',
                    'url' => route('manage.role.index'),
                    'button' => 'Manage Roles',
                    'icon' => 'ti ti-user-shield'
                ])
            @endif

            @if(auth()->user()->canAccessManageModule('monitoring'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => auth()->user()->isSubadminDivision()
                        ? 'Division Monitoring'
                        : 'Learning Monitoring',
                    'description' => auth()->user()->isSubadminDivision()
                        ? 'Monitor employee learning progress within your assigned division.'
                        : 'Monitor learning activities, user progress, assessments, certificates, and overall learning analytics.',
                    'url' => route('monitoring.index'),
                    'button' => auth()->user()->isSubadminDivision()
                        ? 'Open Division Monitoring'
                        : 'Open Monitoring',
                    'icon' => 'ti ti-chart-bar'
                ])
            @endif

            @if(auth()->user()->canAccessManageModule('learning_hours_management'))
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Learning Hour Management',
                    'description' => 'Manage and monitor learning hours for Content User and Playlist Content User records.',
                    'url' => route('manage.learning-hours.index'),
                    'button' => 'Manage Learning Hours',
                    'icon' => 'ti ti-clock-check'
                ])
            @endif

            @if(auth()->user()->isSuperAdmin())
                @include('main.manage.components.admin-menu-card', [
                    'title' => 'Access Permissions',
                    'description' => 'Configure role-based permissions, menu access, and feature authorization across the OWL LMS.',
                    'url' => route('manage.access-permission.index'),
                    'button' => 'Manage Permissions',
                    'icon' => 'ti ti-lock-access'
                ])
            @endif

        </div>
    </div>
@endif
