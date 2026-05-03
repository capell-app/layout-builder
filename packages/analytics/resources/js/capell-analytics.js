;(function () {
    'use strict'

    var configElement = document.querySelector(
        '[data-capell-analytics-tracker]',
    )

    if (!configElement) {
        return
    }

    var config = {}

    try {
        config = JSON.parse(configElement.textContent || '{}')
    } catch (error) {
        return
    }

    var defaultIgnoredSelectors = ['[data-capell-analytics-ignore]']
    var sequence = 0
    var eventQueue = []
    var flushTimer = null
    var maxBatchSize = 25
    var visitStorageKey = 'capell_analytics_visit_id'
    var visitCookieName = 'capell_analytics_visit'

    function currentVisitId() {
        var storedVisitId = null

        try {
            storedVisitId = window.localStorage.getItem(visitStorageKey)
        } catch (error) {
            storedVisitId = null
        }

        return storedVisitId || currentVisitCookie()
    }

    function currentVisitCookie() {
        var cookiePrefix = visitCookieName + '='
        var cookies = document.cookie ? document.cookie.split(';') : []
        var matchingCookie = cookies.find(function (cookie) {
            return cookie.trim().indexOf(cookiePrefix) === 0
        })

        if (!matchingCookie) {
            return null
        }

        return decodeURIComponent(
            matchingCookie.trim().slice(cookiePrefix.length),
        )
    }

    function storeVisitId(visitId) {
        if (!visitId) {
            return
        }

        try {
            window.localStorage.setItem(visitStorageKey, visitId)
        } catch (error) {
            // Storage may be unavailable in private browsing or strict environments.
        }
    }

    function sendJson(url, payload, handleResponse) {
        var json = JSON.stringify(payload)

        if (navigator.sendBeacon) {
            var blob = new Blob([json], { type: 'application/json' })

            if (navigator.sendBeacon(url, blob)) {
                return
            }
        }

        fetch(url, {
            method: 'POST',
            body: json,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            keepalive: true,
        })
            .then(function (response) {
                var contentType = response.headers.get('content-type') || ''

                if (
                    handleResponse &&
                    response.ok &&
                    contentType.indexOf('application/json') !== -1
                ) {
                    response
                        .json()
                        .then(handleResponse)
                        .catch(function () {})
                }
            })
            .catch(function () {})
    }

    function flushEvents() {
        if (flushTimer) {
            window.clearTimeout(flushTimer)
            flushTimer = null
        }

        if (!eventQueue.length) {
            return
        }

        var events = eventQueue.splice(0, maxBatchSize)

        sendJson(config.eventsUrl, {
            visit_id: currentVisitId(),
            events: events,
        })

        if (eventQueue.length) {
            scheduleFlush(0)
        }
    }

    function scheduleFlush(delay) {
        if (flushTimer) {
            return
        }

        flushTimer = window.setTimeout(flushEvents, delay)
    }

    function queueEvent(eventPayload) {
        sequence += 1

        eventQueue.push(
            Object.assign(
                {
                    url: window.location.href,
                    title: document.title,
                    occurred_at: new Date().toISOString(),
                    sequence: sequence,
                },
                eventPayload,
            ),
        )

        if (eventQueue.length >= maxBatchSize) {
            flushEvents()
            return
        }

        scheduleFlush(150)
    }

    function trackedElementFromTarget(target) {
        if (!target) {
            return null
        }

        if (target.closest) {
            return target
        }

        return target.parentElement || null
    }

    function ignoredBySelector(element) {
        if (!element) {
            return false
        }

        var ignoredSelectors = defaultIgnoredSelectors.concat(
            Array.isArray(config.ignoredSelectors)
                ? config.ignoredSelectors
                : [],
        )

        return ignoredSelectors.some(function (selector) {
            try {
                return (
                    element.matches(selector) ||
                    Boolean(element.closest(selector))
                )
            } catch (error) {
                return false
            }
        })
    }

    function nearestLandmark(element) {
        var landmark = element.closest(
            'main, nav, header, footer, aside, section, article, [role]',
        )

        if (!landmark) {
            return null
        }

        return (
            landmark.getAttribute('aria-label') ||
            landmark.getAttribute('role') ||
            landmark.tagName.toLowerCase()
        )
    }

    function selectorFor(element) {
        var selector = element.tagName.toLowerCase()
        var trackingElement = element.closest('[data-capell-analytics]')

        if (trackingElement === element) {
            selector += '[data-capell-analytics]'
        }

        return selector
    }

    function explicitTrackingElement(element) {
        return element.closest('[data-capell-analytics]')
    }

    function automaticTrackingElement(element) {
        if (!config.automaticClickTracking) {
            return null
        }

        return element.closest(
            'a[href], button, input[type="submit"], button[type="submit"]',
        )
    }

    function clickName(element) {
        var explicitName = element.getAttribute('data-capell-analytics')

        if (explicitName) {
            return explicitName
        }

        if (element.matches('a[href]')) {
            return 'link_click'
        }

        if (element.matches('input[type="submit"], button[type="submit"]')) {
            return 'form_submit'
        }

        return 'button_click'
    }

    function clickLabel(element) {
        return (
            element.getAttribute('data-capell-analytics-label') ||
            element.getAttribute('aria-label') ||
            element.textContent.trim().replace(/\s+/g, ' ').slice(0, 255) ||
            null
        )
    }

    function trackClick(event) {
        var clickedElement = trackedElementFromTarget(event.target)

        if (!config.trackClicks || ignoredBySelector(clickedElement)) {
            return
        }

        var trackingElement =
            explicitTrackingElement(clickedElement) ||
            automaticTrackingElement(clickedElement)

        if (!trackingElement || ignoredBySelector(trackingElement)) {
            return
        }

        queueEvent({
            type: 'click',
            event_name: clickName(trackingElement),
            label: clickLabel(trackingElement),
            location: trackingElement.getAttribute(
                'data-capell-analytics-location',
            ),
            target_selector: selectorFor(trackingElement),
            viewport_x: Math.round(event.clientX),
            viewport_y: Math.round(event.clientY),
            document_x: Math.round(event.pageX),
            document_y: Math.round(event.pageY),
            metadata: {
                nearest_landmark: nearestLandmark(trackingElement),
            },
        })
    }

    function trackPageView() {
        if (!config.trackPageViews || ignoredBySelector(document.body)) {
            return
        }

        queueEvent({ type: 'page_view' })
    }

    window.CapellAnalytics = {
        consent: function (payload) {
            var consentJson = JSON.stringify(
                Object.assign(
                    { policy_version: config.policyVersion },
                    payload,
                ),
            )

            fetch(config.consentUrl, {
                method: 'POST',
                body: consentJson,
                headers: { 'Content-Type': 'application/json' },
                keepalive: true,
            })
                .then(function (response) {
                    if (!response.ok) {
                        return
                    }

                    response
                        .json()
                        .then(function (response) {
                            storeVisitId(response.visit_id)
                        })
                        .catch(function () {})
                })
                .catch(function () {})
        },
        track: queueEvent,
        flush: flushEvents,
    }

    document.addEventListener('click', trackClick, true)
    window.addEventListener('pagehide', flushEvents)
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            flushEvents()
        }
    })

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', trackPageView, {
            once: true,
        })
    } else {
        trackPageView()
    }
})()
