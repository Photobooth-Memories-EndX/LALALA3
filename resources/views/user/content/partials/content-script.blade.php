<script>
    $(function () {
        const $filterForm = $('#contentFilterForm');
        const defaultYear = String($filterForm.data('default-year'));

        $('#contentFilterMonth, #contentFilterYear').on('change', function () {
            if ($('#contentFilterMonth').val() && !$('#contentFilterYear').val()) {
                $('#contentFilterYear').val(defaultYear);
            }

            $filterForm.trigger('submit');
        });

        $('.content-auto-submit').on('change', function () {
            $filterForm.trigger('submit');
        });

        function escapeContentHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ── Live Autocomplete Search for Content Filter ─────────────────────────
        function initContentAutocomplete() {
            var searchInput = document.getElementById('content-search-input') || document.getElementById('contentSearch');
            var searchResults = document.getElementById('content-search-results');
            var debounceTimer = null;

            if (!searchInput || !searchResults) return;

            $(searchInput).on('input', function () {
                var value = $.trim(this.value);
                clearTimeout(debounceTimer);

                if (value.length < 1) {
                    searchResults.style.display = 'none';
                    searchResults.innerHTML = '';
                    return;
                }

                debounceTimer = setTimeout(function () {
                    fetch('/content/search-autocomplete?q=' + encodeURIComponent(value), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (response) {
                        if (!response.ok) throw new Error('HTTP ' + response.status);
                        return response.json();
                    })
                    .then(function (data) {
                        if (!data || data.length === 0) {
                            searchResults.innerHTML = '<div class="dropdown-item text-muted small py-2 px-3">No content found</div>';
                        } else {
                            var html = '';
                            data.forEach(function (item) {
                                var badgeClass = item.type === 'video'
                                    ? 'bg-blue-lt text-blue'
                                    : (item.type === 'youtube' ? 'bg-red-lt text-red' : 'bg-green-lt text-green');
                                var typeLabel = item.type_label || (item.type ? escapeContentHtml(item.type.toUpperCase()) : 'CONTENT');

                                var subDetails = [];
                                if (item.author) subDetails.push('By: ' + escapeContentHtml(item.author));
                                if (item.category) subDetails.push(escapeContentHtml(item.category));
                                if (item.duration) subDetails.push(escapeContentHtml(item.duration));

                                html += '<a href="#" class="dropdown-item py-2 px-3 text-wrap border-bottom-subtle content-search-item" data-title="' + escapeContentHtml(item.title) + '">' +
                                    '<div class="d-flex align-items-center justify-content-between mb-1">' +
                                        '<span class="text-dark small fw-semibold text-truncate me-2">' + escapeContentHtml(item.title) + '</span>' +
                                        '<span class="badge ' + badgeClass + ' small flex-shrink-0">' + escapeContentHtml(typeLabel) + '</span>' +
                                    '</div>' +
                                    (subDetails.length > 0 ? '<div class="text-muted text-truncate" style="font-size: 0.75rem;">' + subDetails.join(' &bull; ') + '</div>' : '') +
                                '</a>';
                            });
                            searchResults.innerHTML = html;
                        }
                        searchResults.style.display = 'block';
                    })
                    .catch(function () {
                        searchResults.style.display = 'none';
                    });
                }, 250);
            });

            $(searchInput).on('focus', function () {
                if ($.trim(this.value).length >= 1 && searchResults.children.length > 0) {
                    searchResults.style.display = 'block';
                }
            });

            $(document).on('click', '.content-search-item', function (event) {
                event.preventDefault();
                var title = String($(this).data('title') || '');
                $('#content-search-input, #contentSearch').val(title);
                if (searchResults) searchResults.style.display = 'none';
                $filterForm.trigger('submit');
            });

            document.addEventListener('click', function (e) {
                if (searchInput && searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
        }

        initContentAutocomplete();
    });
</script>
