<script src="{{ asset('js/gallery.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('gallery-search-input');
    const searchResults = document.getElementById('gallery-search-results');
    const autocompleteList = document.getElementById('gallery-autocomplete-list');
    const searchFooter = document.getElementById('gallery-search-footer');

    if (!searchInput || !searchResults || !autocompleteList || !searchFooter) return;

    let debounceTimer;

    searchInput.addEventListener('input', function (e) {
        // Strict guard: ONLY process inputs from #gallery-search-input
        if (e.target.id !== 'gallery-search-input') return;

        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length < 1) {
            searchResults.style.display = 'none';
            autocompleteList.innerHTML = '';
            searchFooter.innerHTML = '';
            searchFooter.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`/gallery/search-autocomplete?q=${encodeURIComponent(query)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                autocompleteList.innerHTML = '';
                searchFooter.innerHTML = '';

                if (data && data.length > 0) {
                    data.forEach(item => {
                        const a = document.createElement('a');
                        a.className = 'dropdown-item py-2 px-3 text-wrap d-flex align-items-center justify-content-between border-bottom-subtle';
                        a.href = `/gallery/${item.slug || item.id}`;
                        a.innerHTML = `
                            <span class="text-truncate fw-normal">${item.title}</span>
                            <span class="badge bg-purple-subtle text-purple ms-2 flex-shrink-0" style="font-size: 10px;">GALLERY</span>
                        `;
                        autocompleteList.appendChild(a);
                    });

                    // Sticky "See all results" footer link
                    searchFooter.innerHTML = `
                        <a href="/gallery?q=${encodeURIComponent(query)}" class="dropdown-item fw-semibold text-primary py-1 d-flex align-items-center justify-content-center gap-2">
                            <i class="ti ti-search"></i>
                            <span>See all results for "${query}"</span>
                        </a>
                    `;
                    searchFooter.style.display = 'block';
                    searchResults.style.display = 'block';
                } else {
                    autocompleteList.innerHTML = `<div class="dropdown-item text-muted disabled py-2 px-3">No event galleries found</div>`;
                    searchFooter.style.display = 'none';
                    searchResults.style.display = 'block';
                }
            })
            .catch(() => {
                searchResults.style.display = 'none';
            });
        }, 250);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (searchInput && searchResults) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        }
    });
});
</script>
