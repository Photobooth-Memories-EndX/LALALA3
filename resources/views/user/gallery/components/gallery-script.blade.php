<script>
    $(function () {
        const $dateInput = $('#gallery-eventdate');
        const $dateTrigger = $('.gallery-date-trigger');

        $dateTrigger.on('click', function () {
            const dateInput = $dateInput.get(0);

            if (!dateInput) {
                return;
            }

            if (typeof dateInput.showPicker === 'function') {
                dateInput.showPicker();
            } else {
                dateInput.click();
            }
        });

        $dateInput.on('change', function () {
            const hasDate = Boolean(this.value);

            $dateTrigger.toggleClass('is-active', hasDate);
            $dateTrigger.attr(
                'title',
                hasDate ? `Event Date: ${this.value}` : 'Event Date'
            );
        });
    });
</script>
