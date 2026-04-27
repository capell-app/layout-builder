;(function () {
    window.timers = window.timers || {}
    let fetchDataCallCount = 0
    const fetchDataCallLimit = 10

    function startInterval(key, delay, callback) {
        clearInterval(window.timers[key])
        window.timers[key] = setInterval(callback, delay)
    }

    function clearAllIntervals() {
        fetchDataCallCount = 0
        Object.keys(window.timers).forEach((key) =>
            clearInterval(window.timers[key]),
        )
    }

    function setCsrfToken(token) {
        document.querySelector('meta[name="csrf-token"]').content = token
        document
            .querySelectorAll('input[name="_token"]')
            .forEach((input) => (input.value = token))
    }

    function fetchData() {
        if (fetchDataCallCount >= fetchDataCallLimit) return
        fetchDataCallCount++

        const payload = {
            count: fetchDataCallCount,
            params: window.location.search,
            url: window.location.href,
            ...window.beaconData.payload,
        }

        fetch(window.beaconData.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
            .then((response) => response.json())
            .then((data) => {
                setCsrfToken(data.csrf_token)
                const toolbar = document.getElementById(
                    'capell-frontend-toolbar',
                )
                if (toolbar && data.editor_html) {
                    toolbar.innerHTML = data.editor_html
                }
            })
            .catch(console.error)
    }

    function onPageLoad() {
        if (!window.beaconData?.url) {
            console.error('Beacon data URL not found')
            return
        }

        clearAllIntervals()
        fetchData()

        if (window.beaconData.timeout) {
            startInterval(
                'csrfTokenRefreshTimer',
                window.beaconData.timeout,
                fetchData,
            )
        }
    }

    document.addEventListener('livewire:navigated', onPageLoad)
})()
