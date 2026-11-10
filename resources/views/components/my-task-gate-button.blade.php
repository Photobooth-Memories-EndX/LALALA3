{{-- Tombol "Task" yang terkunci sampai progress playlist/course 100%.
     Cara pasang: di view-navigation (barisan "Assessment Playlist / Task /
     Generate Certificate"), ganti tombol Task yang lama dengan:

         @include('components.my-task-gate-button', ['planId' => $playlist->id])

     "100%" di sini = angka yang sama persis dengan "N% Progress" di
     Content List, yaitu $playlistUiState['learning_progress'] (dari
     controller: round(finished/total*100), sudah termasuk debug override).

     Logic:
     - < 100% : tombol terkunci (disabled) di dalam wrapper bertitle;
       hover memunculkan kursor not-allowed dan tooltip (mengikuti pola
       Generate Certificate), tombol tidak dapat diklik.
     - = 100% : tombol normal; diklik mencatat unlock lalu mengarah ke tab Task.
--}}
@php
    $mtPlanId = $planId ?? null;
    // Samakan dengan angka "N% Progress" di Content List.
    $mtProgress = (float) ($playlistUiState['learning_progress'] ?? 0);
    $mtLocked = $mtProgress < 100;
@endphp

@if ($mtLocked)
    <span class="task-action-wrapper certificate-action-wrapper is-disabled d-block flex-fill"
          tabindex="0"
          title="Complete the course 100% first to unlock Task.">
        <button type="button"
                class="btn btn-primary task-action-button certificate-action-button w-100"
                aria-disabled="true"
                disabled>
            <i class="ti ti-lock me-2" aria-hidden="true"></i>Task
        </button>
    </span>
@else
    <a href="{{ route('my-task.unlock-playlist', ['playlist' => $mtPlanId]) }}" class="btn btn-primary flex-fill">
        <i class="ti ti-list-check me-2"></i>Task
    </a>
@endif
