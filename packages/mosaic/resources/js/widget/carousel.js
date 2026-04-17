import { Swiper } from 'swiper'
import {
    Navigation,
    Pagination,
    Autoplay,
    EffectFade,
    Mousewheel,
} from 'swiper/modules'

const carouselStore = new WeakMap()
const defaultCarouselSpeed = 300
const defaultAutoplayDelay = 5000

function readAttribute(node, names) {
    for (const name of names) {
        const value = node.getAttribute(name)

        if (value !== null && value !== '') {
            return value
        }
    }

    return null
}

function readBooleanAttribute(node, names) {
    const value = readAttribute(node, names)

    if (value === null) {
        return null
    }

    const normalizedValue = value.toLowerCase()

    if (['1', 'true', 'yes', 'on'].includes(normalizedValue)) {
        return true
    }

    if (['0', 'false', 'no', 'off'].includes(normalizedValue)) {
        return false
    }

    return null
}

function readNumberAttribute(node, names) {
    const value = readAttribute(node, names)

    if (value === null) {
        return null
    }

    const parsedValue = Number.parseInt(value, 10)

    return Number.isNaN(parsedValue) ? null : parsedValue
}

function readSlidesPerView(node) {
    const value = readAttribute(node, [
        'data-carousel-per-view',
        'data-perview',
    ])

    if (value === null) {
        return 1
    }

    if (value === 'auto') {
        return 'auto'
    }

    const parsedValue = Number.parseInt(value, 10)

    return Number.isNaN(parsedValue) ? 1 : parsedValue
}

function parseBreakpoints(node) {
    const breakpointValue = readAttribute(node, [
        'data-carousel-breakpoints',
        'data-breakpoint',
    ])

    if (breakpointValue === null) {
        return null
    }

    try {
        return JSON.parse(breakpointValue)
    } catch (error) {
        console.error('Invalid JSON in carousel breakpoints:', error)

        return null
    }
}

function resolveCarouselId(swiperNode) {
    const existingCarouselId = readAttribute(swiperNode, ['data-carousel-id'])

    if (existingCarouselId !== null) {
        return existingCarouselId
    }

    const generatedCarouselId = `carousel-${Math.random().toString(36).slice(2, 10)}`

    swiperNode.setAttribute('data-carousel-id', generatedCarouselId)

    return generatedCarouselId
}

export function parseCarouselOptions(swiperNode) {
    const effect =
        readAttribute(swiperNode, ['data-carousel-effect']) ??
        (readBooleanAttribute(swiperNode, ['data-fade']) ? 'fade' : 'slide')

    const fadeEnabled =
        effect === 'fade' ||
        readBooleanAttribute(swiperNode, ['data-carousel-fade']) === true
    const slideCount = swiperNode.querySelectorAll(
        '.swiper-wrapper > .swiper-slide',
    ).length
    const loop =
        readBooleanAttribute(swiperNode, ['data-carousel-loop', 'data-loop']) ??
        false
    const autoplayEnabled =
        readBooleanAttribute(swiperNode, [
            'data-carousel-autoplay',
            'data-auto',
        ]) ?? false
    const dragOverride = readBooleanAttribute(swiperNode, [
        'data-carousel-drag',
        'data-drag',
    ])
    const touchOverride = readBooleanAttribute(swiperNode, [
        'data-carousel-touch',
        'data-carousel-swipe',
    ])
    const wheelOverride = readBooleanAttribute(swiperNode, [
        'data-carousel-wheel',
        'data-wheel',
    ])
    const paginationOverride = readBooleanAttribute(swiperNode, [
        'data-carousel-pagination',
    ])
    const navigationOverride = readBooleanAttribute(swiperNode, [
        'data-carousel-navigation',
    ])
    const effectIsFade = fadeEnabled || effect === 'fade'
    const interactionEnabled = dragOverride ?? touchOverride ?? !effectIsFade

    return {
        align:
            readAttribute(swiperNode, ['data-carousel-align', 'data-align']) ??
            'center',
        autoplayDelay:
            readNumberAttribute(swiperNode, [
                'data-carousel-autoplay-delay',
                'data-delay',
            ]) ?? defaultAutoplayDelay,
        autoplayDisableOnInteraction:
            readBooleanAttribute(swiperNode, [
                'data-carousel-disable-on-interaction',
            ]) ?? true,
        autoplayEnabled,
        breakpoints: parseBreakpoints(swiperNode),
        carouselId: resolveCarouselId(swiperNode),
        effect: effectIsFade ? 'fade' : effect,
        fadeEnabled: effectIsFade,
        grabCursor: interactionEnabled,
        initialSlide:
            readNumberAttribute(swiperNode, ['data-carousel-initial-slide']) ??
            0,
        interactionEnabled,
        loop,
        navigationEnabled: navigationOverride,
        paginationEnabled: paginationOverride,
        pauseOnMouseEnter:
            readBooleanAttribute(swiperNode, [
                'data-carousel-pause-on-hover',
            ]) ?? true,
        perView: readSlidesPerView(swiperNode),
        rewind:
            readBooleanAttribute(swiperNode, ['data-carousel-rewind']) ?? false,
        slideCount,
        speed:
            readNumberAttribute(swiperNode, ['data-carousel-speed']) ??
            defaultCarouselSpeed,
        watchOverflow:
            readBooleanAttribute(swiperNode, [
                'data-carousel-watch-overflow',
            ]) ?? true,
        wheelEnabled: wheelOverride ?? false,
    }
}

export function resolveCarouselControls(
    swiperNode,
    options = parseCarouselOptions(swiperNode),
) {
    const scopedControlsSelector = `[data-carousel-controls="${options.carouselId}"]`
    const controls =
        swiperNode.querySelector('.swiper-controls') ??
        swiperNode.parentElement?.querySelector(scopedControlsSelector) ??
        swiperNode.parentElement?.querySelector('.swiper-controls') ??
        swiperNode
            .closest('[data-carousel-scope]')
            ?.querySelector(scopedControlsSelector) ??
        swiperNode.ownerDocument.querySelector(scopedControlsSelector)

    return {
        controls,
        dotsNode: controls?.querySelector('.swiper-pagination') ?? null,
        nextBtn: controls?.querySelector('.swiper-button-next') ?? null,
        prevBtn: controls?.querySelector('.swiper-button-prev') ?? null,
    }
}

function buildPaginationRenderer() {
    return function renderBullet(index, className) {
        return `<button type="button" class="${className}" data-carousel-bullet-index="${index}" aria-label="Go to slide ${index + 1}"></button>`
    }
}

function updateActiveSlides(swiperNode, swiperInstance) {
    const realIndex = swiperInstance.realIndex ?? swiperInstance.activeIndex
    const originalSlides = swiperNode.querySelectorAll(
        '.swiper-wrapper > .swiper-slide:not(.swiper-slide-duplicate)',
    )

    originalSlides.forEach((slide, index) => {
        slide.classList.toggle('swiper-slide-selected', index === realIndex)
    })
}

function bindPaginationBullets(swiperInstance, controls, options, signal) {
    if (!controls.dotsNode) {
        return
    }

    const bullets = controls.dotsNode.querySelectorAll(
        '.swiper-pagination-bullet',
    )

    bullets.forEach((bullet, index) => {
        if (bullet.dataset.carouselPaginationBound === options.carouselId) {
            return
        }

        bullet.dataset.carouselPaginationBound = options.carouselId
        bullet.addEventListener(
            'click',
            (event) => {
                event.preventDefault()

                if (swiperInstance.destroyed) {
                    return
                }

                const parsedBulletIndex = Number.parseInt(
                    bullet.dataset.carouselBulletIndex ?? '',
                    10,
                )
                const targetIndex = Number.isNaN(parsedBulletIndex)
                    ? index
                    : parsedBulletIndex

                if (
                    options.loop &&
                    typeof swiperInstance.slideToLoop === 'function'
                ) {
                    const realIndex =
                        swiperInstance.realIndex ?? swiperInstance.activeIndex
                    const lastBulletIndex = bullets.length - 1

                    if (realIndex === 0 && targetIndex === lastBulletIndex) {
                        swiperInstance.slidePrev()

                        return
                    }

                    swiperInstance.slideToLoop(targetIndex)

                    return
                }

                swiperInstance.slideTo(targetIndex)
            },
            { signal },
        )
    })
}

function toggleCarousel(swiperNode, swiperInstance, enabled) {
    swiperNode.classList.toggle('swiper-disabled', !enabled)

    if (!swiperInstance.autoplay) {
        return
    }

    if (enabled) {
        swiperInstance.autoplay.start()

        return
    }

    swiperInstance.autoplay.stop()
}

function createVisibilityObserver(swiperNode, swiperInstance) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            toggleCarousel(swiperNode, swiperInstance, entry.isIntersecting)
        })
    })

    observer.observe(swiperNode)

    return observer
}

function bindImageRefresh(swiperNode, swiperInstance, signal) {
    const images = swiperNode.querySelectorAll('img')

    images.forEach((imageNode) => {
        imageNode.addEventListener(
            'load',
            () => {
                swiperInstance.update()
                toggleCarousel(swiperNode, swiperInstance, true)
            },
            { signal },
        )
    })
}

export function buildSwiperSettings(swiperNode, options, controls, signal) {
    const modules = []
    const settings = {
        allowTouchMove: options.interactionEnabled,
        centeredSlides: options.align === 'center',
        grabCursor: options.grabCursor,
        initialSlide: options.initialSlide,
        loop: options.loop,
        loopAdditionalSlides: options.loop ? options.slideCount : undefined,
        observeParents: true,
        observer: true,
        rewind: options.loop ? false : options.rewind,
        slidesPerView: options.fadeEnabled ? 1 : options.perView,
        spaceBetween: 0,
        speed: options.speed,
        watchOverflow: options.watchOverflow,
        watchSlidesProgress: true,
    }

    if (options.breakpoints) {
        settings.breakpoints = options.breakpoints
    }

    if (
        (options.navigationEnabled ??
            Boolean(controls.prevBtn || controls.nextBtn)) &&
        (controls.prevBtn || controls.nextBtn)
    ) {
        modules.push(Navigation)
        settings.navigation = {
            disabledClass: 'swiper-button-disabled',
            nextEl: controls.nextBtn,
            prevEl: controls.prevBtn,
        }
    }

    if (
        (options.paginationEnabled ?? Boolean(controls.dotsNode)) &&
        controls.dotsNode
    ) {
        modules.push(Pagination)
        settings.pagination = {
            bulletActiveClass: 'swiper-pagination-bullet-active',
            bulletClass: 'swiper-pagination-bullet',
            clickable: false,
            el: controls.dotsNode,
            renderBullet: buildPaginationRenderer(),
        }
    }

    if (options.autoplayEnabled) {
        modules.push(Autoplay)
        settings.autoplay = {
            delay: options.autoplayDelay,
            disableOnInteraction: options.autoplayDisableOnInteraction,
            pauseOnMouseEnter: options.pauseOnMouseEnter,
            stopOnLastSlide: false,
        }
    }

    if (options.fadeEnabled) {
        modules.push(EffectFade)
        settings.centeredSlides = true
        settings.effect = 'fade'
        settings.fadeEffect = {
            crossFade: true,
        }
    }

    if (options.wheelEnabled) {
        modules.push(Mousewheel)
        settings.mousewheel = {
            forceToAxis: true,
            releaseOnEdges: true,
        }
    }

    settings.modules = modules
    settings.on = {
        init() {
            swiperNode.classList.add('swiper-ready')
            updateActiveSlides(swiperNode, this)
            bindPaginationBullets(this, controls, options, signal)
        },
        paginationRender() {
            bindPaginationBullets(this, controls, options, signal)
        },
        paginationUpdate() {
            bindPaginationBullets(this, controls, options, signal)
        },
        resize() {
            this.update()
        },
        slideChange() {
            updateActiveSlides(swiperNode, this)
            bindPaginationBullets(this, controls, options, signal)
        },
    }

    return settings
}

export function destroyCarousel(swiperNode) {
    const carousel = carouselStore.get(swiperNode)

    if (!carousel) {
        return
    }

    carousel.abortController.abort()
    carousel.observer?.disconnect()

    if (carousel.swiper && !carousel.swiper.destroyed) {
        carousel.swiper.destroy(true, true)
    }

    delete swiperNode.swiper
    delete swiperNode.dataset.initialized
    swiperNode.classList.remove('swiper-disabled', 'swiper-ready')
    carouselStore.delete(swiperNode)
}

export function initCarousel(swiperNode) {
    if (
        !(swiperNode instanceof HTMLElement) ||
        !swiperNode.querySelector('.swiper-wrapper')
    ) {
        return null
    }

    destroyCarousel(swiperNode)

    const options = parseCarouselOptions(swiperNode)
    const controls = resolveCarouselControls(swiperNode, options)
    const abortController = new AbortController()
    const settings = buildSwiperSettings(
        swiperNode,
        options,
        controls,
        abortController.signal,
    )
    const swiper = new Swiper(swiperNode, settings)

    bindImageRefresh(swiperNode, swiper, abortController.signal)

    swiperNode.addEventListener(
        'enable-carousel',
        () => toggleCarousel(swiperNode, swiper, true),
        { signal: abortController.signal },
    )

    swiperNode.addEventListener(
        'disable-carousel',
        () => toggleCarousel(swiperNode, swiper, false),
        { signal: abortController.signal },
    )

    const observer = createVisibilityObserver(swiperNode, swiper)

    carouselStore.set(swiperNode, {
        abortController,
        observer,
        options,
        swiper,
    })

    swiperNode.dataset.initialized = 'true'
    swiperNode.swiper = swiper

    return swiper
}

export function initCarousels(root = document) {
    const carousels = root.querySelectorAll('.swiper')

    carousels.forEach((carouselNode) => {
        if (!carouselNode.dataset.initialized) {
            initCarousel(carouselNode)
        }
    })
}

if (typeof document !== 'undefined') {
    initCarousels()

    document.addEventListener('livewire:navigated', () => {
        initCarousels()
    })
}
