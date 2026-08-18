{{-- Mobile Horizontal Scroll Tabs (visible on mobile <768px) --}}
<div class="d-md-none mb-3">
    <div class="nav nav-pills flex-row flex-nowrap overflow-auto py-1 owl-profile-mobile-tabs gap-2">
        <a href="{{ route('user.profile') }}" class="nav-link text-nowrap {{ request()->routeIs('user.profile') ? 'active' : '' }}">
            <i class="ti ti-user icon-nav"></i> <span>My Account</span>
        </a>
        <a href="{{ route('user.comment') }}" class="nav-link text-nowrap {{ request()->routeIs('user.comment') ? 'active' : '' }}">
            <i class="ti ti-message icon-nav"></i> <span>My Comment</span>
        </a>
        <a href="{{ route('user.changepassword') }}" class="nav-link text-nowrap {{ request()->routeIs('user.changepassword') ? 'active' : '' }}">
            <i class="ti ti-lock icon-nav"></i> <span>Change Password</span>
        </a>
    </div>
</div>

{{-- Desktop Sidebar Navigation (visible on desktop >=768px) --}}
<div class="col-md-3 d-none d-md-block border-end p-0">
    <div class="card-body p-3">
        <div class="text-uppercase text-muted fw-bold mb-3 small px-3">Settings</div>
        <div class="list-group list-group-transparent owl-profile-sidebar-nav gap-1">
            <a href="{{ route('user.profile') }}" class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('user.profile') ? 'active' : '' }}">
                <i class="ti ti-user icon-nav"></i>
                <span>My Account</span>
            </a>
            <a href="{{ route('user.comment') }}" class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('user.comment') ? 'active' : '' }}">
                <i class="ti ti-message icon-nav"></i>
                <span>My Comment</span>
            </a>
            <a href="{{ route('user.changepassword') }}" class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('user.changepassword') ? 'active' : '' }}">
                <i class="ti ti-lock icon-nav"></i>
                <span>Change Password</span>
            </a>
        </div>
    </div>
</div>
