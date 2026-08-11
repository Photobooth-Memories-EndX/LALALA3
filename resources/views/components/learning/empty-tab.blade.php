@props([
    'title' => 'Empty Page',
    'description' => 'Halaman ini masih kosong dan bisa dikembangkan nanti.',
])

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body text-center py-5">
        <h3>{{ $title }}</h3>
        <p class="text-muted mb-0">{{ $description }}</p>
    </div>
</div>