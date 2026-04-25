/* global Alpine */
;(function () {
    const querySelector = '.lightbox'

    const lightboxAttr = 'data-lightbox'

    const defaultGroup = 'default'
    const groupAttr = 'data-group'
    const titleAttr = 'data-title'
    const typeAttr = 'data-type'
    const altAttr = 'alt'

    document.addEventListener('livewire:init', () => {
        const elms = document.querySelectorAll(querySelector)
        var media = {}

        if (elms.length > 0) {
            elms.forEach((elm) => {
                elm.addEventListener('click', function handleClick(e) {
                    e.preventDefault()

                    window.dispatchEvent(
                        new CustomEvent('lightbox', {
                            detail: {
                                group:
                                    e.currentTarget.getAttribute(groupAttr) ||
                                    defaultGroup,
                                type: e.currentTarget.getAttribute(typeAttr),
                                url: e.currentTarget.getAttribute(lightboxAttr),
                                title:
                                    e.currentTarget.getAttribute(titleAttr) ||
                                    e.currentTarget.getAttribute(altAttr),
                            },
                        }),
                    )

                    let carousels = document.getElementsByClassName('swiper')
                    for (let i = 0; i < carousels.length; i++) {
                        carousels[i].dispatchEvent(
                            new Event('disable-carousel'),
                        )
                    }
                })

                let group = elm.getAttribute(groupAttr) || defaultGroup

                if (!media[group]) {
                    media[group] = []
                }

                media[group].push({
                    type: elm.getAttribute(typeAttr),
                    url: elm.getAttribute(lightboxAttr),
                    title:
                        elm.getAttribute(titleAttr) ||
                        elm.getAttribute(altAttr),
                })
            })
        }

        Alpine.data('lightbox', () => ({
            currentIndex: null,
            currentGroup: null,
            currentType: null,
            currentTitle: '',
            currentUrl: '',

            load(group, index) {
                if (!media[group] || !media[group][index]) {
                    return false
                }

                this.currentGroup = group
                this.currentIndex = index
                this.currentTitle = media[group][index].title || ''
                this.currentType = media[group][index].type || 'image'
                this.currentUrl = media[group][index].url || ''
            },

            close: function () {
                this.currentGroup = null
                this.currentIndex = null
                this.currentTitle = ''
                this.currentType = null
                this.currentUrl = ''

                let carousels = document.getElementsByClassName('swiper')
                for (let i = 0; i < carousels.length; i++) {
                    carousels[i].dispatchEvent(new Event('enable-carousel'))
                }
            },

            loadPrevious() {
                if (!media[this.currentGroup]) return false

                let index = this.currentIndex - 1
                if (index === -1) {
                    index = media[this.currentGroup].length - 1
                }

                this.load(this.currentGroup, index)
            },

            loadNext() {
                if (!media[this.currentGroup]) return false

                let index = this.currentIndex + 1
                if (index === media[this.currentGroup].length) {
                    index = 0
                }

                this.load(this.currentGroup, index)
            },

            lightbox(event) {
                if (!media[event.detail.group]) {
                    return false
                }

                let index = media[event.detail.group].findIndex(
                    (x) => x.url === event.detail.url,
                )

                if (index !== -1) {
                    this.load(event.detail.group, index)
                }
            },

            total() {
                return media[this.currentGroup]
                    ? media[this.currentGroup].length
                    : 0
            },
        }))
    })
})()
