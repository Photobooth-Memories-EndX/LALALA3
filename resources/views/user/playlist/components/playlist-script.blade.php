<script>
    $(function () {
        const $items = $('.playlist-item');

        function filterPlaylist() {
            const keyword = ($('#playlistSearch').val() || '').toLowerCase();
            const sort = $('#playlistSort').val();

            let visibleItems = $items.toArray();

            visibleItems.forEach(function (item) {
                const title = $(item).find('.playlist-title').text().toLowerCase();
                const content = $(item).find('.playlist-content-list').text().toLowerCase();

                const isMatch =
                    title.includes(keyword) ||
                    content.includes(keyword);

                $(item).toggle(isMatch);
            });

            visibleItems = $items.filter(':visible').toArray();

            visibleItems.sort(function (a, b) {
                const titleA = $(a).find('.playlist-title').text().trim().toLowerCase();
                const titleB = $(b).find('.playlist-title').text().trim().toLowerCase();

                if (sort === 'az') {
                    return titleA.localeCompare(titleB);
                }

                if (sort === 'za') {
                    return titleB.localeCompare(titleA);
                }

                return 0;
            });

            $('#playlistGrid').append(visibleItems);
        }

        $('#playlistSearch').on('keyup', function () {
            filterPlaylist();
        });

        $('#playlistSort').on('change', function () {
            filterPlaylist();
        });

        $('#playlistCategoryFilter').on('change', function () {
            filterPlaylist();
        });
    });
</script>
