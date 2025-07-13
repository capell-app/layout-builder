import { Swiper } from 'swiper'
import { Navigation, Pagination, Autoplay, EffectFade } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'
import 'swiper/css/effect-fade'

function initCarousels() {
    const carousels = document.querySelectorAll('.swiper')

    carousels.forEach((carousel) => {
        if (!carousel.dataset.initialized) {
            carousel.dataset.initialized = true
            initCarousel(carousel)
        }
    })
}

initCarousels()

document.addEventListener('livewire:navigated', () => {
    initCarousels()
})

function initCarousel(swiperNode) {
    let controls = swiperNode.querySelector('.swiper-controls')
    if (!controls) {
        controls = swiperNode.parentNode.querySelector('.swiper-controls')
    }
    const dotsNode = controls?.querySelector('.swiper-pagination')
    const prevBtn = controls?.querySelector('.swiper-button-prev')
    const nextBtn = controls?.querySelector('.swiper-button-next')

    const fade = swiperNode.dataset.fade === '1'
    const loop = swiperNode.dataset.loop === '1'
    const autoEnabled = swiperNode.dataset.auto === '1'
    const autoDelay = parseInt(swiperNode.dataset.delay, 10) || 5000
    const dragEnabled =
        swiperNode.dataset.drag !== undefined
            ? swiperNode.dataset.drag === '1'
            : true
    const align = swiperNode.dataset.align || 'center'
    const perView = parseInt(swiperNode.dataset.perview, 10) || 1

    let modules = []
    let settings = {
        loop: loop,
        centeredSlides: align === 'center',
        slidesPerView: perView,
        spaceBetween: 0,
        speed: 300,
        allowTouchMove: dragEnabled,
        grabCursor: dragEnabled,
        watchSlidesProgress: true,
        on: {
            init: function () {
                swiperNode.classList.add('swiper-ready')
                updateActiveSlide(this)
            },
            slideChange: function () {
                updateActiveSlide(this)
            },
            resize: function () {
                this.update()
            },
        },
    }

    // Handle breakpoints
    if (swiperNode.dataset.breakpoint) {
        try {
            settings.breakpoints = JSON.parse(swiperNode.dataset.breakpoint)
        } catch (error) {
            console.error('Invalid JSON in data-breakpoint:', error)
        }
    }

    // Add navigation if buttons exist
    if (prevBtn || nextBtn) {
        modules.push(Navigation)
        settings.navigation = {
            nextEl: nextBtn,
            prevEl: prevBtn,
            disabledClass: 'swiper-button-disabled',
        }
    }

    // Add pagination if dots exist
    if (dotsNode) {
        modules.push(Pagination)
        settings.pagination = {
            el: dotsNode,
            clickable: true,
            bulletClass: 'swiper-pagination-bullet',
            bulletActiveClass: 'swiper-pagination-bullet-active',
        }
    }

    // Add autoplay if enabled
    if (autoEnabled) {
        modules.push(Autoplay)
        settings.autoplay = {
            delay: autoDelay,
            stopOnLastSlide: false,
            disableOnInteraction: true,
            pauseOnMouseEnter: true,
        }
    }

    // Add fade effect if enabled
    if (fade) {
        modules.push(EffectFade)
        settings.effect = 'fade'
        settings.fadeEffect = {
            crossFade: true,
        }
        settings.slidesPerView = 1
        settings.centeredSlides = true
    }

    settings.modules = modules

    let swiper = new Swiper(swiperNode, settings)

    function updateActiveSlide(swiperInstance) {
        const slides = swiperInstance.slides
        slides.forEach((slide, index) => {
            slide.classList.remove('swiper-slide-selected')
            if (index === swiperInstance.activeIndex) {
                slide.classList.add('swiper-slide-selected')
            }
        })
    }

    function toggleCarousel(enable) {
        swiperNode.classList.toggle('swiper-disabled', !enable)

        if (!swiper.autoplay) return

        if (enable) {
            swiper.autoplay.start()
        } else {
            swiper.autoplay.stop()
        }
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                toggleCarousel(true)
            } else {
                toggleCarousel(false)
            }
        })
    })

    observer.observe(swiperNode)

    // Add load event listener to images
    const images = swiperNode.querySelectorAll('img')
    images.forEach((img) => {
        img.addEventListener('load', () => {
            swiper.update()
            toggleCarousel(true)
        })
    })

    swiperNode.addEventListener(
        'enable-carousel',
        () => toggleCarousel(true),
        false,
    )

    swiperNode.addEventListener(
        'disable-carousel',
        () => toggleCarousel(false),
        false,
    )

    // Store swiper instance for potential cleanup
    swiperNode.swiper = swiper
}
