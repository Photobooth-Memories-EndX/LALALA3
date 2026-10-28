{{--
    Sub-tab: KP OWL (Online Learning Record)
    Delegates to the existing <x-learning.record-section> component.
    Standardized 12 Columns:
    1. No | 2. Course Title | 3. Type | 4. Category | 5. Main Classification | 6. Duration
    7. Learning Hour | 8. Progress | 9. Date Time | 10. Attendance | 11. Assessment Score (L2) | 12. Certificate
--}}
<template data-learning-record-hero>
    <x-shared.hero-learning-record
        recordMode="online"
        :stats="$recordStats ?? []"
    />
</template>

<style>
    .table-responsive,
    .learning-record-scroll {
        max-height: 70vh !important;
        overflow-y: auto !important;
        overflow-x: auto !important;
        position: relative !important;
        -webkit-overflow-scrolling: touch;
        width: 100% !important;
    }
    table thead th,
    .learning-record-table thead th,
    #learningRecordTable thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 10 !important;
        background-color: #f8fafc !important; /* Solid neutral background so rows pass underneath cleanly */
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); /* Subtle bottom border line when sticky */
    }
    [data-bs-theme="dark"] table thead th,
    [data-bs-theme="dark"] .learning-record-table thead th,
    [data-bs-theme="dark"] #learningRecordTable thead th,
    body.dark-mode table thead th,
    body.dark-mode .learning-record-table thead th,
    body.dark-mode #learningRecordTable thead th,
    body.theme-dark table thead th,
    body.theme-dark .learning-record-table thead th,
    body.theme-dark #learningRecordTable thead th {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
    }
</style>

<x-learning.record-section
    :rows="$rows ?? collect()"
    :certificateReady="$certificateReady ?? collect()"
    :filterOptions="$filterOptions ?? []"
    :recordStats="$recordStats ?? []"
/>
