(function(){
    // debounce helper
    function debounce(fn, ms){
        let t;
        return function(...args){
            clearTimeout(t);
            t = setTimeout(()=> fn.apply(this, args), ms);
        };
    }

    document.addEventListener('DOMContentLoaded', function(){
        // auto-submit search after pause
        const searchInput = document.getElementById('owl-search-input');
        if(searchInput){
            const form = searchInput.closest('form');
            const submit = debounce(()=> { if(form) form.submit(); }, 600);
            searchInput.addEventListener('input', submit);

            // focus shortcut: press '/' to focus search
            document.addEventListener('keydown', function(e){
                if(e.key === '/' && document.activeElement.tagName.toLowerCase() !== 'input'){
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
            });
        }

        // initialize bootstrap tooltips if available
        if(typeof bootstrap !== 'undefined'){
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // keyboard accessible save buttons
        document.querySelectorAll('.owl-save-btn').forEach(function(btn){
            btn.setAttribute('aria-pressed','false');
            btn.addEventListener('click', function(){
                const pressed = btn.getAttribute('aria-pressed') === 'true';
                btn.setAttribute('aria-pressed', (!pressed).toString());
            });
        });
    });
})();
