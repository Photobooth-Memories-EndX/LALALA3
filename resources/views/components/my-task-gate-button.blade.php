{{-- Tombol "Task" yang terkunci sampai progress playlist/course 100%.
     Cara pasang: di view-navigation (barisan "Assessment Playlist / Task /
     Generate Certificate"), ganti tombol Task yang lama dengan:

         @include('components.my-task-gate-button', ['planId' => $playlist->id])

     "100%" di sini = angka yang sama persis dengan "N% Progress" di
     Content List, yaitu $playlistUiState['learning_progress'] (dari
     controller: round(finished/total*100), sudah termasuk debug override).

     Logic:
     - < 100% : tombol tampil tapi dikunci dalam <span> bertitle (pola yang
       sama dengan tombol Generate Certificate, karena tombol disabled tidak
       memunculkan tooltip); hover memunculkan peringatan, diklik muncul
       pesan peringatan.
     - = 100% : tombol normal; diklik mencatat unlock lalu mengarah ke tab Task.
--}}
@php
    $mtPlanId = $planId ?? null;
    // Samakan dengan angka "N% Progress" di Content List.
    $mtProgress = (float) ($playlistUiState['learning_progress'] ?? 0);
    $mtLocked = $mtProgress < 100;
@endphp

@if ($mtLocked)
    <span class="d-block flex-fill" title="Complete the course 100% first to unlock Task.">
        <button type="button" class="btn btn-secondary opacity-75 w-100"
                onclick="alert('Complete the course 100% first to unlock Task.')">
            <i class="ti ti-lock me-2" aria-hidden="true"></i>Task
        </button>
    </span>
@else
    <a href="{{ route('my-task.unlock-playlist', ['playlist' => $mtPlanId]) }}" class="btn btn-primary flex-fill">
        <i class="ti ti-list-check me-2"></i>Task
    </a>
@endif
