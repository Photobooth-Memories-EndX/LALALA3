/**
 * OWL LMS — Video Watch Time Tracker (MVP)
 *
 * Tracks actual video watch time (actual_watch_seconds) separated from
 * playhead position (current_time).
 *
 * Core guarantees:
 * 1. Wall-clock measurement: Uses performance.now() / timestamp deltas, NOT player.currentTime.
 * 2. Seeking forward does not inflate actual watch time.
 * 3. Seeking backward does not decrease actual watch time.
 * 4. Replaying a video accumulates watch time without cap.
 * 5. Pauses accumulation when tab is hidden (Page Visibility API).
 * 6. Flushes remaining delta on pause, ended, visibilitychange, and pagehide/beforeunload.
 * 7. Server-safe: Generates unique heartbeat_id for server idempotency and clamps deltas.
 */
(function (window, document) {
    'use strict';

    function VideoWatchTimeTracker(options) {
        options = options || {};

        this.player = options.player;
        this.url = options.url;
        this.csrf = options.csrf;
        this.contentId = options.contentId;
        this.trainingPlanId = options.trainingPlanId || options.trainingplanId || null;
        this.heartbeatIntervalMs = options.heartbeatIntervalMs || 10000;
        this.onUpdate = options.onUpdate || function () {};

        this.maxActualWatchSeconds = (typeof options.maxActualWatchSeconds === 'number') ? options.maxActualWatchSeconds : null;
        this.accumulatedWatchSeconds = (typeof options.accumulatedWatchSeconds === 'number')
            ? options.accumulatedWatchSeconds
            : (typeof options.initialActualWatchSeconds === 'number' ? options.initialActualWatchSeconds : 0);
        this.isCapped = options.isCapped === true || (this.maxActualWatchSeconds !== null && this.accumulatedWatchSeconds >= this.maxActualWatchSeconds);

        // Internal states
        this._isPlaying = false;
        this._isSeeking = false;
        this._isVisible = document.visibilityState === 'visible';
        this._accumulatedDelta = 0; // in seconds
        this._lastTickTime = null;
        this._heartbeatTimer = null;
        this._destroyed = false;

        this._bindEvents();
    }

    VideoWatchTimeTracker.prototype._now = function () {
        return (window.performance && window.performance.now)
            ? window.performance.now()
            : Date.now();
    };

    VideoWatchTimeTracker.prototype._generateUuid = function () {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    };

    VideoWatchTimeTracker.prototype._getCurrentTime = function () {
        if (!this.player) return 0;
        if (typeof this.player.currentTime === 'number') {
            return this.player.currentTime;
        }
        if (this.player.media && typeof this.player.media.currentTime === 'number') {
            return this.player.media.currentTime;
        }
        return 0;
    };

    VideoWatchTimeTracker.prototype._getDuration = function () {
        if (!this.player) return 0;
        if (typeof this.player.duration === 'number') {
            return this.player.duration;
        }
        if (this.player.media && typeof this.player.media.duration === 'number') {
            return this.player.media.duration;
        }
        return 0;
    };

    /**
     * Measure elapsed wall-clock time since last tick and accumulate if actively playing.
     */
    VideoWatchTimeTracker.prototype._tick = function () {
        var now = this._now();

        if (this._lastTickTime !== null) {
            var elapsedSeconds = (now - this._lastTickTime) / 1000;

            // Only accumulate while video is playing, tab is visible, not seeking, and not capped
            if (this._isPlaying && !this._isSeeking && this._isVisible && !this.isCapped) {
                // Clamping safeguard against system sleep / background throttle jump
                if (elapsedSeconds > 0 && elapsedSeconds <= 30) {
                    if (this.maxActualWatchSeconds !== null) {
                        var remainingBeforeCap = Math.max(0, this.maxActualWatchSeconds - (this.accumulatedWatchSeconds + this._accumulatedDelta));
                        var addable = Math.min(elapsedSeconds, remainingBeforeCap);
                        this._accumulatedDelta += addable;
                        if (this.accumulatedWatchSeconds + this._accumulatedDelta >= this.maxActualWatchSeconds) {
                            this.isCapped = true;
                        }
                    } else {
                        this._accumulatedDelta += elapsedSeconds;
                    }
                }
            }
        }

        this._lastTickTime = now;
    };

    VideoWatchTimeTracker.prototype.startHeartbeat = function () {
        this.stopHeartbeat();
        var self = this;
        this._heartbeatTimer = setInterval(function () {
            self._onHeartbeat();
        }, this.heartbeatIntervalMs);
    };

    VideoWatchTimeTracker.prototype.stopHeartbeat = function () {
        if (this._heartbeatTimer) {
            clearInterval(this._heartbeatTimer);
            this._heartbeatTimer = null;
        }
    };

    VideoWatchTimeTracker.prototype._onHeartbeat = function () {
        this._tick();

        // Send heartbeat if at least 1 second accumulated OR video is playing (to keep playhead position synced even when capped)
        if (this._accumulatedDelta >= 1.0 || (this.isCapped && this._isPlaying)) {
            this.flush('in_progress');
        }
    };

    /**
     * Flush accumulated delta and current playback position to the server.
     */
    VideoWatchTimeTracker.prototype.flush = function (status, isUnload) {
        this._tick();

        var deltaToSend = this._accumulatedDelta;
        var intDelta = Math.round(deltaToSend);

        if (this.maxActualWatchSeconds !== null && intDelta > 0) {
            var remainingSeconds = Math.max(0, this.maxActualWatchSeconds - this.accumulatedWatchSeconds);
            intDelta = Math.min(intDelta, remainingSeconds);
        }

        // Skip flush if no delta accumulated, not playing, and no explicit status to report
        if (intDelta <= 0 && !status && !this._isPlaying) {
            return Promise.resolve(null);
        }

        // Deduct the integer seconds being sent; keep any fractional remainder for next tick
        if (intDelta > 0) {
            this._accumulatedDelta = Math.max(0, this._accumulatedDelta - intDelta);
            this.accumulatedWatchSeconds += intDelta;
        }

        if (this.maxActualWatchSeconds !== null && this.accumulatedWatchSeconds >= this.maxActualWatchSeconds) {
            this.isCapped = true;
            this._accumulatedDelta = 0;
        }

        var payload = {
            content_id: this.contentId,
            type: 'video',
            current_time: Math.round(this._getCurrentTime()),
            duration_seconds: Math.round(this._getDuration()),
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
                if (result) {
                    if (result.is_capped !== undefined) {
                        self.isCapped = Boolean(result.is_capped);
                    }
                    if (typeof result.actual_watch_seconds === 'number') {
                        self.accumulatedWatchSeconds = result.actual_watch_seconds;
                        if (self.maxActualWatchSeconds !== null && self.accumulatedWatchSeconds >= self.maxActualWatchSeconds) {
                            self.isCapped = true;
                            self._accumulatedDelta = 0;
                        }
                    }
                    if (result.success && typeof self.onUpdate === 'function') {
                        self.onUpdate(result);
                    }
                }
                return result;
            })
            .catch(function () {
                // Non-fatal network handling
                return null;
            });
    };

    VideoWatchTimeTracker.prototype._bindEvents = function () {
        var self = this;
        var p = this.player;
        if (!p) return;

        this._onPlay = function () {
            self._tick();
            self._isPlaying = true;
            self.startHeartbeat();
        };

        this._onPause = function () {
            self._tick();
            self._isPlaying = false;
            self.stopHeartbeat();
            self.flush('paused');
        };

        this._onSeeking = function () {
            self._tick();
            self._isSeeking = true;
        };

        this._onSeeked = function () {
            self._isSeeking = false;
            self._lastTickTime = self._now();

            // If paused when user finishes seeking, sync playhead position with 0 delta
            if (!self._isPlaying) {
                self.flush('paused');
            }
        };

        this._onEnded = function () {
            self._tick();
            self._isPlaying = false;
            self.stopHeartbeat();
            self.flush('completed');
        };

        // Support Plyr and standard HTML5 media event attachment
        if (typeof p.on === 'function') {
            p.on('play', this._onPlay);
            p.on('pause', this._onPause);
            p.on('seeking', this._onSeeking);
            p.on('seeked', this._onSeeked);
            p.on('ended', this._onEnded);
        } else if (typeof p.addEventListener === 'function') {
            p.addEventListener('play', this._onPlay);
            p.addEventListener('pause', this._onPause);
            p.addEventListener('seeking', this._onSeeking);
            p.addEventListener('seeked', this._onSeeked);
            p.addEventListener('ended', this._onEnded);
        }

        // Page Visibility API: freeze watch time when user switches tabs
        this._onVisibilityChange = function () {
            self._tick();
            self._isVisible = (document.visibilityState === 'visible');

            if (!self._isVisible) {
                self.flush();
            } else {
                self._lastTickTime = self._now();
            }
        };
        document.addEventListener('visibilitychange', this._onVisibilityChange);

        // Page hide / unload: flush remaining watch time before leaving page
        this._onPageLeave = function () {
            self._tick();
            self.flush(self._isPlaying ? 'in_progress' : null, true);
        };
        window.addEventListener('pagehide', this._onPageLeave);
        window.addEventListener('beforeunload', this._onPageLeave);
    };

    VideoWatchTimeTracker.prototype.destroy = function () {
        this.stopHeartbeat();
        this._destroyed = true;

        var p = this.player;
        if (p) {
            if (typeof p.off === 'function') {
                p.off('play', this._onPlay);
                p.off('pause', this._onPause);
                p.off('seeking', this._onSeeking);
                p.off('seeked', this._onSeeked);
                p.off('ended', this._onEnded);
            } else if (typeof p.removeEventListener === 'function') {
                p.removeEventListener('play', this._onPlay);
                p.removeEventListener('pause', this._onPause);
                p.removeEventListener('seeking', this._onSeeking);
                p.removeEventListener('seeked', this._onSeeked);
                p.removeEventListener('ended', this._onEnded);
            }
        }

        if (this._onVisibilityChange) {
            document.removeEventListener('visibilitychange', this._onVisibilityChange);
        }
        if (this._onPageLeave) {
            window.removeEventListener('pagehide', this._onPageLeave);
            window.removeEventListener('beforeunload', this._onPageLeave);
        }
    };

    window.VideoWatchTimeTracker = VideoWatchTimeTracker;
})(window, document);
