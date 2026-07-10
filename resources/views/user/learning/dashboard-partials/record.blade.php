{{-- Prioritas paling atas: certificate yang sudah memenuhi seluruh syarat. --}}
{{-- <x-learning.record-certificates :items="$certificateReady" /> --}}

{{-- Learning Record: tabel Blade biasa; search, filter & pagination client-side (jQuery, tanpa AJAX). --}}
<x-learning.record-section :rows="$rows" :filter-options="$filterOptions" />
