/**
 * OWL LMS — Document / PDF Reading Time Tracker
 *
 * Tracks actual active reading time (actual_watch_seconds) for PDF and Documents,
 * decoupled from page progress (last_page / total_pages).
 *
 * Core guarantees:
 * 1. Wall-clock measurement: Uses performance.now() / timestamp deltas.
 * 2. Pauses accumulation when tab is hidden (Page Visibility API: document.visibilityState !== 'visible').
 * 3. Does not count time spent while tab is hidden when returning to tab.
 * 4. Navigating pages (forward, backward, scrolling) does not reset or reduce accumulated reading time.
 * 5. Flushes remaining delta on tab hidden, page leave (pagehide / beforeunload), and periodic heartbeat (10s).
 * 6. Server-safe: Generates unique heartbeat_id for server idempotency and clamps deltas.
 * 7. Anti double-counting: delta_seconds is the sole source of actual reading time, independent of page_elapsed_seconds.
 */
(function (window, document) {
    'use strict';

    function DocumentReadingTracker(options) {
        options = options || {};

        this.url = options.url;
        this.csrf = options.csrf;
        this.contentId = options.contentId;
        this.trainingPlanId = options.trainingPlanId || options.trainingplanId || null;
        this.heartbeatIntervalMs = options.heartbeatIntervalMs || 10000;
        this.onUpdate = options.onUpdate || function () {};

        // Internal states
        this._isActive = true;
        this._isVisible = document.visibilityState === 'visible';
        this._accumulatedDelta = 0; // in seconds
        this._lastTickTime = this._now();
        this._heartbeatTimer = null;
        this._destroyed = false;

        this._bindEvents();
        this.startHeartbeat();
    }

    DocumentReadingTracker.prototype._now = function () {
        return (window.performance && window.performance.now)
            ? window.performance.now()
            : Date.now();
    };

    DocumentReadingTracker.prototype._generateUuid = function () {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    };

    /**
     * Measure elapsed wall-clock time since last tick and accumulate if actively reading.
     */
    DocumentReadingTracker.prototype._tick = function () {
        var now = this._now();

        if (this._lastTickTime !== null) {
            var elapsedSeconds = (now - this._lastTickTime) / 1000;

            // Only accumulate while tracker is active and tab is visible
            if (this._isActive && this._isVisible) {
                // Clamping safeguard against system sleep / background throttle jump
                if (elapsedSeconds > 0 && elapsedSeconds <= 30) {
                    this._accumulatedDelta += elapsedSeconds;
                }
            }
        }

        this._lastTickTime = now;
    };

    DocumentReadingTracker.prototype.startHeartbeat = function () {
        this.stopHeartbeat();
        var self = this;
        this._heartbeatTimer = setInterval(function () {
            self._onHeartbeat();
        }, this.heartbeatIntervalMs);
    };

    DocumentReadingTracker.prototype.stopHeartbeat = function () {
        if (this._heartbeatTimer) {
            clearInterval(this._heartbeatTimer);
            this._heartbeatTimer = null;
        }
    };

    DocumentReadingTracker.prototype._onHeartbeat = function () {
        this._tick();

        // Send heartbeat if at least 1 second has been accumulated
        if (this._accumulatedDelta >= 1.0) {
            this.flush('in_progress');
        }
    };

    DocumentReadingTracker.prototype.pause = function () {
        this._tick();
        this._isActive = false;
    };

    DocumentReadingTracker.prototype.resume = function () {
        this._lastTickTime = this._now();
        this._isActive = true;
    };

    /**
     * Flush accumulated delta to the server.
     */
    DocumentReadingTracker.prototype.flush = function (status, isUnload) {
        this._tick();

        var deltaToSend = this._accumulatedDelta;
        var intDelta = Math.round(deltaToSend);

        // Skip flush if no delta accumulated and no explicit status to report
        if (intDelta <= 0 && !status) {
            return Promise.resolve(null);
        }

        // Deduct the integer seconds being sent; keep any fractional remainder for next tick
        if (intDelta > 0) {
            this._accumulatedDelta = Math.max(0, this._accumulatedDelta - intDelta);
        }

        var payload = {
            content_id: this.contentId,
            type: 'document',
            delta_seconds: intDelta,
            heartbeat_id: this._generateUuid()
        };

        if (this.trainingPlanId) {
            payload.trainingplan_id = this.trainingPlanId;
        }

        if (status) {
            payload.status = status;
        }

        var self = this;

        return fetch(this.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrf
            },
            body: JSON.stringify(payload),
            keepalive: true
        })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (result) {
                if (result && result.success && typeof self.onUpdate === 'function') {
                    self.onUpdate(result);
                }
                return result;
            })
            .catch(function () {
                // Non-fatal network handling
                return null;
            });
    };

    DocumentReadingTracker.prototype._bindEvents = function () {
        var self = this;

        // Page Visibility API: freeze reading time when user switches tabs
        this._onVisibilityChange = function () {
            self._tick();
            self._isVisible = (document.visibilityState === 'visible');

            if (!self._isVisible) {
                self.flush('in_progress');
            } else {
                // Reset baseline tick time when returning so hidden time is never counted
                self._lastTickTime = self._now();
            }
        };
        document.addEventListener('visibilitychange', this._onVisibilityChange);

        // Page hide / unload: flush remaining reading time before leaving page
        this._onPageLeave = function () {
            self._tick();
            self.flush('in_progress', true);
        };
        window.addEventListener('pagehide', this._onPageLeave);
        window.addEventListener('beforeunload', this._onPageLeave);
    };

    DocumentReadingTracker.prototype.destroy = function () {
        this.stopHeartbeat();
        this._destroyed = true;
        this._isActive = false;

        if (this._onVisibilityChange) {
            document.removeEventListener('visibilitychange', this._onVisibilityChange);
        }
        if (this._onPageLeave) {
            window.removeEventListener('pagehide', this._onPageLeave);
            window.removeEventListener('beforeunload', this._onPageLeave);
        }
    };

    window.DocumentReadingTracker = DocumentReadingTracker;
})(window, document);
