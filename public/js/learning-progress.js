/**
 * OWL LMS — auto learning progress tracker (MVP).
 *
 * Sends raw reading/playback position to the backend; the backend recomputes
 * and returns the authoritative progress. No build step required.
 */
(function (window, document) {
    'use strict';

    function OwlLearningProgress(options) {
        this.url = options.url;
        this.csrf = options.csrf;
        this.contentId = options.contentId;
        this.type = options.type; // 'video' | 'document'
        this.onUpdate = options.onUpdate || function () {};

        this.throttleMs = options.throttleMs || 12000;
        this._lastSentAt = 0;
        this._debounceTimer = null;
    }

    /**
     * Send progress immediately (force) or at most once per throttle window.
     */
    OwlLearningProgress.prototype.send = function (payload, force) {
        var now = Date.now();

        if (!force && now - this._lastSentAt < this.throttleMs) {
            return;
        }

        this._lastSentAt = now;

        var self = this;
        var data = { content_id: this.contentId, type: this.type };

        for (var key in payload) {
            if (Object.prototype.hasOwnProperty.call(payload, key) && payload[key] !== null && payload[key] !== undefined) {
                data[key] = payload[key];
            }
        }

        fetch(this.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrf
            },
            body: JSON.stringify(data),
            keepalive: true
        })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (result) {
                if (result && result.success) {
                    self.onUpdate(result);
                }
            })
            .catch(function () {
                /* network errors are non-fatal for tracking */
            });
    };

    /**
     * Debounced send — used for rapid events like PDF page flips.
     */
    OwlLearningProgress.prototype.sendDebounced = function (payload, delayMs) {
        var self = this;

        if (this._debounceTimer) {
            clearTimeout(this._debounceTimer);
        }

        this._debounceTimer = setTimeout(function () {
            self._debounceTimer = null;
            self.send(payload, true);
        }, delayMs || 1200);
    };

    /**
     * Shared UI updater: refreshes whichever progress elements exist on the
     * current page (single content sidebar and/or playlist lesson sidebar).
     */
    function owlUpdateLearningUi(data) {
        var progress = parseInt(data.progress, 10) || 0;

        // Single content page sidebar
        var value = document.getElementById('contentProgressValue');
        var bar = document.getElementById('contentProgressBar');
        var statusBadge = document.getElementById('contentProgressStatus');

        if (value) {
            value.textContent = progress + '%';
        }

        if (bar) {
            bar.style.width = progress + '%';
        }

        if (statusBadge) {
            var labels = {
                not_started: 'Not Started',
                in_progress: 'In Progress',
                paused: 'Paused',
                completed: 'Completed'
            };
            statusBadge.textContent = labels[data.status] || 'In Progress';
        }

        // Hidden legacy input used by the old resume logic
        var legacyProgress = document.getElementById('player_progress');
        if (legacyProgress) {
            legacyProgress.value = progress;
        }

        // Playlist sidebar — active lesson item
        var item = document.querySelector('.playlist-lesson-item.is-active');
        if (item) {
            var lessonBar = item.querySelector('.lesson-progress .progress-bar');
            var lessonMeta = item.querySelector('.lesson-meta');

            if (lessonBar) {
                lessonBar.style.width = progress + '%';
            }

            if (lessonMeta) {
                lessonMeta.textContent = progress + '%';
            }

            var assessmentAction = item.querySelector('[data-assessment-action]');
            if (assessmentAction) {
                var requiredProgress = parseInt(assessmentAction.dataset.requiredProgress, 10);
                var assessmentEnabled = Number.isFinite(requiredProgress) && progress >= requiredProgress;
                assessmentAction.classList.toggle('disabled', !assessmentEnabled);
                assessmentAction.setAttribute('aria-disabled', assessmentEnabled ? 'false' : 'true');

                if (assessmentEnabled) {
                    owlSyncAssessmentTooltip(assessmentAction, false);
                    assessmentAction.setAttribute('href', assessmentAction.dataset.assessmentUrl);
                    assessmentAction.removeAttribute('tabindex');
                    assessmentAction.removeAttribute('data-bs-toggle');
                    assessmentAction.removeAttribute('data-bs-placement');
                    assessmentAction.removeAttribute('title');
                } else {
                    assessmentAction.removeAttribute('href');
                    assessmentAction.setAttribute('tabindex', '-1');
                    assessmentAction.setAttribute('data-bs-toggle', 'tooltip');
                    assessmentAction.setAttribute('data-bs-placement', 'top');
                    assessmentAction.setAttribute('title', assessmentAction.dataset.assessmentLockedTitle);
                    owlSyncAssessmentTooltip(assessmentAction, true);
                }
            }

            if (progress >= 100) {
                var playIcon = item.querySelector('.lesson-play-icon');
                if (playIcon) {
                    playIcon.classList.remove(
                        'lesson-play-icon',
                        'lesson-status--not-started',
                        'lesson-status--in-progress'
                    );
                    playIcon.classList.add('lesson-done-icon');
                    playIcon.setAttribute('aria-label', 'Completed');
                    playIcon.setAttribute('title', 'Completed');
                    playIcon.innerHTML = '<i class="ti ti-circle-check-filled" aria-hidden="true"></i>';
                }
            }
        }
    }

    function owlSyncAssessmentTooltip(element, shouldEnable) {
        if (!window.bootstrap || !window.bootstrap.Tooltip) {
            return;
        }

        var tooltip = window.bootstrap.Tooltip.getInstance(element);

        if (tooltip) {
            tooltip.dispose();
        }

        if (shouldEnable) {
            new window.bootstrap.Tooltip(element);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var assessmentActions = document.querySelectorAll(
            '[data-assessment-action][aria-disabled="true"]'
        );

        assessmentActions.forEach(function (assessmentAction) {
            owlSyncAssessmentTooltip(assessmentAction, true);
        });
    });

    document.addEventListener('click', function (event) {
        var assessmentAction = event.target.closest(
            '[data-assessment-action][aria-disabled="true"]'
        );

        if (assessmentAction) {
            event.preventDefault();
        }
    });

    window.OwlLearningProgress = OwlLearningProgress;
    window.owlUpdateLearningUi = owlUpdateLearningUi;
})(window, document);
