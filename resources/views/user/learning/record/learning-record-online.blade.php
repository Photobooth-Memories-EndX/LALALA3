{{--
    Sub-tab: KP OWL (Online Learning Record)
    Delegates to the existing <x-learning.record-section> component.
    Standardized 12 Columns:
    1. No | 2. Course Title | 3. Type | 4. Category | 5. Main Classification | 6. Duration
    7. Learning Hour | 8. Progress | 9. Date Time | 10. Attendance | 11. Score (L2) | 12. Certificate
--}}
<template data-learning-record-hero>
    <x-shared.hero-learning-record
        recordMode="online"
        :stats="$recordStats ?? []"
    />
</template>

<x-learning.record-section
    :rows="$rows ?? collect()"
    :certificateReady="$certificateReady ?? collect()"
    :filterOptions="$filterOptions ?? []"
    :recordStats="$recordStats ?? []"
/>
