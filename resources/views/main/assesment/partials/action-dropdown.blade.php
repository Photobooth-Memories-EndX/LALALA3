<div class="dropdown">

    <button
        class="btn btn-sm btn-light owl-dropdown-btn"
        type="button"
        data-toggle="dropdown"
        data-display="static"
        data-bs-toggle="dropdown"
        data-bs-display="static"
        aria-expanded="false">

        <i class="fas fa-ellipsis-h"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right owl-dropdown-menu">

        <div class="dropdown-header">
            Assessment Actions
        </div>

        <a href="{{ route('assesment.edit', $list->slug) }}"
            class="dropdown-item">

            <i class="fas fa-pen text-primary"></i>
            <span>Edit Assessment</span>
        </a>

        <a href="{{ route('assesment.newlink', $list->slug) }}"
            class="dropdown-item">

            <i class="fas fa-link text-info"></i>
            <span>Generate New Link</span>
        </a>

        <a href="{{ route('assesment.question', $list->slug) }}"
            class="dropdown-item">

            <i class="fas fa-list-check text-success"></i>
            <span>Question List</span>
        </a>

        <a href="{{ route('assesment.result', $list->slug) }}"
            class="dropdown-item">

            <i class="fas fa-chart-line text-success"></i>
            <span>View Result</span>
        </a>

        <a href="{{ route('assesment.assign.users.index', $list->slug) }}"
            class="dropdown-item">

            <i class="fas fa-users text-secondary"></i>
            <span>Assign Users</span>
        </a>

        <a href="{{ route('assesment.clone', $list->slug) }}"
            class="dropdown-item"
            onclick="return confirm('Clone this assessment ?')">

            <i class="fas fa-copy text-warning"></i>
            <span>Clone Assessment</span>
        </a>

        <button
            type="button"
            class="dropdown-item btn-copy-assessment"
            data-copy-text="{{ route('assesment.user.view', $list->slug) }}">

            <i class="fas fa-copy text-info"></i>
            <span>Copy Assessment Link</span>
        </button>

        <div class="dropdown-divider"></div>

        <form action="{{ route('assesment.destroy', $list->slug) }}"
            method="POST">

            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="dropdown-item dropdown-delete"
                onclick="return confirm('Delete this assessment ?')">

                <i class="fas fa-trash-alt text-danger"></i>
                <span>Delete Assessment</span>
            </button>

        </form>

    </div>

</div>