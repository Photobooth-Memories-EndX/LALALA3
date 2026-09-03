<script src="{{ asset('js/gallery.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function initGalleryAutocomplete() {
        var searchInput = document.getElementById('gallery-search-input') || document.querySelector('input[placeholder*="Search by title"]');
        var searchResults = document.getElementById('gallery-search-results');

        if (!searchInput || !searchResults) return;

        var listEl = searchResults.querySelector('.autocomplete-list-group') || searchResults;
        var footerEl = searchResults.querySelector('.search-footer');
        var debounceTimer;

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            var query = this.value.trim();

            if (query.length < 1) {
                searchResults.style.display = 'none';
                if (listEl) listEl.innerHTML = '';
                if (footerEl) footerEl.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(function () {
                fetch('/gallery/search-autocomplete?q=' + encodeURIComponent(query), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (listEl) listEl.innerHTML = '';
                        if (data && data.length > 0) {
                            data.forEach(function (item) {
                                var a = document.createElement('a');
                                a.className = 'dropdown-item py-2 px-3 text-wrap border-bottom-subtle gallery-search-item';
                                a.href = item.slug ? '/gallery/' + encodeURIComponent(item.slug) : '#';

                                var titleText = item.title || item.name || '';
                                var photoCount = item.photo_count !== undefined ? item.photo_count : null;
                                var category = item.category || null;
                                var eventDate = item.event_date || null;

                                var subDetails = [];
                                if (photoCount !== null) {
                                    subDetails.push(photoCount + ' ' + (photoCount === 1 ? 'Photo' : 'Photos'));
                                }
                                if (category) subDetails.push(category);
                                if (eventDate) subDetails.push(eventDate);

                                a.innerHTML = '<div class="d-flex align-items-center justify-content-between mb-1">' +
                                        '<span class="text-dark small fw-semibold text-truncate me-2">' + escapeHtml(titleText) + '</span>' +
                                        '<span class="badge bg-purple-lt text-purple small flex-shrink-0">GALLERY</span>' +
                                    '</div>' +
                                    (subDetails.length > 0 ? '<div class="text-muted text-truncate" style="font-size: 0.75rem;">' + subDetails.map(escapeHtml).join(' &bull; ') + '</div>' : '');

                                if (!item.slug) {
                                    a.addEventListener('click', function (e) {
                                        e.preventDefault();
                                        searchInput.value = titleText;
                                        searchResults.style.display = 'none';

                                        var form = searchInput.closest('form');
                                        if (form) {
                                            if (typeof form.requestSubmit === 'function') {
                                                form.requestSubmit();
                                            } else {
                                                form.submit();
                                            }
                                        }
                                    });
                                }
                                if (listEl) listEl.appendChild(a);
                            });
                        }

                        var keyword = searchInput.value.trim();
                        if (footerEl) {
                            if (data && data.length > 0) {
                                footerEl.innerHTML = `
                                    <a href="/gallery?q=${encodeURIComponent(keyword)}" class="dropdown-item fw-semibold text-primary py-2 d-flex align-items-center justify-content-center gap-2">
                                        <i class="ti ti-search"></i>
                                        <span>See all results for "${keyword}"</span>
                                    </a>
                                `;
                                footerEl.style.display = 'block';
                            } else {
                                footerEl.innerHTML = '<div class="dropdown-item text-muted disabled py-2">No items found</div>';
                                footerEl.style.display = 'block';
                            }
                        }
                        searchResults.style.display = 'block';
                    })
                    .catch(function () {
                        searchResults.style.display = 'none';
                    });
            }, 250);
        });

        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length >= 1 && ((listEl && listEl.children.length > 0) || (footerEl && footerEl.style.display !== 'none'))) {
                searchResults.style.display = 'block';
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (searchInput && searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    initGalleryAutocomplete();
});
</script>
