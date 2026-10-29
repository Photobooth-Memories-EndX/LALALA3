/**
 * OWL LMS - Total Engagement Time Tracker
 *
 * Dedicated active learning engagement tracker strictly scoped to designated pages.
 * Accurately tracks user engagement time based on active interaction and visibility.
 *
 * Core Guarantees:
 * 1. Counts duration strictly when page is open, tab visible, and window focused.
 * 2. Activity signals: mouse move, mouse click, keyboard, scroll, touch.
 * 3. 60-second explicit idle threshold: pauses counting when user is inactive > 60s.
 * 4. Tab switch / window blur stops counting immediately.
 * 5. Returning to tab stays paused until fresh user interaction occurs.
 * 6. Periodic heartbeat (every 15s) flushes accumulated delta to server.
 * 7. Wall-clock timing ensures browser throttling does not inflate duration.
 * 8. Unload flushing via fetch keepalive ensures unsaved time is saved on exit.
 * 9. Idempotency and atomic updates on server protect against double counting.
 */
(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var card = document.getElementById('totalEngagementCard');
        if (!card) {
            // Not on an authorized engagement tracking page — do nothing.
            return;
        }

        var durationDisplay = document.getElementById('engagementDurationDisplay');
        var statusBadge = document.getElementById('engagementStatusBadge');
        var statusText = document.getElementById('engagementStatusText');

        var targetType = card.getAttribute('data-target-type');
        var contentId = parseInt(card.getAttribute('data-content-id'), 10);
        var trainingPlanId = card.getAttribute('data-trainingplan-id')
            ? parseInt(card.getAttribute('data-trainingplan-id'), 10)
            : null;
        var initialSeconds = parseInt(card.getAttribute('data-initial-seconds'), 10) || 0;
        var heartbeatUrl = card.getAttribute('data-heartbeat-url');
        var csrfToken = card.getAttribute('data-csrf-token');

        if (!targetType || !contentId || !heartbeatUrl || !csrfToken) {
            return;
        }

        // Configuration
        var IDLE_TIMEOUT_MS = 60 * 1000; // 60 seconds idle threshold
        var HEARTBEAT_INTERVAL_SEC = 15; // flush to server every 15 active seconds

        // State variables
        var accumulatedTotalSeconds = initialSeconds;
        var unsavedDeltaSeconds = 0;
        var isActive = false;
        var isTabVisible = document.visibilityState === 'visible';
        var isWindowFocused = (typeof document.hasFocus === 'function') ? document.hasFocus() : true;
        var lastActivityTime = 0;
        var lastTickTime = null;
        var isSendingHeartbeat = false;
        var throttleActivityTimer = 0;

        function formatDuration(totalSec) {
            totalSec = Math.max(0, Math.floor(totalSec));
            var h = Math.floor(totalSec / 3600);
            var m = Math.floor((totalSec % 3600) / 60);
            var s = totalSec % 60;
            var pad = function (n) { return (n < 10 ? '0' : '') + n; };
            return pad(h) + ':' + pad(m) + ':' + pad(s);
        }

        function updateDisplay() {
            if (durationDisplay) {
                durationDisplay.textContent = formatDuration(accumulatedTotalSeconds);
            }
        }

        function setStatus(state) {
            if (!statusBadge || !statusText) return;

            if (state === 'Active') {
                statusBadge.classList.remove('is-paused');
                statusBadge.classList.add('is-active');
                statusText.textContent = 'Active';
            } else {
                statusBadge.classList.remove('is-active');
                statusBadge.classList.add('is-paused');
                statusText.textContent = 'Paused';
            }
        }

        function generateUuid() {
            if (window.crypto && window.crypto.randomUUID) {
                return window.crypto.randomUUID();
            }
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = (Math.random() * 16) | 0;
                var v = c === 'x' ? r : (r & 0x3) | 0x8;
                return v.toString(16);
            });
        }

        function sendHeartbeat(isImmediate) {
            if (unsavedDeltaSeconds <= 0) {
                return Promise.resolve(null);
            }

            if (isSendingHeartbeat && !isImmediate) {
                return Promise.resolve(null);
            }

            var deltaToSend = Math.min(unsavedDeltaSeconds, 120);
            var heartbeatId = generateUuid();

            var payload = {
                target_type: targetType,
                content_id: contentId,
                delta_seconds: deltaToSend,
                heartbeat_id: heartbeatId
            };

            if (trainingPlanId) {
                payload.trainingplan_id = trainingPlanId;
            }

            // Tentatively deduct delta to prevent re-sending in flight
            unsavedDeltaSeconds = Math.max(0, unsavedDeltaSeconds - deltaToSend);
            isSendingHeartbeat = true;

            return fetch(heartbeatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload),
                keepalive: true
            })
                .then(function (res) {
                    return res.ok ? res.json() : null;
                })
                .then(function (data) {
                    isSendingHeartbeat = false;
                    if (data && data.success) {
                        if (typeof data.total_engagement_seconds === 'number') {
                            // Synchronize with server total, keeping any unsaved delta that occurred in flight
                            accumulatedTotalSeconds = Math.max(
                                accumulatedTotalSeconds,
                                data.total_engagement_seconds + unsavedDeltaSeconds
                            );
                            updateDisplay();
                        }
                    } else {
                        // Request failed on server; recover delta for subsequent retry
                        unsavedDeltaSeconds += deltaToSend;
                    }
                    return data;
                })
                .catch(function () {
                    isSendingHeartbeat = false;
                    // Network failure; recover delta for subsequent retry
                    unsavedDeltaSeconds += deltaToSend;
                    return null;
                });
        }

        function onUserInteraction() {
            var now = Date.now();

            // Throttle handling to at most once per 200ms
            if (now - throttleActivityTimer < 200) {
                return;
            }
            throttleActivityTimer = now;

            lastActivityTime = now;

            if (isTabVisible && isWindowFocused) {
                if (!isActive) {
                    isActive = true;
                    lastTickTime = now;
                    setStatus('Active');
                }
            }
        }

        // Attach user engagement listeners
        ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'pointerdown'].forEach(function (eventName) {
            window.addEventListener(eventName, onUserInteraction, { passive: true });
        });

        // Tab visibility listener
        document.addEventListener('visibilitychange', function () {
            var wasVisible = isTabVisible;
            isTabVisible = document.visibilityState === 'visible';

            if (!isTabVisible) {
                if (isActive) {
                    isActive = false;
                    setStatus('Paused');
                    if (unsavedDeltaSeconds > 0) {
                        sendHeartbeat(true);
                    }
                }
            } else if (!wasVisible) {
                // Returned to tab: do NOT auto-resume! Require user activity per requirement 7.
                isActive = false;
                setStatus('Paused');
                lastTickTime = Date.now();
            }
        });

        // Window focus / blur listeners
        window.addEventListener('blur', function () {
            isWindowFocused = false;
            if (isActive) {
                isActive = false;
                setStatus('Paused');
                if (unsavedDeltaSeconds > 0) {
                    sendHeartbeat(true);
                }
            }
        });

        window.addEventListener('focus', function () {
            isWindowFocused = true;
            // Returned to window: wait for user interaction to resume
            if (!isActive) {
                setStatus('Paused');
                lastTickTime = Date.now();
            }
        });

        // Page exit listeners
        window.addEventListener('pagehide', function () {
            if (unsavedDeltaSeconds > 0) {
                sendHeartbeat(true);
            }
        });

        window.addEventListener('beforeunload', function () {
            if (unsavedDeltaSeconds > 0) {
                sendHeartbeat(true);
            }
        });

        // Main 1-second interval ticker
        var tickerInterval = setInterval(function () {
            var now = Date.now();

            // If tab is hidden or window has no focus, pause counting
            if (!isTabVisible || !isWindowFocused) {
                if (isActive) {
                    isActive = false;
                    setStatus('Paused');
                }
                lastTickTime = now;
                return;
            }

            if (isActive) {
                var timeSinceActivity = now - lastActivityTime;

                if (timeSinceActivity > IDLE_TIMEOUT_MS) {
                    // Inactivity exceeded 60s idle threshold -> Pause!
                    isActive = false;
                    setStatus('Paused');

                    // Credit any active time up to the exact 60s threshold boundary
                    if (lastTickTime !== null) {
                        var idleThresholdTimestamp = lastActivityTime + IDLE_TIMEOUT_MS;
                        if (idleThresholdTimestamp > lastTickTime) {
                            var creditMs = idleThresholdTimestamp - lastTickTime;
                            var creditSec = Math.floor(creditMs / 1000);
                            if (creditSec > 0) {
                                accumulatedTotalSeconds += creditSec;
                                unsavedDeltaSeconds += creditSec;
                                updateDisplay();
                            }
                        }
                    }

                    lastTickTime = now;

                    if (unsavedDeltaSeconds > 0) {
                        sendHeartbeat(false);
                    }
                    return;
                }

                // User is actively engaged
                if (lastTickTime === null) {
                    lastTickTime = now;
                }

                var elapsedMs = now - lastTickTime;
                lastTickTime = now;

                // Wall-clock delta measurement protects against browser interval drift
                var secondsToAdd = Math.floor(elapsedMs / 1000);
                if (secondsToAdd <= 0) {
                    secondsToAdd = 1;
                }
                // Clamp single tick increment to at most 2 seconds
                secondsToAdd = Math.min(secondsToAdd, 2);

                accumulatedTotalSeconds += secondsToAdd;
                unsavedDeltaSeconds += secondsToAdd;
                updateDisplay();

                // Periodic heartbeat
                if (unsavedDeltaSeconds >= HEARTBEAT_INTERVAL_SEC) {
                    sendHeartbeat(false);
                }
            } else {
                lastTickTime = now;
            }
        }, 1000);

        // Initial setup
        updateDisplay();
        setStatus('Paused');

        // Cleanup on window unload
        window.addEventListener('unload', function () {
            clearInterval(tickerInterval);
        });
    });
})(window, document);
