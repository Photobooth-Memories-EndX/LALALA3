<script>
$(function () {
    function toggleFields(form) {
        const required = form.find('.certificate-assessment-required').is(':checked');
        const type = form.find('.certificate-type-select').val();
        form.find('.certificate-assessment-fields').toggle(required);
        form.find('.certificate-minimum-score').prop('required', required);
        form.find('.certificate-custom-template-fields')
            .toggle(!form.find('.certificate-default-template').is(':checked'));
        form.find('.certificate-progress-field').toggle(type !== 'global');
        form.find('[name="minimum_progress"]').prop('required', type !== 'global');
        form.find('.certificate-playlist-contents').toggle(type === 'playlist');
    }

    function selectedTarget(form, type) {
        const select = form.find('.certificate-item-select');
        if (type === 'content') return select.data('selected-content');
        if (type === 'playlist') return select.data('selected-trainingplan');
        return select.data('selected-assessment');
    }

    function renderPlaylist(form) {
        const items = form.find('.certificate-item-select').data('playlist-items') || [];
        const id = form.find('.certificate-item-select').val();
        const playlist = items.find(item => String(item.id) === String(id));
        const list = form.find('.certificate-playlist-content-list').empty();
        const contents = playlist ? playlist.contents : [];
        if (!contents || !contents.length) {
            $('<li>', {class: 'text-muted', text: 'Playlist belum memiliki content.'}).appendTo(list);
            return;
        }
        contents.forEach(content => $('<li>', {text: content.title}).appendTo(list));
    }

    function loadAssessments(form) {
        const select = form.find('.certificate-assessment-select');
        select.html('<option value="">Loading...</option>');
        $.get(@json(route('certificate.setting.ajax.assessments')), {
            type: form.find('.certificate-type-select').val(),
            item_id: form.find('.certificate-item-select').val()
        }).done(function (items) {
            const selectedId = select.data('selected');
            select.html('<option value="">Pilih assessment</option>');
            items.forEach(function (item) {
                $('<option>', {
                    value: item.id,
                    text: (item.matched_by_title ? '[Matched by title] ' : '') + item.title,
                    selected: String(item.id) === String(selectedId)
                }).appendTo(select);
            });
        }).fail(() => select.html('<option value="">Gagal memuat assessment</option>'));
    }

    function loadItems(form) {
        const type = form.find('.certificate-type-select').val();
        const select = form.find('.certificate-item-select');
        const selectedId = selectedTarget(form, type);
        form.find('.certificate-item-label').text(
            type === 'content' ? 'Nama Content' : (type === 'playlist' ? 'Nama Playlist' : 'Nama Global Assessment')
        );
        select.html('<option value="">Loading...</option>');
        $.get(@json(route('certificate.setting.ajax.items')), {type: type}).done(function (items) {
            select.html('<option value="">Pilih data</option>');
            items.forEach(item => $('<option>', {
                value: item.id,
                text: item.title,
                selected: String(item.id) === String(selectedId)
            }).appendTo(select));
            select.data('playlist-items', type === 'playlist' ? items : []);
            renderPlaylist(form);
            loadAssessments(form);
        }).fail(() => select.html('<option value="">Gagal memuat data</option>'));
    }

    function initialize(form) {
        toggleFields(form);
        loadItems(form);
    }

    $('.modal form').each(function () { initialize($(this)); });
    $(document).on('certificate:reload', '.modal form', function () { initialize($(this)); });

    $(document).on('change', '.certificate-type-select', function () {
        const form = $(this).closest('form');
        form.find('.certificate-item-select')
            .data('selected-content', '').data('selected-trainingplan', '').data('selected-assessment', '');
        form.find('.certificate-assessment-select').data('selected', '');
        toggleFields(form);
        loadItems(form);
    });
    $(document).on('change', '.certificate-item-select', function () {
        const form = $(this).closest('form');
        form.find('.certificate-assessment-select').data('selected', '');
        renderPlaylist(form);
        loadAssessments(form);
    });
    $(document).on('change', '.certificate-assessment-required, .certificate-default-template', function () {
        toggleFields($(this).closest('form'));
    });
});
</script>
