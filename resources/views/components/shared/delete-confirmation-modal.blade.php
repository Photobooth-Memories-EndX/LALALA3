@props([
    'id' => 'modal-delete-confirmation',
    'formId' => 'form-delete-confirmation',
    'title' => 'Delete Data',
    'message' => 'Are you sure you want to delete this data?',
    'warning' => 'Deleted data cannot be recovered.',
    'confirmText' => 'Delete',
    'cancelText' => 'Cancel',
    'action' => '#',
    'icon' => 'fas fa-trash-alt',
])

<div class="modal modal-blur fade"
     id="{{ $id }}"
     tabindex="-1"
     role="dialog"
     aria-hidden="true"
     aria-labelledby="{{ $id }}Title"
     data-bs-backdrop="static"
     data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 400px !important;">
        <div class="modal-content rounded-4 border-0 shadow text-center p-3">
            <div class="modal-body pb-2">
                {{-- Delete Warning Icon Circle --}}
                <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center"
                     style="width: 56px; height: 56px; background-color: rgba(214, 57, 57, 0.12); color: #d63939;">
                    <i class="{{ $icon }} fs-2"></i>
                </div>

                {{-- Title & Message --}}
                <h5 class="fw-bold mb-2" id="{{ $id }}Title">{{ $title }}</h5>
                <p class="text-muted small mb-2" id="{{ $id }}Message">{{ $message }}</p>

                @if ($warning)
                    <p class="text-danger small mb-3 fw-semibold" id="{{ $id }}Warning">{{ $warning }}</p>
                @endif

                {{ $slot ?? '' }}

                {{-- Modal Action Buttons --}}
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-pill flex-fill"
                            id="{{ $id }}-btn-cancel"
                            data-bs-dismiss="modal"
                            data-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        {{ $cancelText }}
                    </button>
                    <form method="POST" action="{{ $action }}" id="{{ $formId }}" class="flex-fill m-0 p-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="btn btn-danger rounded-pill w-100 fw-semibold d-inline-flex align-items-center justify-content-center"
                                id="{{ $id }}-btn-confirm">
                            <i class="fas fa-trash-alt me-1"></i>
                            {{ $confirmText }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
