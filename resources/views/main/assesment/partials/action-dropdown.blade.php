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

        <button
            type="button"
            class="dropdown-item btn-duplicate-assessment"
            data-url="{{ route('assesment.clone', $list->slug) }}"
            data-title="{{ $list->title }}"
            data-type="{{ $list->type }}"
            data-content-for="{{ $list->assasment_content_for ?? 'content' }}"
            data-trainingplan-id="{{ $list->trainingplan_id ?? '' }}"
            data-toggle="modal"
            data-target="#modalDuplicateAssessment"
            data-bs-toggle="modal"
            data-bs-target="#modalDuplicateAssessment">

            <i class="fas fa-copy text-warning"></i>
            <span>Duplicate Assessment</span>
        </button>


        <button
            type="button"
            class="dropdown-item btn-copy-assessment"
            data-copy-text="{{ route('assesment.user.view', $list->slug) }}">

            <i class="fas fa-copy text-info"></i>
            <span>Copy Link Assessment </span>
        </button>

        <a href="{{ route('assesment.result', $list->slug) }}"
            class="dropdown-item">

            <i class="fas fa-chart-line text-success"></i>
            <span>Assessment Result</span>
        </a>

        <div class="dropdown-divider"></div>

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


        <a href="{{ route('assesment.assign.users.index', $list->slug) }}"
            class="dropdown-item">

            <i class="fas fa-users text-secondary"></i>
            <span>Assign Users</span>
        </a>



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