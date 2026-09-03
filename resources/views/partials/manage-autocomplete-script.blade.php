<script>
document.addEventListener('DOMContentLoaded', function () {
    function setupManageAutocomplete(config) {
        const input = document.getElementById(config.inputId);
        const results = document.getElementById(config.resultsId);
        const list = document.getElementById(config.listId);
        const footer = document.getElementById(config.footerId);

        if (!input || !results || !list || !footer) return;

        // Prevent duplicate listener binding
        if (input.dataset.manageAutocompleteBound === 'true') return;
        input.dataset.manageAutocompleteBound = 'true';

        let timer = null;

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        input.addEventListener('input', function (e) {
            // Strict ID Guard against header search or other inputs collision
            if (e.target && e.target.id !== config.inputId) return;

            clearTimeout(timer);
            const query = this.value.trim();

            if (query.length < 1) {
                results.style.display = 'none';
                list.innerHTML = '';
                footer.innerHTML = '';
                footer.style.display = 'none';
                return;
            }

            timer = setTimeout(() => {
                fetch(`${config.endpoint}?q=${encodeURIComponent(query)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(data => {
                    list.innerHTML = '';
                    footer.innerHTML = '';

                    if (data && data.length > 0) {
                        // Limit rendering to maximum 10 items
                        const itemsToRender = data.slice(0, 10);

                        itemsToRender.forEach(item => {
                            const a = document.createElement('a');
                            a.className = 'dropdown-item py-2 px-3 text-wrap d-flex align-items-center justify-content-between border-bottom-subtle';
                            a.href = item.url || `${config.targetUrl}?q=${encodeURIComponent(item.title)}`;

                            // Build subtitle details if available
                            let subDetails = [];
                            if (item.subtitle) {
                                subDetails.push(item.subtitle);
                            } else {
                                if (item.classification) subDetails.push(item.classification);
                                if (item.author) subDetails.push('By: ' + item.author);
                                if (item.category) subDetails.push(item.category);
                                if (item.uploader) subDetails.push('By: ' + item.uploader);
                                if (item.total_contents !== undefined && item.total_contents !== null) {
                                    subDetails.push(item.total_contents + ' ' + (item.total_contents === 1 ? 'material' : 'materials'));
                                }
                                if (item.questions_count !== undefined && item.questions_count !== null) {
                                    subDetails.push(item.questions_count + ' ' + (item.questions_count === 1 ? 'question' : 'questions'));
                                }
                                if (item.photo_count !== undefined && item.photo_count !== null) {
                                    subDetails.push(item.photo_count + ' ' + (item.photo_count === 1 ? 'photo' : 'photos'));
                                }
                                if (item.event_date) subDetails.push(item.event_date);
                                if (item.active) subDetails.push('Status: ' + item.active);
                            }

                            const subHtml = subDetails.length > 0 
                                ? `<small class="text-muted text-truncate" style="font-size: 11px;">${subDetails.map(escapeHtml).join(' &bull; ')}</small>` 
                                : '';

                            const badgeText = item.type || config.badgeText;
                            const badgeClass = config.badgeClass || 'bg-purple-subtle text-purple';

                            a.innerHTML = `
                                <div class="d-flex flex-column min-w-0 me-2">
                                    <span class="text-truncate fw-medium text-dark">${escapeHtml(item.title)}</span>
                                    ${subHtml}
                                </div>
                                <span class="badge ${badgeClass} text-uppercase ms-2 flex-shrink-0" style="font-size: 10px;">${escapeHtml(badgeText)}</span>
                            `;
                            list.appendChild(a);
                        });

                        // Sticky "See all results" footer link
                        footer.innerHTML = `
                            <a href="${config.targetUrl}?q=${encodeURIComponent(query)}" class="dropdown-item fw-semibold text-primary py-1 d-flex align-items-center justify-content-center gap-2">
                                <i class="ti ti-search"></i>
                                <span>See all results for "${escapeHtml(query)}"</span>
                            </a>
                        `;
                        footer.style.display = 'block';
                        results.style.display = 'block';
                    } else {
                        list.innerHTML = `<div class="dropdown-item text-muted disabled py-2 px-3">No results found</div>`;
                        footer.style.display = 'none';
                        results.style.display = 'block';
                    }
                })
                .catch(() => {
                    results.style.display = 'none';
                });
            }, 250);
        });

        // Hide on click outside
        document.addEventListener('click', function (e) {
            if (input && results && !input.contains(e.target) && !results.contains(e.target)) {
                results.style.display = 'none';
            }
        });

        // Reopen on focus if query exists
        input.addEventListener('focus', function () {
            if (this.value.trim().length >= 1 && list.children.length > 0) {
                results.style.display = 'block';
            }
        });

        // Hide on Enter keydown
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                results.style.display = 'none';
            }
        });
    }

    // 1. Content Repository
    setupManageAutocomplete({
        inputId: 'manage-content-search-input',
        resultsId: 'manage-content-search-results',
        listId: 'manage-content-autocomplete-list',
        footerId: 'manage-content-search-footer',
        endpoint: '/manage/content/autocomplete',
        targetUrl: '/manage/content',
        badgeText: 'CONTENT',
        badgeClass: 'bg-info-subtle text-info'
    });

    // 2. Playlist & Learning Path
    setupManageAutocomplete({
        inputId: 'manage-playlist-search-input',
        resultsId: 'manage-playlist-search-results',
        listId: 'manage-playlist-autocomplete-list',
        footerId: 'manage-playlist-search-footer',
        endpoint: '/manage/playlist/autocomplete',
        targetUrl: '/manage/playlist',
        badgeText: 'PLAYLIST',
        badgeClass: 'bg-purple-subtle text-purple'
    });

    // 3. Question Bank
    setupManageAutocomplete({
        inputId: 'manage-questionbank-search-input',
        resultsId: 'manage-questionbank-search-results',
        listId: 'manage-questionbank-autocomplete-list',
        footerId: 'manage-questionbank-search-footer',
        endpoint: '/manage/question-bank/autocomplete',
        targetUrl: '/manage/question-bank',
        badgeText: 'QUESTION BANK',
        badgeClass: 'bg-purple-subtle text-purple'
    });
    // Question Bank alias
    setupManageAutocomplete({
        inputId: 'manage-qb-search-input',
        resultsId: 'manage-qb-search-results',
        listId: 'manage-qb-autocomplete-list',
        footerId: 'manage-qb-search-footer',
        endpoint: '/manage/question-bank/autocomplete',
        targetUrl: '/manage/question-bank',
        badgeText: 'QUESTION BANK',
        badgeClass: 'bg-purple-subtle text-purple'
    });

    // 4. News Management
    setupManageAutocomplete({
        inputId: 'manage-news-search-input',
        resultsId: 'manage-news-search-results',
        listId: 'manage-news-autocomplete-list',
        footerId: 'manage-news-search-footer',
        endpoint: '/manage/news/autocomplete',
        targetUrl: '/manage/news',
        badgeText: 'NEWS',
        badgeClass: 'bg-primary-subtle text-primary'
    });

    // 5. Event Photo Gallery
    setupManageAutocomplete({
        inputId: 'manage-eventphoto-search-input',
        resultsId: 'manage-eventphoto-search-results',
        listId: 'manage-eventphoto-autocomplete-list',
        footerId: 'manage-eventphoto-search-footer',
        endpoint: '/manage/eventphoto/autocomplete',
        targetUrl: '/manage/eventphoto',
        badgeText: 'GALLERY',
        badgeClass: 'bg-purple-subtle text-purple'
    });
    // Event Photo alias
    setupManageAutocomplete({
        inputId: 'manage-gallery-search-input',
        resultsId: 'manage-gallery-search-results',
        listId: 'manage-gallery-autocomplete-list',
        footerId: 'manage-gallery-search-footer',
        endpoint: '/manage/eventphoto/autocomplete',
        targetUrl: '/manage/eventphoto',
        badgeText: 'GALLERY',
        badgeClass: 'bg-purple-subtle text-purple'
    });

    // 6. Category & Classification
    setupManageAutocomplete({
        inputId: 'manage-classification-search-input',
        resultsId: 'manage-classification-search-results',
        listId: 'manage-classification-autocomplete-list',
        footerId: 'manage-classification-search-footer',
        endpoint: '/manage/classification/autocomplete',
        targetUrl: '/manage/classification',
        badgeText: 'CATEGORY',
        badgeClass: 'bg-primary-subtle text-primary'
    });
    // Category alias
    setupManageAutocomplete({
        inputId: 'manage-category-search-input',
        resultsId: 'manage-category-search-results',
        listId: 'manage-category-autocomplete-list',
        footerId: 'manage-category-search-footer',
        endpoint: '/manage/classification/autocomplete',
        targetUrl: '/manage/classification',
        badgeText: 'CATEGORY',
        badgeClass: 'bg-primary-subtle text-primary'
    });
});
</script>
