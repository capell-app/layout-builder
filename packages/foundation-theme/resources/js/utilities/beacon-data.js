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

    function runBeaconScripts(scripts) {
        if (!Array.isArray(scripts)) {
            return
        }

        scripts.forEach((script, index) => {
            if (typeof script !== 'string' || script.trim() === '') {
                return
            }

            const element = document.createElement('script')
            element.dataset.capellBeaconScript = String(index)
            element.text = script

            document.body.appendChild(element)
            element.remove()
        })
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
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then((response) => {
                const contentType = response.headers.get('content-type') || ''

                if (!response.ok || !contentType.includes('application/json')) {
                    throw new Error(
                        `Beacon request failed with status ${response.status}`,
                    )
                }

                return response.json()
            })
            .then((data) => {
                if (data.csrf_token) {
                    setCsrfToken(data.csrf_token)
                }

                runBeaconScripts(data.scripts)
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
