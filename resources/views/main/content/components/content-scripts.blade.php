<script src="{{ asset('js/pdf.min.js') }}"></script>
<script>
    var pdfjsLib = window["pdfjs-dist/build/pdf"];
    if (pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/pdf.worker.min.js') }}";
    }
</script>
<script src="{{ asset('js/sync-content-document-total-page.js') }}?v={{ filemtime(public_path('js/sync-content-document-total-page.js')) }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new SyncContentDocumentTotalPage({
            buttonId: 'btnSyncPdfTotalPage',
            modalId: 'modalTotalPageSynchronization',
            executeBtnId: 'btnExecuteSyncTotalPage',
            cancelBtnId: 'btnCancelSyncTotalPage',
            closeBtnId: 'btnCloseSyncTotalPage',
            headerCloseBtnId: 'syncTotalPageCloseHeaderBtn',
            fetchListUrl: "{{ route('content.document-pdf-list') }}",
            syncUrl: "{{ route('content.sync-total-page') }}",
            csrfToken: "{{ csrf_token() }}",
            workerSrc: "{{ asset('js/pdf.worker.min.js') }}"
        });

        // Autocomplete is handled by standardized initManageAutocomplete

        // Synchronize Learning Hours Handler
        const btnExecuteSyncLh = document.getElementById('btnExecuteSyncLh');
        const syncLhPromptBody = document.getElementById('syncLhPromptBody');
        const syncLhLoadingBody = document.getElementById('syncLhLoadingBody');
        const syncLhResultBody = document.getElementById('syncLhResultBody');
        const syncLhErrorContainer = document.getElementById('syncLhErrorContainer');
        const syncLhErrorText = document.getElementById('syncLhErrorText');
        const btnCancelSyncLh = document.getElementById('btnCancelSyncLh');
        const btnDoneSyncLh = document.getElementById('btnDoneSyncLh');
        const syncLhCloseHeaderBtn = document.getElementById('syncLhCloseHeaderBtn');
        const modalEl = document.getElementById('modalSyncLearningHours');

        if (btnExecuteSyncLh) {
            btnExecuteSyncLh.addEventListener('click', function () {
                const syncUrl = btnExecuteSyncLh.getAttribute('data-url');
                if (!syncUrl) return;

                // Set Loading State
                btnExecuteSyncLh.disabled = true;
                btnExecuteSyncLh.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Syncing Learning Hours...';
                if (btnCancelSyncLh) btnCancelSyncLh.style.display = 'none';
                if (syncLhCloseHeaderBtn) syncLhCloseHeaderBtn.style.display = 'none';
                if (syncLhPromptBody) syncLhPromptBody.style.display = 'none';
                if (syncLhErrorContainer) syncLhErrorContainer.style.display = 'none';
                if (syncLhLoadingBody) syncLhLoadingBody.style.display = 'block';

                $.ajax({
                    url: syncUrl,
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                }).done(function (res) {
                    if (res && res.success && res.data) {
                        if (syncLhLoadingBody) syncLhLoadingBody.style.display = 'none';
                        if (syncLhResultBody) syncLhResultBody.style.display = 'block';

                        const resContentTotal = document.getElementById('resContentTotal');
                        const resContentUpdated = document.getElementById('resContentUpdated');
                        const resContentSkipped = document.getElementById('resContentSkipped');
                        const resPlaylistTotal = document.getElementById('resPlaylistTotal');
                        const resPlaylistUpdated = document.getElementById('resPlaylistUpdated');
                        const resPlaylistSkipped = document.getElementById('resPlaylistSkipped');

                        if (resContentTotal) resContentTotal.textContent = res.data.content_total || 0;
                        if (resContentUpdated) resContentUpdated.textContent = res.data.content_updated || 0;
                        if (resContentSkipped) resContentSkipped.textContent = res.data.content_skipped || 0;

                        if (resPlaylistTotal) resPlaylistTotal.textContent = res.data.playlist_total || 0;
                        if (resPlaylistUpdated) resPlaylistUpdated.textContent = res.data.playlist_updated || 0;
                        if (resPlaylistSkipped) resPlaylistSkipped.textContent = res.data.playlist_skipped || 0;

                        btnExecuteSyncLh.style.display = 'none';
                        if (btnDoneSyncLh) btnDoneSyncLh.style.display = 'inline-block';
                        if (syncLhCloseHeaderBtn) syncLhCloseHeaderBtn.style.display = 'block';
                    } else {
                        handleSyncError(res ? res.message : 'Unknown synchronization error occurred.');
                    }
                }).fail(function (xhr) {
                    let errMsg = 'Failed to execute synchronization.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    handleSyncError(errMsg);
                });

                function handleSyncError(message) {
                    if (syncLhLoadingBody) syncLhLoadingBody.style.display = 'none';
                    if (syncLhPromptBody) syncLhPromptBody.style.display = 'block';
                    if (syncLhErrorContainer) {
                        syncLhErrorContainer.style.display = 'block';
                        if (syncLhErrorText) syncLhErrorText.textContent = message;
                    }
                    btnExecuteSyncLh.disabled = false;
                    btnExecuteSyncLh.innerHTML = '<i class="ti ti-refresh me-1"></i> Synchronize Learning Hours';
                    if (btnCancelSyncLh) btnCancelSyncLh.style.display = 'inline-block';
                    if (syncLhCloseHeaderBtn) syncLhCloseHeaderBtn.style.display = 'block';
                }
            });
        }

        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                // Reset or reload when modal is closed after completed sync
                if (btnDoneSyncLh && btnDoneSyncLh.style.display !== 'none') {
                    window.location.reload();
                }
            });
        }

        // View Content Detail Handler (Zero Native Alert, 100% In-Modal States)
        const contentDetailModalEl = document.getElementById('contentDetailModal');

        function showContentDetailModal() {
            if (!contentDetailModalEl) {
                return;
            }

            if (
                window.bootstrap &&
                window.bootstrap.Modal &&
                typeof window.bootstrap.Modal.getOrCreateInstance === 'function'
            ) {
                window.bootstrap.Modal
                    .getOrCreateInstance(contentDetailModalEl)
                    .show();
                return;
            }

            if (
                window.bootstrap &&
                typeof window.bootstrap.Modal === 'function'
            ) {
                new window.bootstrap.Modal(contentDetailModalEl).show();
                return;
            }

            if (
                typeof $ !== 'undefined' &&
                $.fn &&
                typeof $.fn.modal === 'function'
            ) {
                $(contentDetailModalEl).modal('show');
                return;
            }

            // Last-resort fallback only if no modal API exists.
            contentDetailModalEl.style.display = 'block';
            contentDetailModalEl.classList.add('show');
        }

        const detailLoadingEl = document.getElementById('contentDetailLoading');
        const detailErrorEl = document.getElementById('contentDetailError');
        const detailContentEl = document.getElementById('contentDetailContent');
        const detailErrorMsgEl = document.getElementById('contentDetailErrorMessage');
        const btnRetryDetail = document.getElementById('btnRetryContentDetail');
        const btnRetryText = document.getElementById('btnRetryText');
        const btnRetrySpinner = document.getElementById('btnRetrySpinner');
        let currentDetailId = null;

        function safeVal(val, fallback) {
            if (fallback === undefined) fallback = '-';
            if (val === null || val === undefined) return fallback;
            const str = String(val).trim();
            if (str === '' || str === 'null' || str === 'undefined' || str === 'NaN') return fallback;
            return str;
        }

        function isNumericVal(val) {
            return val !== null && val !== undefined && val !== '' && !isNaN(Number(val));
        }

        function loadContentDetail(contentId) {
            currentDetailId = contentId;
            showContentDetailModal();

            // In-modal loading state: show spinner, hide content and error
            if (detailLoadingEl) detailLoadingEl.style.display = 'block';
            if (detailErrorEl) detailErrorEl.style.display = 'none';
            if (detailContentEl) detailContentEl.style.display = 'none';

            const url = "{{ url('/manage/content') }}/" + encodeURIComponent(contentId) + "/details";

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json'
                }
            }).done(function (res) {
                if (res && res.success && res.data) {
                    renderContentDetail(res.data);
                    if (detailLoadingEl) detailLoadingEl.style.display = 'none';
                    if (detailErrorEl) detailErrorEl.style.display = 'none';
                    if (detailContentEl) detailContentEl.style.display = 'block';
                } else {
                    const errMsg = (res && res.message) ? res.message : 'Unable to load content details. Please try again.';
                    showDetailError(errMsg);
                }
            }).fail(function (xhr, textStatus) {
                let msg = 'Unable to load content details. Please try again.';
                if (xhr.status === 403) {
                    msg = 'You do not have permission to view this content.';
                } else if (xhr.status === 404) {
                    msg = 'Content not found.';
                } else if (xhr.status === 500) {
                    msg = 'Unable to load content details. Please try again.';
                } else if (xhr.status === 0 || textStatus === 'timeout' || (!xhr.status && textStatus === 'error')) {
                    msg = 'Unable to connect to the server. Please try again.';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showDetailError(msg);
            }).always(function () {
                // Reset retry button state
                if (btnRetryDetail) {
                    btnRetryDetail.disabled = false;
                }
                if (btnRetryText) {
                    btnRetryText.textContent = 'Retry';
                }
                if (btnRetrySpinner) {
                    btnRetrySpinner.classList.remove('ti-spin');
                }
            });
        }

        function showDetailError(msg) {
            if (detailLoadingEl) detailLoadingEl.style.display = 'none';
            if (detailContentEl) detailContentEl.style.display = 'none';
            if (detailErrorEl) detailErrorEl.style.display = 'block';
            if (detailErrorMsgEl) detailErrorMsgEl.textContent = msg || 'Unable to load content details. Please try again.';
        }

        // Retry button listener (attached once, zero duplicate listeners)
        if (btnRetryDetail) {
            btnRetryDetail.addEventListener('click', function (e) {
                e.preventDefault();
                if (currentDetailId) {
                    btnRetryDetail.disabled = true;
                    if (btnRetryText) btnRetryText.textContent = 'Retrying...';
                    if (btnRetrySpinner) btnRetrySpinner.classList.add('ti-spin');
                    loadContentDetail(currentDetailId);
                }
            });
        }

        function renderContentDetail(data) {
            if (!data) return;

            const titleEl = document.getElementById('detailTitle');
            const subTitleEl = document.getElementById('contentDetailModalSubtitle');
            const typeBadgeEl = document.getElementById('detailTypeBadge');
            const statusBadgeEl = document.getElementById('detailStatusBadge');
            const classBadgeEl = document.getElementById('detailClassificationBadge');
            const viewsEl = document.getElementById('detailViews');
            const durationEl = document.getElementById('detailDuration');
            const lhEl = document.getElementById('detailLearningHour');
            const descEl = document.getElementById('detailDescription');
            const authorEl = document.getElementById('detailAuthor');
            const dateEl = document.getElementById('detailDate');
            const examEl = document.getElementById('detailExam');
            const playlistEl = document.getElementById('detailPlaylist');
            const deadlineEl = document.getElementById('detailDeadline');
            const docSectionEl = document.getElementById('detailDocumentSection');
            const totalPageEl = document.getElementById('detailTotalPage');
            const pageDurEl = document.getElementById('detailPageDuration');
            const editBtnEl = document.getElementById('btnEditContentShortcut');
            const playlistsContainer = document.getElementById('detailAssociatedPlaylistsContainer');
            const playlistsList = document.getElementById('detailPlaylistsList');

            if (titleEl) titleEl.textContent = safeVal(data.title);
            if (subTitleEl) {
                const idText = safeVal(data.id, '');
                const slugText = data.slug ? ' • ' + data.slug : '';
                subTitleEl.textContent = idText ? ('Content #' + idText + slugText) : 'Content Details';
            }
            if (typeBadgeEl) typeBadgeEl.textContent = safeVal(data.type_label || data.type, 'Content');
            if (statusBadgeEl) {
                const statusText = safeVal(data.status, 'Inactive');
                statusBadgeEl.textContent = statusText;
                statusBadgeEl.className = 'badge ' + (statusText === 'Active' ? 'bg-success-lt' : 'bg-warning-lt');
            }
            if (classBadgeEl) classBadgeEl.textContent = safeVal(data.classification);
            if (viewsEl) viewsEl.textContent = isNumericVal(data.views) ? (data.views_formatted || Number(data.views).toLocaleString()) : '0';
            if (durationEl) durationEl.textContent = safeVal(data.duration_formatted || data.duration, '00:00:00');
            if (lhEl) {
                if (data.learning_hours !== null && data.learning_hours !== undefined && !isNaN(data.learning_hours)) {
                    lhEl.textContent = data.learning_hours_formatted || (Number(data.learning_hours).toFixed(4) + ' h');
                } else {
                    lhEl.textContent = '-';
                }
            }
            if (descEl) descEl.textContent = safeVal(data.description, 'No description provided.');
            if (authorEl) authorEl.textContent = safeVal(data.author);
            if (dateEl) dateEl.textContent = safeVal(data.date);
            if (examEl) {
                if (data.exam === 'OK') {
                    examEl.textContent = 'With Exam (OK)';
                } else if (data.exam === 'NOT') {
                    examEl.textContent = 'Without Exam';
                } else {
                    examEl.textContent = safeVal(data.exam_label);
                }
            }
            if (playlistEl) {
                if (data.playlistonly === 'Y') {
                    playlistEl.textContent = 'Playlist Only';
                } else if (data.playlistonly === 'N') {
                    playlistEl.textContent = 'General Content';
                } else {
                    playlistEl.textContent = safeVal(data.playlist_label);
                }
            }

            if (deadlineEl) {
                if (data.has_deadline && data.from_date && data.to_date && data.from_date !== '-' && data.to_date !== '-') {
                    deadlineEl.textContent = data.from_date + ' – ' + data.to_date + (data.is_expired ? ' (Expired)' : '');
                } else {
                    deadlineEl.textContent = 'No Deadline';
                }
            }

            if (docSectionEl) {
                const isDoc = data.type === 'Document' || (isNumericVal(data.total_page) && Number(data.total_page) > 0) || (isNumericVal(data.page_duration) && Number(data.page_duration) > 0);
                if (isDoc) {
                    docSectionEl.style.display = 'block';
                    if (totalPageEl) totalPageEl.textContent = (isNumericVal(data.total_page) && Number(data.total_page) > 0) ? (data.total_page + ' pages') : '-';
                    if (pageDurEl) pageDurEl.textContent = (isNumericVal(data.page_duration) && Number(data.page_duration) > 0) ? (data.page_duration + ' seconds / page') : '-';
                } else {
                    docSectionEl.style.display = 'none';
                }
            }

            if (playlistsContainer && playlistsList) {
                if (Array.isArray(data.playlists) && data.playlists.length > 0) {
                    playlistsContainer.style.display = 'block';
                    playlistsList.innerHTML = '';
                    data.playlists.forEach(function (p) {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-purple-lt';
                        badge.textContent = safeVal(p.title, 'Playlist #' + (p.id || ''));
                        playlistsList.appendChild(badge);
                    });
                } else {
                    playlistsContainer.style.display = 'none';
                    playlistsList.innerHTML = '';
                }
            }

            if (editBtnEl) {
                editBtnEl.href = data.edit_url || (data.slug ? ('{{ url("/manage/content") }}/' + encodeURIComponent(data.slug) + '/edit') : '#');
            }
        }

        // Event delegation for View Detail buttons
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-view-content-detail');
            if (!btn) return;
            e.preventDefault();
            const contentId = btn.getAttribute('data-id');
            if (contentId) {
                loadContentDetail(contentId);
            }
        });

        // Delete Content Confirmation Handler (100% Tabler Modal, Zero Native confirm)
        let activeDeleteForm = null;
        const deleteModalEl = document.getElementById('deleteContentModal');
        const deleteItemTitleEl = document.getElementById('deleteContentItemTitle');
        const deleteItemNameContainer = document.getElementById('deleteContentItemNameContainer');
        const btnConfirmDelete = document.getElementById('btnConfirmDeleteContent');
        const btnConfirmDeleteText = document.getElementById('btnConfirmDeleteText');
        const btnConfirmDeleteIcon = document.getElementById('btnConfirmDeleteIcon');
        const btnCancelDelete = document.getElementById('btnCancelDeleteContent');

        function showDeleteContentModal() {
            if (!deleteModalEl) return;

            if (
                window.bootstrap &&
                window.bootstrap.Modal &&
                typeof window.bootstrap.Modal.getOrCreateInstance === 'function'
            ) {
                window.bootstrap.Modal.getOrCreateInstance(deleteModalEl).show();
                return;
            }

            if (
                window.bootstrap &&
                typeof window.bootstrap.Modal === 'function'
            ) {
                new window.bootstrap.Modal(deleteModalEl).show();
                return;
            }

            if (
                typeof $ !== 'undefined' &&
                $.fn &&
                typeof $.fn.modal === 'function'
            ) {
                $(deleteModalEl).modal('show');
                return;
            }

            // Fallback
            deleteModalEl.style.display = 'block';
            deleteModalEl.classList.add('show');
        }

        // Delegated event listener for delete trigger buttons across pagination & filters
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-trigger-delete');
            if (!btn) return;

            e.preventDefault();
            activeDeleteForm = btn.closest('form');

            const title = btn.getAttribute('data-title') || '';
            if (deleteItemTitleEl && deleteItemNameContainer) {
                if (title.trim()) {
                    deleteItemTitleEl.textContent = title;
                    deleteItemNameContainer.style.display = 'block';
                } else {
                    deleteItemNameContainer.style.display = 'none';
                }
            }

            // Reset confirm button state
            if (btnConfirmDelete) {
                btnConfirmDelete.disabled = false;
            }
            if (btnConfirmDeleteText) {
                btnConfirmDeleteText.textContent = 'Delete';
            }
            if (btnConfirmDeleteIcon) {
                btnConfirmDeleteIcon.className = 'ti ti-trash me-1';
            }
            if (btnCancelDelete) {
                btnCancelDelete.disabled = false;
            }

            showDeleteContentModal();
        });

        // Confirm delete button click: submit the active existing delete form
        if (btnConfirmDelete) {
            btnConfirmDelete.addEventListener('click', function () {
                if (!activeDeleteForm) return;

                // Double submit protection
                btnConfirmDelete.disabled = true;
                if (btnConfirmDeleteText) btnConfirmDeleteText.textContent = 'Deleting...';
                if (btnConfirmDeleteIcon) btnConfirmDeleteIcon.className = 'spinner-border spinner-border-sm me-1';
                if (btnCancelDelete) btnCancelDelete.disabled = true;

                activeDeleteForm.submit();
            });
        }
    });
</script>
