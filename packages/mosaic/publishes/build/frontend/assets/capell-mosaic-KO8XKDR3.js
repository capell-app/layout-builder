function e(e) {
    return (
        typeof e == `object` &&
        !!e &&
        `constructor` in e &&
        e.constructor === Object
    )
}
function t(n = {}, r = {}) {
    let i = [`__proto__`, `constructor`, `prototype`]
    Object.keys(r)
        .filter((e) => i.indexOf(e) < 0)
        .forEach((i) => {
            n[i] === void 0
                ? (n[i] = r[i])
                : e(r[i]) &&
                  e(n[i]) &&
                  Object.keys(r[i]).length > 0 &&
                  t(n[i], r[i])
        })
}
var n = {
    body: {},
    addEventListener() {},
    removeEventListener() {},
    activeElement: { blur() {}, nodeName: `` },
    querySelector() {
        return null
    },
    querySelectorAll() {
        return []
    },
    getElementById() {
        return null
    },
    createEvent() {
        return { initEvent() {} }
    },
    createElement() {
        return {
            children: [],
            childNodes: [],
            style: {},
            setAttribute() {},
            getElementsByTagName() {
                return []
            },
        }
    },
    createElementNS() {
        return {}
    },
    importNode() {
        return null
    },
    location: {
        hash: ``,
        host: ``,
        hostname: ``,
        href: ``,
        origin: ``,
        pathname: ``,
        protocol: ``,
        search: ``,
    },
}
function r() {
    let e = typeof document < `u` ? document : {}
    return (t(e, n), e)
}
var i = {
    document: n,
    navigator: { userAgent: `` },
    location: {
        hash: ``,
        host: ``,
        hostname: ``,
        href: ``,
        origin: ``,
        pathname: ``,
        protocol: ``,
        search: ``,
    },
    history: { replaceState() {}, pushState() {}, go() {}, back() {} },
    CustomEvent: function () {
        return this
    },
    addEventListener() {},
    removeEventListener() {},
    getComputedStyle() {
        return {
            getPropertyValue() {
                return ``
            },
        }
    },
    Image() {},
    Date() {},
    screen: {},
    setTimeout() {},
    clearTimeout() {},
    matchMedia() {
        return {}
    },
    requestAnimationFrame(e) {
        return typeof setTimeout > `u` ? (e(), null) : setTimeout(e, 0)
    },
    cancelAnimationFrame(e) {
        typeof setTimeout > `u` || clearTimeout(e)
    },
}
function a() {
    let e = typeof window < `u` ? window : {}
    return (t(e, i), e)
}
function o(e = ``) {
    return e
        .trim()
        .split(` `)
        .filter((e) => !!e.trim())
}
function s(e) {
    let t = e
    Object.keys(t).forEach((e) => {
        try {
            t[e] = null
        } catch {}
        try {
            delete t[e]
        } catch {}
    })
}
function c(e, t = 0) {
    return setTimeout(e, t)
}
function l() {
    return Date.now()
}
function u(e) {
    let t = a(),
        n
    return (
        t.getComputedStyle && (n = t.getComputedStyle(e, null)),
        !n && e.currentStyle && (n = e.currentStyle),
        (n ||= e.style),
        n
    )
}
function d(e, t = `x`) {
    let n = a(),
        r,
        i,
        o,
        s = u(e)
    return (
        n.WebKitCSSMatrix
            ? ((i = s.transform || s.webkitTransform),
              i.split(`,`).length > 6 &&
                  (i = i
                      .split(`, `)
                      .map((e) => e.replace(`,`, `.`))
                      .join(`, `)),
              (o = new n.WebKitCSSMatrix(i === `none` ? `` : i)))
            : ((o =
                  s.MozTransform ||
                  s.OTransform ||
                  s.MsTransform ||
                  s.msTransform ||
                  s.transform ||
                  s
                      .getPropertyValue(`transform`)
                      .replace(`translate(`, `matrix(1, 0, 0, 1,`)),
              (r = o.toString().split(`,`))),
        t === `x` &&
            (i = n.WebKitCSSMatrix
                ? o.m41
                : r.length === 16
                  ? parseFloat(r[12])
                  : parseFloat(r[4])),
        t === `y` &&
            (i = n.WebKitCSSMatrix
                ? o.m42
                : r.length === 16
                  ? parseFloat(r[13])
                  : parseFloat(r[5])),
        i || 0
    )
}
function f(e) {
    return (
        typeof e == `object` &&
        !!e &&
        e.constructor &&
        Object.prototype.toString.call(e).slice(8, -1) === `Object`
    )
}
function p(e) {
    return typeof window < `u` && window.HTMLElement !== void 0
        ? e instanceof HTMLElement
        : e && (e.nodeType === 1 || e.nodeType === 11)
}
function m(...e) {
    let t = Object(e[0])
    for (let n = 1; n < e.length; n += 1) {
        let r = e[n]
        if (r != null && !p(r)) {
            let e = Object.keys(Object(r)).filter(
                (e) =>
                    e !== `__proto__` &&
                    e !== `constructor` &&
                    e !== `prototype`,
            )
            for (let n = 0, i = e.length; n < i; n += 1) {
                let i = e[n],
                    a = Object.getOwnPropertyDescriptor(r, i)
                a !== void 0 &&
                    a.enumerable &&
                    (f(t[i]) && f(r[i])
                        ? r[i].__swiper__
                            ? (t[i] = r[i])
                            : m(t[i], r[i])
                        : !f(t[i]) && f(r[i])
                          ? ((t[i] = {}),
                            r[i].__swiper__ ? (t[i] = r[i]) : m(t[i], r[i]))
                          : (t[i] = r[i]))
            }
        }
    }
    return t
}
function h(e, t, n) {
    e.style.setProperty(t, n)
}
function g({ swiper: e, targetPosition: t, side: n }) {
    let r = a(),
        i = -e.translate,
        o = null,
        s,
        c = e.params.speed
    ;((e.wrapperEl.style.scrollSnapType = `none`),
        r.cancelAnimationFrame(e.cssModeFrameID))
    let l = t > i ? `next` : `prev`,
        u = (e, t) => (l === `next` && e >= t) || (l === `prev` && e <= t),
        d = () => {
            ;((s = new Date().getTime()), o === null && (o = s))
            let a = Math.max(Math.min((s - o) / c, 1), 0),
                l = i + (0.5 - Math.cos(a * Math.PI) / 2) * (t - i)
            if (
                (u(l, t) && (l = t), e.wrapperEl.scrollTo({ [n]: l }), u(l, t))
            ) {
                ;((e.wrapperEl.style.overflow = `hidden`),
                    (e.wrapperEl.style.scrollSnapType = ``),
                    setTimeout(() => {
                        ;((e.wrapperEl.style.overflow = ``),
                            e.wrapperEl.scrollTo({ [n]: l }))
                    }),
                    r.cancelAnimationFrame(e.cssModeFrameID))
                return
            }
            e.cssModeFrameID = r.requestAnimationFrame(d)
        }
    d()
}
function _(e) {
    return (
        e.querySelector(`.swiper-slide-transform`) ||
        (e.shadowRoot &&
            e.shadowRoot.querySelector(`.swiper-slide-transform`)) ||
        e
    )
}
function v(e, t = ``) {
    let n = a(),
        r = [...e.children]
    return (
        n.HTMLSlotElement &&
            e instanceof HTMLSlotElement &&
            r.push(...e.assignedElements()),
        t ? r.filter((e) => e.matches(t)) : r
    )
}
function y(e, t) {
    let n = [t]
    for (; n.length > 0; ) {
        let t = n.shift()
        if (e === t) return !0
        n.push(
            ...t.children,
            ...(t.shadowRoot ? t.shadowRoot.children : []),
            ...(t.assignedElements ? t.assignedElements() : []),
        )
    }
}
function b(e, t) {
    let n = a(),
        r = t.contains(e)
    return (
        !r &&
            n.HTMLSlotElement &&
            t instanceof HTMLSlotElement &&
            ((r = [...t.assignedElements()].includes(e)), (r ||= y(e, t))),
        r
    )
}
function x(e) {
    try {
        console.warn(e)
        return
    } catch {}
}
function S(e, t = []) {
    let n = document.createElement(e)
    return (n.classList.add(...(Array.isArray(t) ? t : o(t))), n)
}
function C(e, t) {
    let n = []
    for (; e.previousElementSibling; ) {
        let r = e.previousElementSibling
        ;(t ? r.matches(t) && n.push(r) : n.push(r), (e = r))
    }
    return n
}
function w(e, t) {
    let n = []
    for (; e.nextElementSibling; ) {
        let r = e.nextElementSibling
        ;(t ? r.matches(t) && n.push(r) : n.push(r), (e = r))
    }
    return n
}
function T(e, t) {
    return a().getComputedStyle(e, null).getPropertyValue(t)
}
function E(e) {
    let t = e,
        n
    if (t) {
        for (n = 0; (t = t.previousSibling) !== null; )
            t.nodeType === 1 && (n += 1)
        return n
    }
}
function D(e, t) {
    let n = [],
        r = e.parentElement
    for (; r; )
        (t ? r.matches(t) && n.push(r) : n.push(r), (r = r.parentElement))
    return n
}
function O(e, t) {
    function n(r) {
        r.target === e &&
            (t.call(e, r), e.removeEventListener(`transitionend`, n))
    }
    t && e.addEventListener(`transitionend`, n)
}
function k(e, t, n) {
    let r = a()
    return n
        ? e[t === `width` ? `offsetWidth` : `offsetHeight`] +
              parseFloat(
                  r
                      .getComputedStyle(e, null)
                      .getPropertyValue(
                          t === `width` ? `margin-right` : `margin-top`,
                      ),
              ) +
              parseFloat(
                  r
                      .getComputedStyle(e, null)
                      .getPropertyValue(
                          t === `width` ? `margin-left` : `margin-bottom`,
                      ),
              )
        : e.offsetWidth
}
function A(e) {
    return (Array.isArray(e) ? e : [e]).filter((e) => !!e)
}
function j(e, t = ``) {
    typeof trustedTypes < `u`
        ? (e.innerHTML = trustedTypes
              .createPolicy(`html`, { createHTML: (e) => e })
              .createHTML(t))
        : (e.innerHTML = t)
}
var M
function N() {
    let e = a(),
        t = r()
    return {
        smoothScroll:
            t.documentElement &&
            t.documentElement.style &&
            `scrollBehavior` in t.documentElement.style,
        touch: !!(
            `ontouchstart` in e ||
            (e.DocumentTouch && t instanceof e.DocumentTouch)
        ),
    }
}
function P() {
    return ((M ||= N()), M)
}
var ee
function te({ userAgent: e } = {}) {
    let t = P(),
        n = a(),
        r = n.navigator.platform,
        i = e || n.navigator.userAgent,
        o = { ios: !1, android: !1 },
        s = n.screen.width,
        c = n.screen.height,
        l = i.match(/(Android);?[\s\/]+([\d.]+)?/),
        u = i.match(/(iPad)(?!\1).*OS\s([\d_]+)/),
        d = i.match(/(iPod)(.*OS\s([\d_]+))?/),
        f = !u && i.match(/(iPhone\sOS|iOS)\s([\d_]+)/),
        p = r === `Win32`,
        m = r === `MacIntel`
    return (
        !u &&
            m &&
            t.touch &&
            [
                `1024x1366`,
                `1366x1024`,
                `834x1194`,
                `1194x834`,
                `834x1112`,
                `1112x834`,
                `768x1024`,
                `1024x768`,
                `820x1180`,
                `1180x820`,
                `810x1080`,
                `1080x810`,
            ].indexOf(`${s}x${c}`) >= 0 &&
            ((u = i.match(/(Version)\/([\d.]+)/)),
            (u ||= [0, 1, `13_0_0`]),
            (m = !1)),
        l && !p && ((o.os = `android`), (o.android = !0)),
        (u || f || d) && ((o.os = `ios`), (o.ios = !0)),
        o
    )
}
function ne(e = {}) {
    return ((ee ||= te(e)), ee)
}
var re
function ie() {
    let e = a(),
        t = ne(),
        n = !1
    function r() {
        let t = e.navigator.userAgent.toLowerCase()
        return (
            t.indexOf(`safari`) >= 0 &&
            t.indexOf(`chrome`) < 0 &&
            t.indexOf(`android`) < 0
        )
    }
    if (r()) {
        let t = String(e.navigator.userAgent)
        if (t.includes(`Version/`)) {
            let [e, r] = t
                .split(`Version/`)[1]
                .split(` `)[0]
                .split(`.`)
                .map((e) => Number(e))
            n = e < 16 || (e === 16 && r < 2)
        }
    }
    let i = /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(
            e.navigator.userAgent,
        ),
        o = r(),
        s = o || (i && t.ios)
    return {
        isSafari: n || o,
        needPerspectiveFix: n,
        need3dFix: s,
        isWebView: i,
    }
}
function ae() {
    return ((re ||= ie()), re)
}
function oe({ swiper: e, on: t, emit: n }) {
    let r = a(),
        i = null,
        o = null,
        s = () => {
            !e ||
                e.destroyed ||
                !e.initialized ||
                (n(`beforeResize`), n(`resize`))
        },
        c = () => {
            !e ||
                e.destroyed ||
                !e.initialized ||
                ((i = new ResizeObserver((t) => {
                    o = r.requestAnimationFrame(() => {
                        let { width: n, height: r } = e,
                            i = n,
                            a = r
                        ;(t.forEach(
                            ({
                                contentBoxSize: t,
                                contentRect: n,
                                target: r,
                            }) => {
                                ;(r && r !== e.el) ||
                                    ((i = n ? n.width : (t[0] || t).inlineSize),
                                    (a = n ? n.height : (t[0] || t).blockSize))
                            },
                        ),
                            (i !== n || a !== r) && s())
                    })
                })),
                i.observe(e.el))
        },
        l = () => {
            ;(o && r.cancelAnimationFrame(o),
                i && i.unobserve && e.el && (i.unobserve(e.el), (i = null)))
        },
        u = () => {
            !e || e.destroyed || !e.initialized || n(`orientationchange`)
        }
    ;(t(`init`, () => {
        if (e.params.resizeObserver && r.ResizeObserver !== void 0) {
            c()
            return
        }
        ;(r.addEventListener(`resize`, s),
            r.addEventListener(`orientationchange`, u))
    }),
        t(`destroy`, () => {
            ;(l(),
                r.removeEventListener(`resize`, s),
                r.removeEventListener(`orientationchange`, u))
        }))
}
function se({ swiper: e, extendParams: t, on: n, emit: r }) {
    let i = [],
        o = a(),
        s = (t, n = {}) => {
            let a = new (o.MutationObserver || o.WebkitMutationObserver)(
                (t) => {
                    if (e.__preventObserver__) return
                    if (t.length === 1) {
                        r(`observerUpdate`, t[0])
                        return
                    }
                    let n = function () {
                        r(`observerUpdate`, t[0])
                    }
                    o.requestAnimationFrame
                        ? o.requestAnimationFrame(n)
                        : o.setTimeout(n, 0)
                },
            )
            ;(a.observe(t, {
                attributes: n.attributes === void 0 ? !0 : n.attributes,
                childList:
                    e.isElement || (n.childList === void 0 ? !0 : n).childList,
                characterData:
                    n.characterData === void 0 ? !0 : n.characterData,
            }),
                i.push(a))
        }
    ;(t({ observer: !1, observeParents: !1, observeSlideChildren: !1 }),
        n(`init`, () => {
            if (e.params.observer) {
                if (e.params.observeParents) {
                    let t = D(e.hostEl)
                    for (let e = 0; e < t.length; e += 1) s(t[e])
                }
                ;(s(e.hostEl, { childList: e.params.observeSlideChildren }),
                    s(e.wrapperEl, { attributes: !1 }))
            }
        }),
        n(`destroy`, () => {
            ;(i.forEach((e) => {
                e.disconnect()
            }),
                i.splice(0, i.length))
        }))
}
var ce = {
    on(e, t, n) {
        let r = this
        if (!r.eventsListeners || r.destroyed || typeof t != `function`)
            return r
        let i = n ? `unshift` : `push`
        return (
            e.split(` `).forEach((e) => {
                ;(r.eventsListeners[e] || (r.eventsListeners[e] = []),
                    r.eventsListeners[e][i](t))
            }),
            r
        )
    },
    once(e, t, n) {
        let r = this
        if (!r.eventsListeners || r.destroyed || typeof t != `function`)
            return r
        function i(...n) {
            ;(r.off(e, i),
                i.__emitterProxy && delete i.__emitterProxy,
                t.apply(r, n))
        }
        return ((i.__emitterProxy = t), r.on(e, i, n))
    },
    onAny(e, t) {
        let n = this
        if (!n.eventsListeners || n.destroyed || typeof e != `function`)
            return n
        let r = t ? `unshift` : `push`
        return (
            n.eventsAnyListeners.indexOf(e) < 0 && n.eventsAnyListeners[r](e),
            n
        )
    },
    offAny(e) {
        let t = this
        if (!t.eventsListeners || t.destroyed || !t.eventsAnyListeners) return t
        let n = t.eventsAnyListeners.indexOf(e)
        return (n >= 0 && t.eventsAnyListeners.splice(n, 1), t)
    },
    off(e, t) {
        let n = this
        return (
            !n.eventsListeners ||
                n.destroyed ||
                !n.eventsListeners ||
                e.split(` `).forEach((e) => {
                    t === void 0
                        ? (n.eventsListeners[e] = [])
                        : n.eventsListeners[e] &&
                          n.eventsListeners[e].forEach((r, i) => {
                              ;(r === t ||
                                  (r.__emitterProxy &&
                                      r.__emitterProxy === t)) &&
                                  n.eventsListeners[e].splice(i, 1)
                          })
                }),
            n
        )
    },
    emit(...e) {
        let t = this
        if (!t.eventsListeners || t.destroyed || !t.eventsListeners) return t
        let n, r, i
        return (
            typeof e[0] == `string` || Array.isArray(e[0])
                ? ((n = e[0]), (r = e.slice(1, e.length)), (i = t))
                : ((n = e[0].events), (r = e[0].data), (i = e[0].context || t)),
            r.unshift(i),
            (Array.isArray(n) ? n : n.split(` `)).forEach((e) => {
                ;(t.eventsAnyListeners &&
                    t.eventsAnyListeners.length &&
                    t.eventsAnyListeners.forEach((t) => {
                        t.apply(i, [e, ...r])
                    }),
                    t.eventsListeners &&
                        t.eventsListeners[e] &&
                        t.eventsListeners[e].forEach((e) => {
                            e.apply(i, r)
                        }))
            }),
            t
        )
    },
}
function le() {
    let e = this,
        t,
        n,
        r = e.el
    ;((t =
        e.params.width !== void 0 && e.params.width !== null
            ? e.params.width
            : r.clientWidth),
        (n =
            e.params.height !== void 0 && e.params.height !== null
                ? e.params.height
                : r.clientHeight),
        !((t === 0 && e.isHorizontal()) || (n === 0 && e.isVertical())) &&
            ((t =
                t -
                parseInt(T(r, `padding-left`) || 0, 10) -
                parseInt(T(r, `padding-right`) || 0, 10)),
            (n =
                n -
                parseInt(T(r, `padding-top`) || 0, 10) -
                parseInt(T(r, `padding-bottom`) || 0, 10)),
            Number.isNaN(t) && (t = 0),
            Number.isNaN(n) && (n = 0),
            Object.assign(e, {
                width: t,
                height: n,
                size: e.isHorizontal() ? t : n,
            })))
}
function ue() {
    let e = this
    function t(t, n) {
        return parseFloat(t.getPropertyValue(e.getDirectionLabel(n)) || 0)
    }
    let n = e.params,
        { wrapperEl: r, slidesEl: i, rtlTranslate: a, wrongRTL: o } = e,
        s = e.virtual && n.virtual.enabled,
        c = s ? e.virtual.slides.length : e.slides.length,
        l = v(i, `.${e.params.slideClass}, swiper-slide`),
        u = s ? e.virtual.slides.length : l.length,
        d = [],
        f = [],
        p = [],
        m = n.slidesOffsetBefore
    typeof m == `function` && (m = n.slidesOffsetBefore.call(e))
    let g = n.slidesOffsetAfter
    typeof g == `function` && (g = n.slidesOffsetAfter.call(e))
    let _ = e.snapGrid.length,
        y = e.slidesGrid.length,
        b = e.size - m - g,
        x = n.spaceBetween,
        S = -m,
        C = 0,
        w = 0
    if (b === void 0) return
    ;(typeof x == `string` && x.indexOf(`%`) >= 0
        ? (x = (parseFloat(x.replace(`%`, ``)) / 100) * b)
        : typeof x == `string` && (x = parseFloat(x)),
        (e.virtualSize = -x - m - g),
        l.forEach((e) => {
            ;(a ? (e.style.marginLeft = ``) : (e.style.marginRight = ``),
                (e.style.marginBottom = ``),
                (e.style.marginTop = ``))
        }),
        n.centeredSlides &&
            n.cssMode &&
            (h(r, `--swiper-centered-offset-before`, ``),
            h(r, `--swiper-centered-offset-after`, ``)),
        n.cssMode &&
            (h(r, `--swiper-slides-offset-before`, `${m}px`),
            h(r, `--swiper-slides-offset-after`, `${g}px`)))
    let E = n.grid && n.grid.rows > 1 && e.grid
    E ? e.grid.initSlides(l) : e.grid && e.grid.unsetSlides()
    let D,
        O =
            n.slidesPerView === `auto` &&
            n.breakpoints &&
            Object.keys(n.breakpoints).filter(
                (e) => n.breakpoints[e].slidesPerView !== void 0,
            ).length > 0
    for (let r = 0; r < u; r += 1) {
        D = 0
        let i = l[r]
        if (
            !(
                i &&
                (E && e.grid.updateSlide(r, i, l), T(i, `display`) === `none`)
            )
        ) {
            if (s && n.slidesPerView === `auto`)
                (n.virtual.slidesPerViewAutoSlideSize &&
                    (D = n.virtual.slidesPerViewAutoSlideSize),
                    D &&
                        i &&
                        (n.roundLengths && (D = Math.floor(D)),
                        (i.style[e.getDirectionLabel(`width`)] = `${D}px`)))
            else if (n.slidesPerView === `auto`) {
                O && (i.style[e.getDirectionLabel(`width`)] = ``)
                let r = getComputedStyle(i),
                    a = i.style.transform,
                    o = i.style.webkitTransform
                if (
                    (a && (i.style.transform = `none`),
                    o && (i.style.webkitTransform = `none`),
                    n.roundLengths)
                )
                    D = e.isHorizontal()
                        ? k(i, `width`, !0)
                        : k(i, `height`, !0)
                else {
                    let e = t(r, `width`),
                        n = t(r, `padding-left`),
                        a = t(r, `padding-right`),
                        o = t(r, `margin-left`),
                        s = t(r, `margin-right`),
                        c = r.getPropertyValue(`box-sizing`)
                    if (c && c === `border-box`) D = e + o + s
                    else {
                        let { clientWidth: t, offsetWidth: r } = i
                        D = e + n + a + o + s + (r - t)
                    }
                }
                ;(a && (i.style.transform = a),
                    o && (i.style.webkitTransform = o),
                    n.roundLengths && (D = Math.floor(D)))
            } else
                ((D = (b - (n.slidesPerView - 1) * x) / n.slidesPerView),
                    n.roundLengths && (D = Math.floor(D)),
                    i && (i.style[e.getDirectionLabel(`width`)] = `${D}px`))
            ;(i && (i.swiperSlideSize = D),
                p.push(D),
                n.centeredSlides
                    ? ((S = S + D / 2 + C / 2 + x),
                      C === 0 && r !== 0 && (S = S - b / 2 - x),
                      r === 0 && (S = S - b / 2 - x),
                      Math.abs(S) < 1 / 1e3 && (S = 0),
                      n.roundLengths && (S = Math.floor(S)),
                      w % n.slidesPerGroup === 0 && d.push(S),
                      f.push(S))
                    : (n.roundLengths && (S = Math.floor(S)),
                      (w - Math.min(e.params.slidesPerGroupSkip, w)) %
                          e.params.slidesPerGroup ===
                          0 && d.push(S),
                      f.push(S),
                      (S = S + D + x)),
                (e.virtualSize += D + x),
                (C = D),
                (w += 1))
        }
    }
    if (
        ((e.virtualSize = Math.max(e.virtualSize, b) + g),
        a &&
            o &&
            (n.effect === `slide` || n.effect === `coverflow`) &&
            (r.style.width = `${e.virtualSize + x}px`),
        n.setWrapperSize &&
            (r.style[e.getDirectionLabel(`width`)] = `${e.virtualSize + x}px`),
        E && e.grid.updateWrapperSize(D, d),
        !n.centeredSlides)
    ) {
        let t = n.slidesPerView !== `auto` && n.slidesPerView % 1 != 0,
            r =
                n.snapToSlideEdge &&
                !n.loop &&
                (n.slidesPerView === `auto` || t),
            i = d.length
        if (r) {
            let e
            if (n.slidesPerView === `auto`) {
                e = 1
                let t = 0
                for (
                    let n = p.length - 1;
                    n >= 0 &&
                    ((t += p[n] + (n < p.length - 1 ? x : 0)), t <= b);
                    --n
                )
                    e = p.length - n
            } else e = Math.floor(n.slidesPerView)
            i = Math.max(u - e, 0)
        }
        let a = []
        for (let t = 0; t < d.length; t += 1) {
            let o = d[t]
            ;(n.roundLengths && (o = Math.floor(o)),
                r
                    ? t <= i && a.push(o)
                    : d[t] <= e.virtualSize - b && a.push(o))
        }
        ;((d = a),
            Math.floor(e.virtualSize - b) - Math.floor(d[d.length - 1]) > 1 &&
                (r || d.push(e.virtualSize - b)))
    }
    if (s && n.loop) {
        let t = p[0] + x
        if (n.slidesPerGroup > 1) {
            let r = Math.ceil(
                    (e.virtual.slidesBefore + e.virtual.slidesAfter) /
                        n.slidesPerGroup,
                ),
                i = t * n.slidesPerGroup
            for (let e = 0; e < r; e += 1) d.push(d[d.length - 1] + i)
        }
        for (
            let r = 0;
            r < e.virtual.slidesBefore + e.virtual.slidesAfter;
            r += 1
        )
            (n.slidesPerGroup === 1 && d.push(d[d.length - 1] + t),
                f.push(f[f.length - 1] + t),
                (e.virtualSize += t))
    }
    if ((d.length === 0 && (d = [0]), x !== 0)) {
        let t =
            e.isHorizontal() && a
                ? `marginLeft`
                : e.getDirectionLabel(`marginRight`)
        l.filter((e, t) =>
            !n.cssMode || n.loop ? !0 : t !== l.length - 1,
        ).forEach((e) => {
            e.style[t] = `${x}px`
        })
    }
    if (n.centeredSlides && n.centeredSlidesBounds) {
        let e = 0
        ;(p.forEach((t) => {
            e += t + (x || 0)
        }),
            (e -= x))
        let t = e > b ? e - b : 0
        d = d.map((e) => (e <= 0 ? -m : e > t ? t + g : e))
    }
    if (n.centerInsufficientSlides) {
        let e = 0
        if (
            (p.forEach((t) => {
                e += t + (x || 0)
            }),
            (e -= x),
            e < b)
        ) {
            let t = (b - e) / 2
            ;(d.forEach((e, n) => {
                d[n] = e - t
            }),
                f.forEach((e, n) => {
                    f[n] = e + t
                }))
        }
    }
    if (
        (Object.assign(e, {
            slides: l,
            snapGrid: d,
            slidesGrid: f,
            slidesSizesGrid: p,
        }),
        n.centeredSlides && n.cssMode && !n.centeredSlidesBounds)
    ) {
        ;(h(r, `--swiper-centered-offset-before`, `${-d[0]}px`),
            h(
                r,
                `--swiper-centered-offset-after`,
                `${e.size / 2 - p[p.length - 1] / 2}px`,
            ))
        let t = -e.snapGrid[0],
            n = -e.slidesGrid[0]
        ;((e.snapGrid = e.snapGrid.map((e) => e + t)),
            (e.slidesGrid = e.slidesGrid.map((e) => e + n)))
    }
    if (
        (u !== c && e.emit(`slidesLengthChange`),
        d.length !== _ &&
            (e.params.watchOverflow && e.checkOverflow(),
            e.emit(`snapGridLengthChange`)),
        f.length !== y && e.emit(`slidesGridLengthChange`),
        n.watchSlidesProgress && e.updateSlidesOffset(),
        e.emit(`slidesUpdated`),
        !s && !n.cssMode && (n.effect === `slide` || n.effect === `fade`))
    ) {
        let t = `${n.containerModifierClass}backface-hidden`,
            r = e.el.classList.contains(t)
        u <= n.maxBackfaceHiddenSlides
            ? r || e.el.classList.add(t)
            : r && e.el.classList.remove(t)
    }
}
function de(e) {
    let t = this,
        n = [],
        r = t.virtual && t.params.virtual.enabled,
        i = 0,
        a
    typeof e == `number`
        ? t.setTransition(e)
        : e === !0 && t.setTransition(t.params.speed)
    let o = (e) => (r ? t.slides[t.getSlideIndexByData(e)] : t.slides[e])
    if (t.params.slidesPerView !== `auto` && t.params.slidesPerView > 1)
        if (t.params.centeredSlides)
            (t.visibleSlides || []).forEach((e) => {
                n.push(e)
            })
        else
            for (a = 0; a < Math.ceil(t.params.slidesPerView); a += 1) {
                let e = t.activeIndex + a
                if (e > t.slides.length && !r) break
                n.push(o(e))
            }
    else n.push(o(t.activeIndex))
    for (a = 0; a < n.length; a += 1)
        if (n[a] !== void 0) {
            let e = n[a].offsetHeight
            i = e > i ? e : i
        }
    ;(i || i === 0) && (t.wrapperEl.style.height = `${i}px`)
}
function fe() {
    let e = this,
        t = e.slides,
        n = e.isElement
            ? e.isHorizontal()
                ? e.wrapperEl.offsetLeft
                : e.wrapperEl.offsetTop
            : 0
    for (let r = 0; r < t.length; r += 1)
        t[r].swiperSlideOffset =
            (e.isHorizontal() ? t[r].offsetLeft : t[r].offsetTop) -
            n -
            e.cssOverflowAdjustment()
}
var F = (e, t, n) => {
    t && !e.classList.contains(n)
        ? e.classList.add(n)
        : !t && e.classList.contains(n) && e.classList.remove(n)
}
function pe(e = (this && this.translate) || 0) {
    let t = this,
        n = t.params,
        { slides: r, rtlTranslate: i, snapGrid: a } = t
    if (r.length === 0) return
    r[0].swiperSlideOffset === void 0 && t.updateSlidesOffset()
    let o = -e
    ;(i && (o = e), (t.visibleSlidesIndexes = []), (t.visibleSlides = []))
    let s = n.spaceBetween
    typeof s == `string` && s.indexOf(`%`) >= 0
        ? (s = (parseFloat(s.replace(`%`, ``)) / 100) * t.size)
        : typeof s == `string` && (s = parseFloat(s))
    for (let e = 0; e < r.length; e += 1) {
        let c = r[e],
            l = c.swiperSlideOffset
        n.cssMode && n.centeredSlides && (l -= r[0].swiperSlideOffset)
        let u =
                (o + (n.centeredSlides ? t.minTranslate() : 0) - l) /
                (c.swiperSlideSize + s),
            d =
                (o - a[0] + (n.centeredSlides ? t.minTranslate() : 0) - l) /
                (c.swiperSlideSize + s),
            f = -(o - l),
            p = f + t.slidesSizesGrid[e],
            m = f >= 0 && f <= t.size - t.slidesSizesGrid[e],
            h =
                (f >= 0 && f < t.size - 1) ||
                (p > 1 && p <= t.size) ||
                (f <= 0 && p >= t.size)
        ;(h && (t.visibleSlides.push(c), t.visibleSlidesIndexes.push(e)),
            F(c, h, n.slideVisibleClass),
            F(c, m, n.slideFullyVisibleClass),
            (c.progress = i ? -u : u),
            (c.originalProgress = i ? -d : d))
    }
}
function me(e) {
    let t = this
    if (e === void 0) {
        let n = t.rtlTranslate ? -1 : 1
        e = (t && t.translate && t.translate * n) || 0
    }
    let n = t.params,
        r = t.maxTranslate() - t.minTranslate(),
        { progress: i, isBeginning: a, isEnd: o, progressLoop: s } = t,
        c = a,
        l = o
    if (r === 0) ((i = 0), (a = !0), (o = !0))
    else {
        i = (e - t.minTranslate()) / r
        let n = Math.abs(e - t.minTranslate()) < 1,
            s = Math.abs(e - t.maxTranslate()) < 1
        ;((a = n || i <= 0), (o = s || i >= 1), n && (i = 0), s && (i = 1))
    }
    if (n.loop) {
        let n = t.getSlideIndexByData(0),
            r = t.getSlideIndexByData(t.slides.length - 1),
            i = t.slidesGrid[n],
            a = t.slidesGrid[r],
            o = t.slidesGrid[t.slidesGrid.length - 1],
            c = Math.abs(e)
        ;((s = c >= i ? (c - i) / o : (c + o - a) / o), s > 1 && --s)
    }
    ;(Object.assign(t, {
        progress: i,
        progressLoop: s,
        isBeginning: a,
        isEnd: o,
    }),
        (n.watchSlidesProgress || (n.centeredSlides && n.autoHeight)) &&
            t.updateSlidesProgress(e),
        a && !c && t.emit(`reachBeginning toEdge`),
        o && !l && t.emit(`reachEnd toEdge`),
        ((c && !a) || (l && !o)) && t.emit(`fromEdge`),
        t.emit(`progress`, i))
}
var I = (e, t, n) => {
    t && !e.classList.contains(n)
        ? e.classList.add(n)
        : !t && e.classList.contains(n) && e.classList.remove(n)
}
function he() {
    let e = this,
        { slides: t, params: n, slidesEl: r, activeIndex: i } = e,
        a = e.virtual && n.virtual.enabled,
        o = e.grid && n.grid && n.grid.rows > 1,
        s = (e) => v(r, `.${n.slideClass}${e}, swiper-slide${e}`)[0],
        c,
        l,
        u
    if (a)
        if (n.loop) {
            let t = i - e.virtual.slidesBefore
            ;(t < 0 && (t = e.virtual.slides.length + t),
                t >= e.virtual.slides.length && (t -= e.virtual.slides.length),
                (c = s(`[data-swiper-slide-index="${t}"]`)))
        } else c = s(`[data-swiper-slide-index="${i}"]`)
    else
        o
            ? ((c = t.find((e) => e.column === i)),
              (u = t.find((e) => e.column === i + 1)),
              (l = t.find((e) => e.column === i - 1)))
            : (c = t[i])
    ;(c &&
        (o ||
            ((u = w(c, `.${n.slideClass}, swiper-slide`)[0]),
            n.loop && !u && (u = t[0]),
            (l = C(c, `.${n.slideClass}, swiper-slide`)[0]),
            n.loop)),
        t.forEach((e) => {
            ;(I(e, e === c, n.slideActiveClass),
                I(e, e === u, n.slideNextClass),
                I(e, e === l, n.slidePrevClass))
        }),
        e.emitSlidesClasses())
}
var L = (e, t) => {
        if (!e || e.destroyed || !e.params) return
        let n = t.closest(
            e.isElement ? `swiper-slide` : `.${e.params.slideClass}`,
        )
        if (n) {
            let t = n.querySelector(`.${e.params.lazyPreloaderClass}`)
            ;(!t &&
                e.isElement &&
                (n.shadowRoot
                    ? (t = n.shadowRoot.querySelector(
                          `.${e.params.lazyPreloaderClass}`,
                      ))
                    : requestAnimationFrame(() => {
                          n.shadowRoot &&
                              ((t = n.shadowRoot.querySelector(
                                  `.${e.params.lazyPreloaderClass}`,
                              )),
                              t && !t.lazyPreloaderManaged && t.remove())
                      })),
                t && !t.lazyPreloaderManaged && t.remove())
        }
    },
    R = (e, t) => {
        if (!e.slides[t]) return
        let n = e.slides[t].querySelector(`[loading="lazy"]`)
        n && n.removeAttribute(`loading`)
    },
    z = (e) => {
        if (!e || e.destroyed || !e.params) return
        let t = e.params.lazyPreloadPrevNext,
            n = e.slides.length
        if (!n || !t || t < 0) return
        t = Math.min(t, n)
        let r =
                e.params.slidesPerView === `auto`
                    ? e.slidesPerViewDynamic()
                    : Math.ceil(e.params.slidesPerView),
            i = e.activeIndex
        if (e.params.grid && e.params.grid.rows > 1) {
            let n = i,
                a = [n - t]
            ;(a.push(...Array.from({ length: t }).map((e, t) => n + r + t)),
                e.slides.forEach((t, n) => {
                    a.includes(t.column) && R(e, n)
                }))
            return
        }
        let a = i + r - 1
        if (e.params.rewind || e.params.loop)
            for (let r = i - t; r <= a + t; r += 1) {
                let t = ((r % n) + n) % n
                ;(t < i || t > a) && R(e, t)
            }
        else
            for (
                let r = Math.max(i - t, 0);
                r <= Math.min(a + t, n - 1);
                r += 1
            )
                r !== i && (r > a || r < i) && R(e, r)
    }
function ge(e) {
    let { slidesGrid: t, params: n } = e,
        r = e.rtlTranslate ? e.translate : -e.translate,
        i
    for (let e = 0; e < t.length; e += 1)
        t[e + 1] === void 0
            ? r >= t[e] && (i = e)
            : r >= t[e] && r < t[e + 1] - (t[e + 1] - t[e]) / 2
              ? (i = e)
              : r >= t[e] && r < t[e + 1] && (i = e + 1)
    return (n.normalizeSlideIndex && (i < 0 || i === void 0) && (i = 0), i)
}
function _e(e) {
    let t = this,
        n = t.rtlTranslate ? t.translate : -t.translate,
        {
            snapGrid: r,
            params: i,
            activeIndex: a,
            realIndex: o,
            snapIndex: s,
        } = t,
        c = e,
        l,
        u = (e) => {
            let n = e - t.virtual.slidesBefore
            return (
                n < 0 && (n = t.virtual.slides.length + n),
                n >= t.virtual.slides.length && (n -= t.virtual.slides.length),
                n
            )
        }
    if ((c === void 0 && (c = ge(t)), r.indexOf(n) >= 0)) l = r.indexOf(n)
    else {
        let e = Math.min(i.slidesPerGroupSkip, c)
        l = e + Math.floor((c - e) / i.slidesPerGroup)
    }
    if ((l >= r.length && (l = r.length - 1), c === a && !t.params.loop)) {
        l !== s && ((t.snapIndex = l), t.emit(`snapIndexChange`))
        return
    }
    if (c === a && t.params.loop && t.virtual && t.params.virtual.enabled) {
        t.realIndex = u(c)
        return
    }
    let d = t.grid && i.grid && i.grid.rows > 1,
        f
    if (t.virtual && i.virtual.enabled) f = i.loop ? u(c) : c
    else if (d) {
        let e = t.slides.find((e) => e.column === c),
            n = parseInt(e.getAttribute(`data-swiper-slide-index`), 10)
        ;(Number.isNaN(n) && (n = Math.max(t.slides.indexOf(e), 0)),
            (f = Math.floor(n / i.grid.rows)))
    } else if (t.slides[c]) {
        let e = t.slides[c].getAttribute(`data-swiper-slide-index`)
        f = e ? parseInt(e, 10) : c
    } else f = c
    ;(Object.assign(t, {
        previousSnapIndex: s,
        snapIndex: l,
        previousRealIndex: o,
        realIndex: f,
        previousIndex: a,
        activeIndex: c,
    }),
        t.initialized && z(t),
        t.emit(`activeIndexChange`),
        t.emit(`snapIndexChange`),
        (t.initialized || t.params.runCallbacksOnInit) &&
            (o !== f && t.emit(`realIndexChange`), t.emit(`slideChange`)))
}
function ve(e, t) {
    let n = this,
        r = n.params,
        i = e.closest(`.${r.slideClass}, swiper-slide`)
    !i &&
        n.isElement &&
        t &&
        t.length > 1 &&
        t.includes(e) &&
        [...t.slice(t.indexOf(e) + 1, t.length)].forEach((e) => {
            !i &&
                e.matches &&
                e.matches(`.${r.slideClass}, swiper-slide`) &&
                (i = e)
        })
    let a = !1,
        o
    if (i) {
        for (let e = 0; e < n.slides.length; e += 1)
            if (n.slides[e] === i) {
                ;((a = !0), (o = e))
                break
            }
    }
    if (i && a)
        ((n.clickedSlide = i),
            n.virtual && n.params.virtual.enabled
                ? (n.clickedIndex = parseInt(
                      i.getAttribute(`data-swiper-slide-index`),
                      10,
                  ))
                : (n.clickedIndex = o))
    else {
        ;((n.clickedSlide = void 0), (n.clickedIndex = void 0))
        return
    }
    r.slideToClickedSlide &&
        n.clickedIndex !== void 0 &&
        n.clickedIndex !== n.activeIndex &&
        n.slideToClickedSlide()
}
var ye = {
    updateSize: le,
    updateSlides: ue,
    updateAutoHeight: de,
    updateSlidesOffset: fe,
    updateSlidesProgress: pe,
    updateProgress: me,
    updateSlidesClasses: he,
    updateActiveIndex: _e,
    updateClickedSlide: ve,
}
function be(e = this.isHorizontal() ? `x` : `y`) {
    let t = this,
        { params: n, rtlTranslate: r, translate: i, wrapperEl: a } = t
    if (n.virtualTranslate) return r ? -i : i
    if (n.cssMode) return i
    let o = d(a, e)
    return ((o += t.cssOverflowAdjustment()), r && (o = -o), o || 0)
}
function xe(e, t) {
    let n = this,
        { rtlTranslate: r, params: i, wrapperEl: a, progress: o } = n,
        s = 0,
        c = 0
    ;(n.isHorizontal() ? (s = r ? -e : e) : (c = e),
        i.roundLengths && ((s = Math.floor(s)), (c = Math.floor(c))),
        (n.previousTranslate = n.translate),
        (n.translate = n.isHorizontal() ? s : c),
        i.cssMode
            ? (a[n.isHorizontal() ? `scrollLeft` : `scrollTop`] =
                  n.isHorizontal() ? -s : -c)
            : i.virtualTranslate ||
              (n.isHorizontal()
                  ? (s -= n.cssOverflowAdjustment())
                  : (c -= n.cssOverflowAdjustment()),
              (a.style.transform = `translate3d(${s}px, ${c}px, 0px)`)))
    let l,
        u = n.maxTranslate() - n.minTranslate()
    ;((l = u === 0 ? 0 : (e - n.minTranslate()) / u),
        l !== o && n.updateProgress(e),
        n.emit(`setTranslate`, n.translate, t))
}
function Se() {
    return -this.snapGrid[0]
}
function Ce() {
    return -this.snapGrid[this.snapGrid.length - 1]
}
function we(e = 0, t = this.params.speed, n = !0, r = !0, i) {
    let a = this,
        { params: o, wrapperEl: s } = a
    if (a.animating && o.preventInteractionOnTransition) return !1
    let c = a.minTranslate(),
        l = a.maxTranslate(),
        u
    if (
        ((u = r && e > c ? c : r && e < l ? l : e),
        a.updateProgress(u),
        o.cssMode)
    ) {
        let e = a.isHorizontal()
        if (t === 0) s[e ? `scrollLeft` : `scrollTop`] = -u
        else {
            if (!a.support.smoothScroll)
                return (
                    g({
                        swiper: a,
                        targetPosition: -u,
                        side: e ? `left` : `top`,
                    }),
                    !0
                )
            s.scrollTo({ [e ? `left` : `top`]: -u, behavior: `smooth` })
        }
        return !0
    }
    return (
        t === 0
            ? (a.setTransition(0),
              a.setTranslate(u),
              n &&
                  (a.emit(`beforeTransitionStart`, t, i),
                  a.emit(`transitionEnd`)))
            : (a.setTransition(t),
              a.setTranslate(u),
              n &&
                  (a.emit(`beforeTransitionStart`, t, i),
                  a.emit(`transitionStart`)),
              a.animating ||
                  ((a.animating = !0),
                  (a.onTranslateToWrapperTransitionEnd ||= function (e) {
                      !a ||
                          a.destroyed ||
                          (e.target === this &&
                              (a.wrapperEl.removeEventListener(
                                  `transitionend`,
                                  a.onTranslateToWrapperTransitionEnd,
                              ),
                              (a.onTranslateToWrapperTransitionEnd = null),
                              delete a.onTranslateToWrapperTransitionEnd,
                              (a.animating = !1),
                              n && a.emit(`transitionEnd`)))
                  }),
                  a.wrapperEl.addEventListener(
                      `transitionend`,
                      a.onTranslateToWrapperTransitionEnd,
                  ))),
        !0
    )
}
var Te = {
    getTranslate: be,
    setTranslate: xe,
    minTranslate: Se,
    maxTranslate: Ce,
    translateTo: we,
}
function Ee(e, t) {
    let n = this
    ;(n.params.cssMode ||
        ((n.wrapperEl.style.transitionDuration = `${e}ms`),
        (n.wrapperEl.style.transitionDelay = e === 0 ? `0ms` : ``)),
        n.emit(`setTransition`, e, t))
}
function B({ swiper: e, runCallbacks: t, direction: n, step: r }) {
    let { activeIndex: i, previousIndex: a } = e,
        o = n
    ;((o ||= i > a ? `next` : i < a ? `prev` : `reset`),
        e.emit(`transition${r}`),
        t && o === `reset`
            ? e.emit(`slideResetTransition${r}`)
            : t &&
              i !== a &&
              (e.emit(`slideChangeTransition${r}`),
              o === `next`
                  ? e.emit(`slideNextTransition${r}`)
                  : e.emit(`slidePrevTransition${r}`)))
}
function De(e = !0, t) {
    let n = this,
        { params: r } = n
    r.cssMode ||
        (r.autoHeight && n.updateAutoHeight(),
        B({ swiper: n, runCallbacks: e, direction: t, step: `Start` }))
}
function Oe(e = !0, t) {
    let n = this,
        { params: r } = n
    ;((n.animating = !1),
        !r.cssMode &&
            (n.setTransition(0),
            B({ swiper: n, runCallbacks: e, direction: t, step: `End` })))
}
var ke = { setTransition: Ee, transitionStart: De, transitionEnd: Oe }
function Ae(e = 0, t, n = !0, r, i) {
    typeof e == `string` && (e = parseInt(e, 10))
    let a = this,
        o = e
    o < 0 && (o = 0)
    let {
        params: s,
        snapGrid: c,
        slidesGrid: l,
        previousIndex: u,
        activeIndex: d,
        rtlTranslate: f,
        wrapperEl: p,
        enabled: m,
    } = a
    if (
        (!m && !r && !i) ||
        a.destroyed ||
        (a.animating && s.preventInteractionOnTransition)
    )
        return !1
    t === void 0 && (t = a.params.speed)
    let h = Math.min(a.params.slidesPerGroupSkip, o),
        _ = h + Math.floor((o - h) / a.params.slidesPerGroup)
    _ >= c.length && (_ = c.length - 1)
    let v = -c[_]
    if (s.normalizeSlideIndex)
        for (let e = 0; e < l.length; e += 1) {
            let t = -Math.floor(v * 100),
                n = Math.floor(l[e] * 100),
                r = Math.floor(l[e + 1] * 100)
            l[e + 1] === void 0
                ? t >= n && (o = e)
                : t >= n && t < r - (r - n) / 2
                  ? (o = e)
                  : t >= n && t < r && (o = e + 1)
        }
    if (
        a.initialized &&
        o !== d &&
        ((!a.allowSlideNext &&
            (f
                ? v > a.translate && v > a.minTranslate()
                : v < a.translate && v < a.minTranslate())) ||
            (!a.allowSlidePrev &&
                v > a.translate &&
                v > a.maxTranslate() &&
                (d || 0) !== o))
    )
        return !1
    ;(o !== (u || 0) && n && a.emit(`beforeSlideChangeStart`),
        a.updateProgress(v))
    let y
    y = o > d ? `next` : o < d ? `prev` : `reset`
    let b = a.virtual && a.params.virtual.enabled
    if (!(b && i) && ((f && -v === a.translate) || (!f && v === a.translate)))
        return (
            a.updateActiveIndex(o),
            s.autoHeight && a.updateAutoHeight(),
            a.updateSlidesClasses(),
            s.effect !== `slide` && a.setTranslate(v),
            y !== `reset` && (a.transitionStart(n, y), a.transitionEnd(n, y)),
            !1
        )
    if (s.cssMode) {
        let e = a.isHorizontal(),
            n = f ? v : -v
        if (t === 0)
            (b &&
                ((a.wrapperEl.style.scrollSnapType = `none`),
                (a._immediateVirtual = !0)),
                b && !a._cssModeVirtualInitialSet && a.params.initialSlide > 0
                    ? ((a._cssModeVirtualInitialSet = !0),
                      requestAnimationFrame(() => {
                          p[e ? `scrollLeft` : `scrollTop`] = n
                      }))
                    : (p[e ? `scrollLeft` : `scrollTop`] = n),
                b &&
                    requestAnimationFrame(() => {
                        ;((a.wrapperEl.style.scrollSnapType = ``),
                            (a._immediateVirtual = !1))
                    }))
        else {
            if (!a.support.smoothScroll)
                return (
                    g({
                        swiper: a,
                        targetPosition: n,
                        side: e ? `left` : `top`,
                    }),
                    !0
                )
            p.scrollTo({ [e ? `left` : `top`]: n, behavior: `smooth` })
        }
        return !0
    }
    let x = ae().isSafari
    return (
        b && !i && x && a.isElement && a.virtual.update(!1, !1, o),
        a.setTransition(t),
        a.setTranslate(v),
        a.updateActiveIndex(o),
        a.updateSlidesClasses(),
        a.emit(`beforeTransitionStart`, t, r),
        a.transitionStart(n, y),
        t === 0
            ? a.transitionEnd(n, y)
            : a.animating ||
              ((a.animating = !0),
              (a.onSlideToWrapperTransitionEnd ||= function (e) {
                  !a ||
                      a.destroyed ||
                      (e.target === this &&
                          (a.wrapperEl.removeEventListener(
                              `transitionend`,
                              a.onSlideToWrapperTransitionEnd,
                          ),
                          (a.onSlideToWrapperTransitionEnd = null),
                          delete a.onSlideToWrapperTransitionEnd,
                          a.transitionEnd(n, y)))
              }),
              a.wrapperEl.addEventListener(
                  `transitionend`,
                  a.onSlideToWrapperTransitionEnd,
              )),
        !0
    )
}
function je(e = 0, t, n = !0, r) {
    typeof e == `string` && (e = parseInt(e, 10))
    let i = this
    if (i.destroyed) return
    t === void 0 && (t = i.params.speed)
    let a = i.grid && i.params.grid && i.params.grid.rows > 1,
        o = e
    if (i.params.loop)
        if (i.virtual && i.params.virtual.enabled) o += i.virtual.slidesBefore
        else {
            let e
            if (a) {
                let t = o * i.params.grid.rows
                e = i.slides.find(
                    (e) => e.getAttribute(`data-swiper-slide-index`) * 1 === t,
                ).column
            } else e = i.getSlideIndexByData(o)
            let t = a
                    ? Math.ceil(i.slides.length / i.params.grid.rows)
                    : i.slides.length,
                {
                    centeredSlides: n,
                    slidesOffsetBefore: s,
                    slidesOffsetAfter: c,
                } = i.params,
                l = n || !!s || !!c,
                u = i.params.slidesPerView
            u === `auto`
                ? (u = i.slidesPerViewDynamic())
                : ((u = Math.ceil(parseFloat(i.params.slidesPerView, 10))),
                  l && u % 2 == 0 && (u += 1))
            let d = t - e < u
            if (
                (l && (d ||= e < Math.ceil(u / 2)),
                r && l && i.params.slidesPerView !== `auto` && !a && (d = !1),
                d)
            ) {
                let n = l
                    ? e < i.activeIndex
                        ? `prev`
                        : `next`
                    : e - i.activeIndex - 1 < i.params.slidesPerView
                      ? `next`
                      : `prev`
                i.loopFix({
                    direction: n,
                    slideTo: !0,
                    activeSlideIndex: n === `next` ? e + 1 : e - t + 1,
                    slideRealIndex: n === `next` ? i.realIndex : void 0,
                })
            }
            if (a) {
                let e = o * i.params.grid.rows
                o = i.slides.find(
                    (t) => t.getAttribute(`data-swiper-slide-index`) * 1 === e,
                ).column
            } else o = i.getSlideIndexByData(o)
        }
    return (
        requestAnimationFrame(() => {
            i.slideTo(o, t, n, r)
        }),
        i
    )
}
function Me(e, t = !0, n) {
    let r = this,
        { enabled: i, params: a, animating: o } = r
    if (!i || r.destroyed) return r
    e === void 0 && (e = r.params.speed)
    let s = a.slidesPerGroup
    a.slidesPerView === `auto` &&
        a.slidesPerGroup === 1 &&
        a.slidesPerGroupAuto &&
        (s = Math.max(r.slidesPerViewDynamic(`current`, !0), 1))
    let c = r.activeIndex < a.slidesPerGroupSkip ? 1 : s,
        l = r.virtual && a.virtual.enabled
    if (a.loop) {
        if (o && !l && a.loopPreventsSliding) return !1
        if (
            (r.loopFix({ direction: `next` }),
            (r._clientLeft = r.wrapperEl.clientLeft),
            r.activeIndex === r.slides.length - 1 && a.cssMode)
        )
            return (
                requestAnimationFrame(() => {
                    r.slideTo(r.activeIndex + c, e, t, n)
                }),
                !0
            )
    }
    return a.rewind && r.isEnd
        ? r.slideTo(0, e, t, n)
        : r.slideTo(r.activeIndex + c, e, t, n)
}
function Ne(e, t = !0, n) {
    let r = this,
        {
            params: i,
            snapGrid: a,
            slidesGrid: o,
            rtlTranslate: s,
            enabled: c,
            animating: l,
        } = r
    if (!c || r.destroyed) return r
    e === void 0 && (e = r.params.speed)
    let u = r.virtual && i.virtual.enabled
    if (i.loop) {
        if (l && !u && i.loopPreventsSliding) return !1
        ;(r.loopFix({ direction: `prev` }),
            (r._clientLeft = r.wrapperEl.clientLeft))
    }
    let d = s ? r.translate : -r.translate
    function f(e) {
        return e < 0 ? -Math.floor(Math.abs(e)) : Math.floor(e)
    }
    let p = f(d),
        m = a.map((e) => f(e)),
        h = i.freeMode && i.freeMode.enabled,
        g = a[m.indexOf(p) - 1]
    if (g === void 0 && (i.cssMode || h)) {
        let e
        ;(a.forEach((t, n) => {
            p >= t && (e = n)
        }),
            e !== void 0 && (g = h ? a[e] : a[e > 0 ? e - 1 : e]))
    }
    let _ = 0
    if (
        (g !== void 0 &&
            ((_ = o.indexOf(g)),
            _ < 0 && (_ = r.activeIndex - 1),
            i.slidesPerView === `auto` &&
                i.slidesPerGroup === 1 &&
                i.slidesPerGroupAuto &&
                ((_ = _ - r.slidesPerViewDynamic(`previous`, !0) + 1),
                (_ = Math.max(_, 0)))),
        i.rewind && r.isBeginning)
    ) {
        let i =
            r.params.virtual && r.params.virtual.enabled && r.virtual
                ? r.virtual.slides.length - 1
                : r.slides.length - 1
        return r.slideTo(i, e, t, n)
    } else if (i.loop && r.activeIndex === 0 && i.cssMode)
        return (
            requestAnimationFrame(() => {
                r.slideTo(_, e, t, n)
            }),
            !0
        )
    return r.slideTo(_, e, t, n)
}
function Pe(e, t = !0, n) {
    let r = this
    if (!r.destroyed)
        return (
            e === void 0 && (e = r.params.speed),
            r.slideTo(r.activeIndex, e, t, n)
        )
}
function Fe(e, t = !0, n, r = 0.5) {
    let i = this
    if (i.destroyed) return
    e === void 0 && (e = i.params.speed)
    let a = i.activeIndex,
        o = Math.min(i.params.slidesPerGroupSkip, a),
        s = o + Math.floor((a - o) / i.params.slidesPerGroup),
        c = i.rtlTranslate ? i.translate : -i.translate
    if (c >= i.snapGrid[s]) {
        let e = i.snapGrid[s],
            t = i.snapGrid[s + 1]
        c - e > (t - e) * r && (a += i.params.slidesPerGroup)
    } else {
        let e = i.snapGrid[s - 1],
            t = i.snapGrid[s]
        c - e <= (t - e) * r && (a -= i.params.slidesPerGroup)
    }
    return (
        (a = Math.max(a, 0)),
        (a = Math.min(a, i.slidesGrid.length - 1)),
        i.slideTo(a, e, t, n)
    )
}
function Ie() {
    let e = this
    if (e.destroyed) return
    let { params: t, slidesEl: n } = e,
        r =
            t.slidesPerView === `auto`
                ? e.slidesPerViewDynamic()
                : t.slidesPerView,
        i = e.getSlideIndexWhenGrid(e.clickedIndex),
        a,
        o = e.isElement ? `swiper-slide` : `.${t.slideClass}`,
        s = e.grid && e.params.grid && e.params.grid.rows > 1
    if (t.loop) {
        if (e.animating) return
        ;((a = parseInt(
            e.clickedSlide.getAttribute(`data-swiper-slide-index`),
            10,
        )),
            t.centeredSlides
                ? e.slideToLoop(a)
                : i >
                    (s
                        ? (e.slides.length - r) / 2 - (e.params.grid.rows - 1)
                        : e.slides.length - r)
                  ? (e.loopFix(),
                    (i = e.getSlideIndex(
                        v(n, `${o}[data-swiper-slide-index="${a}"]`)[0],
                    )),
                    c(() => {
                        e.slideTo(i)
                    }))
                  : e.slideTo(i))
    } else e.slideTo(i)
}
var Le = {
    slideTo: Ae,
    slideToLoop: je,
    slideNext: Me,
    slidePrev: Ne,
    slideReset: Pe,
    slideToClosest: Fe,
    slideToClickedSlide: Ie,
}
function Re(e, t) {
    let n = this,
        { params: r, slidesEl: i } = n
    if (!r.loop || (n.virtual && n.params.virtual.enabled)) return
    let a = () => {
            v(i, `.${r.slideClass}, swiper-slide`).forEach((e, t) => {
                e.setAttribute(`data-swiper-slide-index`, t)
            })
        },
        o = () => {
            let e = v(i, `.${r.slideBlankClass}`)
            ;(e.forEach((e) => {
                e.remove()
            }),
                e.length > 0 && (n.recalcSlides(), n.updateSlides()))
        },
        s = n.grid && r.grid && r.grid.rows > 1
    r.loopAddBlankSlides && (r.slidesPerGroup > 1 || s) && o()
    let c = r.slidesPerGroup * (s ? r.grid.rows : 1),
        l = n.slides.length % c !== 0,
        u = s && n.slides.length % r.grid.rows !== 0,
        d = (e) => {
            for (let t = 0; t < e; t += 1) {
                let e = n.isElement
                    ? S(`swiper-slide`, [r.slideBlankClass])
                    : S(`div`, [r.slideClass, r.slideBlankClass])
                n.slidesEl.append(e)
            }
        }
    l
        ? (r.loopAddBlankSlides
              ? (d(c - (n.slides.length % c)),
                n.recalcSlides(),
                n.updateSlides())
              : x(
                    `Swiper Loop Warning: The number of slides is not even to slidesPerGroup, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)`,
                ),
          a())
        : (u &&
              (r.loopAddBlankSlides
                  ? (d(r.grid.rows - (n.slides.length % r.grid.rows)),
                    n.recalcSlides(),
                    n.updateSlides())
                  : x(
                        `Swiper Loop Warning: The number of slides is not even to grid.rows, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)`,
                    )),
          a())
    let f = r.centeredSlides || !!r.slidesOffsetBefore || !!r.slidesOffsetAfter
    n.loopFix({ slideRealIndex: e, direction: f ? void 0 : `next`, initial: t })
}
function ze({
    slideRealIndex: e,
    slideTo: t = !0,
    direction: n,
    setTranslate: r,
    activeSlideIndex: i,
    initial: a,
    byController: o,
    byMousewheel: s,
} = {}) {
    let c = this
    if (!c.params.loop) return
    c.emit(`beforeLoopFix`)
    let {
            slides: l,
            allowSlidePrev: u,
            allowSlideNext: d,
            slidesEl: f,
            params: p,
        } = c,
        {
            centeredSlides: m,
            slidesOffsetBefore: h,
            slidesOffsetAfter: g,
            initialSlide: _,
        } = p,
        v = m || !!h || !!g
    if (
        ((c.allowSlidePrev = !0),
        (c.allowSlideNext = !0),
        c.virtual && p.virtual.enabled)
    ) {
        ;(t &&
            (!v && c.snapIndex === 0
                ? c.slideTo(c.virtual.slides.length, 0, !1, !0)
                : v && c.snapIndex < p.slidesPerView
                  ? c.slideTo(c.virtual.slides.length + c.snapIndex, 0, !1, !0)
                  : c.snapIndex === c.snapGrid.length - 1 &&
                    c.slideTo(c.virtual.slidesBefore, 0, !1, !0)),
            (c.allowSlidePrev = u),
            (c.allowSlideNext = d),
            c.emit(`loopFix`))
        return
    }
    let y = p.slidesPerView
    y === `auto`
        ? (y = c.slidesPerViewDynamic())
        : ((y = Math.ceil(parseFloat(p.slidesPerView, 10))),
          v && y % 2 == 0 && (y += 1))
    let b = p.slidesPerGroupAuto ? y : p.slidesPerGroup,
        S = v ? Math.max(b, Math.ceil(y / 2)) : b
    ;(S % b !== 0 && (S += b - (S % b)),
        (S += p.loopAdditionalSlides),
        (c.loopedSlides = S))
    let C = c.grid && p.grid && p.grid.rows > 1
    l.length < y + S || (c.params.effect === `cards` && l.length < y + S * 2)
        ? x(
              `Swiper Loop Warning: The number of slides is not enough for loop mode, it will be disabled or not function properly. You need to add more slides (or make duplicates) or lower the values of slidesPerView and slidesPerGroup parameters`,
          )
        : C &&
          p.grid.fill === `row` &&
          x(
              'Swiper Loop Warning: Loop mode is not compatible with grid.fill = `row`',
          )
    let w = [],
        T = [],
        E = C ? Math.ceil(l.length / p.grid.rows) : l.length,
        D = a && E - _ < y && !v,
        O = D ? _ : c.activeIndex
    i === void 0
        ? (i = c.getSlideIndex(
              l.find((e) => e.classList.contains(p.slideActiveClass)),
          ))
        : (O = i)
    let k = n === `next` || !n,
        A = n === `prev` || !n,
        j = 0,
        M = 0,
        N = (C ? l[i].column : i) + (v && r === void 0 ? -y / 2 + 0.5 : 0)
    if (N < S) {
        j = Math.max(S - N, b)
        for (let e = 0; e < S - N; e += 1) {
            let t = e - Math.floor(e / E) * E
            if (C) {
                let e = E - t - 1
                for (let t = l.length - 1; t >= 0; --t)
                    l[t].column === e && w.push(t)
            } else w.push(E - t - 1)
        }
    } else if (N + y > E - S) {
        ;((M = Math.max(N - (E - S * 2), b)),
            D && (M = Math.max(M, y - E + _ + 1)))
        for (let e = 0; e < M; e += 1) {
            let t = e - Math.floor(e / E) * E
            C
                ? l.forEach((e, n) => {
                      e.column === t && T.push(n)
                  })
                : T.push(t)
        }
    }
    if (
        ((c.__preventObserver__ = !0),
        requestAnimationFrame(() => {
            c.__preventObserver__ = !1
        }),
        c.params.effect === `cards` &&
            l.length < y + S * 2 &&
            (T.includes(i) && T.splice(T.indexOf(i), 1),
            w.includes(i) && w.splice(w.indexOf(i), 1)),
        A &&
            w.forEach((e) => {
                ;((l[e].swiperLoopMoveDOM = !0),
                    f.prepend(l[e]),
                    (l[e].swiperLoopMoveDOM = !1))
            }),
        k &&
            T.forEach((e) => {
                ;((l[e].swiperLoopMoveDOM = !0),
                    f.append(l[e]),
                    (l[e].swiperLoopMoveDOM = !1))
            }),
        c.recalcSlides(),
        p.slidesPerView === `auto`
            ? c.updateSlides()
            : C &&
              ((w.length > 0 && A) || (T.length > 0 && k)) &&
              c.slides.forEach((e, t) => {
                  c.grid.updateSlide(t, e, c.slides)
              }),
        p.watchSlidesProgress && c.updateSlidesOffset(),
        t)
    ) {
        if (w.length > 0 && A) {
            if (e === void 0) {
                let e = c.slidesGrid[O],
                    t = c.slidesGrid[O + j] - e
                s
                    ? c.setTranslate(c.translate - t)
                    : (c.slideTo(O + Math.ceil(j), 0, !1, !0),
                      r &&
                          ((c.touchEventsData.startTranslate =
                              c.touchEventsData.startTranslate - t),
                          (c.touchEventsData.currentTranslate =
                              c.touchEventsData.currentTranslate - t)))
            } else if (r) {
                let e = C ? w.length / p.grid.rows : w.length
                ;(c.slideTo(c.activeIndex + e, 0, !1, !0),
                    (c.touchEventsData.currentTranslate = c.translate))
            }
        } else if (T.length > 0 && k)
            if (e === void 0) {
                let e = c.slidesGrid[O],
                    t = c.slidesGrid[O - M] - e
                s
                    ? c.setTranslate(c.translate - t)
                    : (c.slideTo(O - M, 0, !1, !0),
                      r &&
                          ((c.touchEventsData.startTranslate =
                              c.touchEventsData.startTranslate - t),
                          (c.touchEventsData.currentTranslate =
                              c.touchEventsData.currentTranslate - t)))
            } else {
                let e = C ? T.length / p.grid.rows : T.length
                c.slideTo(c.activeIndex - e, 0, !1, !0)
            }
    }
    if (
        ((c.allowSlidePrev = u),
        (c.allowSlideNext = d),
        c.controller && c.controller.control && !o)
    ) {
        let a = {
            slideRealIndex: e,
            direction: n,
            setTranslate: r,
            activeSlideIndex: i,
            byController: !0,
        }
        Array.isArray(c.controller.control)
            ? c.controller.control.forEach((e) => {
                  !e.destroyed &&
                      e.params.loop &&
                      e.loopFix({
                          ...a,
                          slideTo:
                              e.params.slidesPerView === p.slidesPerView
                                  ? t
                                  : !1,
                      })
              })
            : c.controller.control instanceof c.constructor &&
              c.controller.control.params.loop &&
              c.controller.control.loopFix({
                  ...a,
                  slideTo:
                      c.controller.control.params.slidesPerView ===
                      p.slidesPerView
                          ? t
                          : !1,
              })
    }
    c.emit(`loopFix`)
}
function Be() {
    let e = this,
        { params: t, slidesEl: n } = e
    if (!t.loop || !n || (e.virtual && e.params.virtual.enabled)) return
    e.recalcSlides()
    let r = []
    ;(e.slides.forEach((e) => {
        let t =
            e.swiperSlideIndex === void 0
                ? e.getAttribute(`data-swiper-slide-index`) * 1
                : e.swiperSlideIndex
        r[t] = e
    }),
        e.slides.forEach((e) => {
            e.removeAttribute(`data-swiper-slide-index`)
        }),
        r.forEach((e) => {
            n.append(e)
        }),
        e.recalcSlides(),
        e.slideTo(e.realIndex, 0))
}
var Ve = { loopCreate: Re, loopFix: ze, loopDestroy: Be }
function He(e) {
    let t = this
    if (
        !t.params.simulateTouch ||
        (t.params.watchOverflow && t.isLocked) ||
        t.params.cssMode
    )
        return
    let n = t.params.touchEventsTarget === `container` ? t.el : t.wrapperEl
    ;(t.isElement && (t.__preventObserver__ = !0),
        (n.style.cursor = `move`),
        (n.style.cursor = e ? `grabbing` : `grab`),
        t.isElement &&
            requestAnimationFrame(() => {
                t.__preventObserver__ = !1
            }))
}
function Ue() {
    let e = this
    ;(e.params.watchOverflow && e.isLocked) ||
        e.params.cssMode ||
        (e.isElement && (e.__preventObserver__ = !0),
        (e[
            e.params.touchEventsTarget === `container` ? `el` : `wrapperEl`
        ].style.cursor = ``),
        e.isElement &&
            requestAnimationFrame(() => {
                e.__preventObserver__ = !1
            }))
}
var We = { setGrabCursor: He, unsetGrabCursor: Ue }
function Ge(e, t = this) {
    function n(t) {
        if (!t || t === r() || t === a()) return null
        t.assignedSlot && (t = t.assignedSlot)
        let i = t.closest(e)
        return !i && !t.getRootNode ? null : i || n(t.getRootNode().host)
    }
    return n(t)
}
function V(e, t, n) {
    let r = a(),
        { params: i } = e,
        o = i.edgeSwipeDetection,
        s = i.edgeSwipeThreshold
    return o && (n <= s || n >= r.innerWidth - s)
        ? o === `prevent`
            ? (t.preventDefault(), !0)
            : !1
        : !0
}
function Ke(e) {
    let t = this,
        n = r(),
        i = e
    i.originalEvent && (i = i.originalEvent)
    let a = t.touchEventsData
    if (i.type === `pointerdown`) {
        if (a.pointerId !== null && a.pointerId !== i.pointerId) return
        a.pointerId = i.pointerId
    } else
        i.type === `touchstart` &&
            i.targetTouches.length === 1 &&
            (a.touchId = i.targetTouches[0].identifier)
    if (i.type === `touchstart`) {
        V(t, i, i.targetTouches[0].pageX)
        return
    }
    let { params: o, touches: s, enabled: c } = t
    if (
        !c ||
        (!o.simulateTouch && i.pointerType === `mouse`) ||
        (t.animating && o.preventInteractionOnTransition)
    )
        return
    !t.animating && o.cssMode && o.loop && t.loopFix()
    let u = i.target
    if (
        (o.touchEventsTarget === `wrapper` && !b(u, t.wrapperEl)) ||
        (`which` in i && i.which === 3) ||
        (`button` in i && i.button > 0) ||
        (a.isTouched && a.isMoved)
    )
        return
    let d = !!o.noSwipingClass && o.noSwipingClass !== ``,
        f = i.composedPath ? i.composedPath() : i.path
    d && i.target && i.target.shadowRoot && f && (u = f[0])
    let p = o.noSwipingSelector ? o.noSwipingSelector : `.${o.noSwipingClass}`,
        m = !!(i.target && i.target.shadowRoot)
    if (o.noSwiping && (m ? Ge(p, u) : u.closest(p))) {
        t.allowClick = !0
        return
    }
    if (o.swipeHandler && !u.closest(o.swipeHandler)) return
    ;((s.currentX = i.pageX), (s.currentY = i.pageY))
    let h = s.currentX,
        g = s.currentY
    if (!V(t, i, h)) return
    ;(Object.assign(a, {
        isTouched: !0,
        isMoved: !1,
        allowTouchCallbacks: !0,
        isScrolling: void 0,
        startMoving: void 0,
    }),
        (s.startX = h),
        (s.startY = g),
        (a.touchStartTime = l()),
        (t.allowClick = !0),
        t.updateSize(),
        (t.swipeDirection = void 0),
        o.threshold > 0 && (a.allowThresholdMove = !1))
    let _ = !0
    ;(u.matches(a.focusableElements) &&
        ((_ = !1), u.nodeName === `SELECT` && (a.isTouched = !1)),
        n.activeElement &&
            n.activeElement.matches(a.focusableElements) &&
            n.activeElement !== u &&
            (i.pointerType === `mouse` ||
                (i.pointerType !== `mouse` &&
                    !u.matches(a.focusableElements))) &&
            n.activeElement.blur())
    let v = _ && t.allowTouchMove && o.touchStartPreventDefault
    ;((o.touchStartForcePreventDefault || v) &&
        !u.isContentEditable &&
        i.preventDefault(),
        o.freeMode &&
            o.freeMode.enabled &&
            t.freeMode &&
            t.animating &&
            !o.cssMode &&
            t.freeMode.onTouchStart(),
        t.emit(`touchStart`, i))
}
function qe(e) {
    let t = r(),
        n = this,
        i = n.touchEventsData,
        { params: a, touches: o, rtlTranslate: s, enabled: c } = n
    if (!c || (!a.simulateTouch && e.pointerType === `mouse`)) return
    let u = e
    if (
        (u.originalEvent && (u = u.originalEvent),
        u.type === `pointermove` &&
            (i.touchId !== null || u.pointerId !== i.pointerId))
    )
        return
    let d
    if (u.type === `touchmove`) {
        if (
            ((d = [...u.changedTouches].find(
                (e) => e.identifier === i.touchId,
            )),
            !d || d.identifier !== i.touchId)
        )
            return
    } else d = u
    if (!i.isTouched) {
        i.startMoving && i.isScrolling && n.emit(`touchMoveOpposite`, u)
        return
    }
    let f = d.pageX,
        p = d.pageY
    if (u.preventedByNestedSwiper) {
        ;((o.startX = f), (o.startY = p))
        return
    }
    if (!n.allowTouchMove) {
        ;(u.target.matches(i.focusableElements) || (n.allowClick = !1),
            i.isTouched &&
                (Object.assign(o, {
                    startX: f,
                    startY: p,
                    currentX: f,
                    currentY: p,
                }),
                (i.touchStartTime = l())))
        return
    }
    if (a.touchReleaseOnEdges && !a.loop) {
        if (n.isVertical()) {
            if (
                (p < o.startY && n.translate <= n.maxTranslate()) ||
                (p > o.startY && n.translate >= n.minTranslate())
            ) {
                ;((i.isTouched = !1), (i.isMoved = !1))
                return
            }
        } else if (
            s &&
            ((f > o.startX && -n.translate <= n.maxTranslate()) ||
                (f < o.startX && -n.translate >= n.minTranslate()))
        )
            return
        else if (
            !s &&
            ((f < o.startX && n.translate <= n.maxTranslate()) ||
                (f > o.startX && n.translate >= n.minTranslate()))
        )
            return
    }
    if (
        (t.activeElement &&
            t.activeElement.matches(i.focusableElements) &&
            t.activeElement !== u.target &&
            u.pointerType !== `mouse` &&
            t.activeElement.blur(),
        t.activeElement &&
            u.target === t.activeElement &&
            u.target.matches(i.focusableElements))
    ) {
        ;((i.isMoved = !0), (n.allowClick = !1))
        return
    }
    ;(i.allowTouchCallbacks && n.emit(`touchMove`, u),
        (o.previousX = o.currentX),
        (o.previousY = o.currentY),
        (o.currentX = f),
        (o.currentY = p))
    let m = o.currentX - o.startX,
        h = o.currentY - o.startY
    if (n.params.threshold && Math.sqrt(m ** 2 + h ** 2) < n.params.threshold)
        return
    if (i.isScrolling === void 0) {
        let e
        ;(n.isHorizontal() && o.currentY === o.startY) ||
        (n.isVertical() && o.currentX === o.startX)
            ? (i.isScrolling = !1)
            : m * m + h * h >= 25 &&
              ((e = (Math.atan2(Math.abs(h), Math.abs(m)) * 180) / Math.PI),
              (i.isScrolling = n.isHorizontal()
                  ? e > a.touchAngle
                  : 90 - e > a.touchAngle))
    }
    if (
        (i.isScrolling && n.emit(`touchMoveOpposite`, u),
        i.startMoving === void 0 &&
            (o.currentX !== o.startX || o.currentY !== o.startY) &&
            (i.startMoving = !0),
        i.isScrolling ||
            (u.type === `touchmove` && i.preventTouchMoveFromPointerMove))
    ) {
        i.isTouched = !1
        return
    }
    if (!i.startMoving) return
    ;((n.allowClick = !1),
        !a.cssMode && u.cancelable && u.preventDefault(),
        a.touchMoveStopPropagation && !a.nested && u.stopPropagation())
    let g = n.isHorizontal() ? m : h,
        _ = n.isHorizontal()
            ? o.currentX - o.previousX
            : o.currentY - o.previousY
    ;(a.oneWayMovement &&
        ((g = Math.abs(g) * (s ? 1 : -1)), (_ = Math.abs(_) * (s ? 1 : -1))),
        (o.diff = g),
        (g *= a.touchRatio),
        s && ((g = -g), (_ = -_)))
    let v = n.touchesDirection
    ;((n.swipeDirection = g > 0 ? `prev` : `next`),
        (n.touchesDirection = _ > 0 ? `prev` : `next`))
    let y = n.params.loop && !a.cssMode,
        b =
            (n.touchesDirection === `next` && n.allowSlideNext) ||
            (n.touchesDirection === `prev` && n.allowSlidePrev)
    if (!i.isMoved) {
        if (
            (y && b && n.loopFix({ direction: n.swipeDirection }),
            (i.startTranslate = n.getTranslate()),
            n.setTransition(0),
            n.animating)
        ) {
            let e = new window.CustomEvent(`transitionend`, {
                bubbles: !0,
                cancelable: !0,
                detail: { bySwiperTouchMove: !0 },
            })
            n.wrapperEl.dispatchEvent(e)
        }
        ;((i.allowMomentumBounce = !1),
            a.grabCursor &&
                (n.allowSlideNext === !0 || n.allowSlidePrev === !0) &&
                n.setGrabCursor(!0),
            n.emit(`sliderFirstMove`, u))
    }
    if (
        (new Date().getTime(),
        a._loopSwapReset !== !1 &&
            i.isMoved &&
            i.allowThresholdMove &&
            v !== n.touchesDirection &&
            y &&
            b &&
            Math.abs(g) >= 1)
    ) {
        ;(Object.assign(o, {
            startX: f,
            startY: p,
            currentX: f,
            currentY: p,
            startTranslate: i.currentTranslate,
        }),
            (i.loopSwapReset = !0),
            (i.startTranslate = i.currentTranslate))
        return
    }
    ;(n.emit(`sliderMove`, u),
        (i.isMoved = !0),
        (i.currentTranslate = g + i.startTranslate))
    let x = !0,
        S = a.resistanceRatio
    if (
        (a.touchReleaseOnEdges && (S = 0),
        g > 0
            ? (y &&
                  b &&
                  i.allowThresholdMove &&
                  i.currentTranslate >
                      (a.centeredSlides
                          ? n.minTranslate() -
                            n.slidesSizesGrid[n.activeIndex + 1] -
                            (a.slidesPerView !== `auto` &&
                            n.slides.length - a.slidesPerView >= 2
                                ? n.slidesSizesGrid[n.activeIndex + 1] +
                                  n.params.spaceBetween
                                : 0) -
                            n.params.spaceBetween
                          : n.minTranslate()) &&
                  n.loopFix({
                      direction: `prev`,
                      setTranslate: !0,
                      activeSlideIndex: 0,
                  }),
              i.currentTranslate > n.minTranslate() &&
                  ((x = !1),
                  a.resistance &&
                      (i.currentTranslate =
                          n.minTranslate() -
                          1 +
                          (-n.minTranslate() + i.startTranslate + g) ** S)))
            : g < 0 &&
              (y &&
                  b &&
                  i.allowThresholdMove &&
                  i.currentTranslate <
                      (a.centeredSlides
                          ? n.maxTranslate() +
                            n.slidesSizesGrid[n.slidesSizesGrid.length - 1] +
                            n.params.spaceBetween +
                            (a.slidesPerView !== `auto` &&
                            n.slides.length - a.slidesPerView >= 2
                                ? n.slidesSizesGrid[
                                      n.slidesSizesGrid.length - 1
                                  ] + n.params.spaceBetween
                                : 0)
                          : n.maxTranslate()) &&
                  n.loopFix({
                      direction: `next`,
                      setTranslate: !0,
                      activeSlideIndex:
                          n.slides.length -
                          (a.slidesPerView === `auto`
                              ? n.slidesPerViewDynamic()
                              : Math.ceil(parseFloat(a.slidesPerView, 10))),
                  }),
              i.currentTranslate < n.maxTranslate() &&
                  ((x = !1),
                  a.resistance &&
                      (i.currentTranslate =
                          n.maxTranslate() +
                          1 -
                          (n.maxTranslate() - i.startTranslate - g) ** S))),
        x && (u.preventedByNestedSwiper = !0),
        !n.allowSlideNext &&
            n.swipeDirection === `next` &&
            i.currentTranslate < i.startTranslate &&
            (i.currentTranslate = i.startTranslate),
        !n.allowSlidePrev &&
            n.swipeDirection === `prev` &&
            i.currentTranslate > i.startTranslate &&
            (i.currentTranslate = i.startTranslate),
        !n.allowSlidePrev &&
            !n.allowSlideNext &&
            (i.currentTranslate = i.startTranslate),
        a.threshold > 0)
    )
        if (Math.abs(g) > a.threshold || i.allowThresholdMove) {
            if (!i.allowThresholdMove) {
                ;((i.allowThresholdMove = !0),
                    (o.startX = o.currentX),
                    (o.startY = o.currentY),
                    (i.currentTranslate = i.startTranslate),
                    (o.diff = n.isHorizontal()
                        ? o.currentX - o.startX
                        : o.currentY - o.startY))
                return
            }
        } else {
            i.currentTranslate = i.startTranslate
            return
        }
    !a.followFinger ||
        a.cssMode ||
        (((a.freeMode && a.freeMode.enabled && n.freeMode) ||
            a.watchSlidesProgress) &&
            (n.updateActiveIndex(), n.updateSlidesClasses()),
        a.freeMode &&
            a.freeMode.enabled &&
            n.freeMode &&
            n.freeMode.onTouchMove(),
        n.updateProgress(i.currentTranslate),
        n.setTranslate(i.currentTranslate))
}
function Je(e) {
    let t = this,
        n = t.touchEventsData,
        r = e
    r.originalEvent && (r = r.originalEvent)
    let i
    if (!(r.type === `touchend` || r.type === `touchcancel`)) {
        if (n.touchId !== null || r.pointerId !== n.pointerId) return
        i = r
    } else if (
        ((i = [...r.changedTouches].find((e) => e.identifier === n.touchId)),
        !i || i.identifier !== n.touchId)
    )
        return
    if (
        [`pointercancel`, `pointerout`, `pointerleave`, `contextmenu`].includes(
            r.type,
        ) &&
        !(
            [`pointercancel`, `contextmenu`].includes(r.type) &&
            (t.browser.isSafari || t.browser.isWebView)
        )
    )
        return
    ;((n.pointerId = null), (n.touchId = null))
    let {
        params: a,
        touches: o,
        rtlTranslate: s,
        slidesGrid: u,
        enabled: d,
    } = t
    if (!d || (!a.simulateTouch && r.pointerType === `mouse`)) return
    if (
        (n.allowTouchCallbacks && t.emit(`touchEnd`, r),
        (n.allowTouchCallbacks = !1),
        !n.isTouched)
    ) {
        ;(n.isMoved && a.grabCursor && t.setGrabCursor(!1),
            (n.isMoved = !1),
            (n.startMoving = !1))
        return
    }
    a.grabCursor &&
        n.isMoved &&
        n.isTouched &&
        (t.allowSlideNext === !0 || t.allowSlidePrev === !0) &&
        t.setGrabCursor(!1)
    let f = l(),
        p = f - n.touchStartTime
    if (t.allowClick) {
        let e = r.path || (r.composedPath && r.composedPath())
        ;(t.updateClickedSlide((e && e[0]) || r.target, e),
            t.emit(`tap click`, r),
            p < 300 &&
                f - n.lastClickTime < 300 &&
                t.emit(`doubleTap doubleClick`, r))
    }
    if (
        ((n.lastClickTime = l()),
        c(() => {
            t.destroyed || (t.allowClick = !0)
        }),
        !n.isTouched ||
            !n.isMoved ||
            !t.swipeDirection ||
            (o.diff === 0 && !n.loopSwapReset) ||
            (n.currentTranslate === n.startTranslate && !n.loopSwapReset))
    ) {
        ;((n.isTouched = !1), (n.isMoved = !1), (n.startMoving = !1))
        return
    }
    ;((n.isTouched = !1), (n.isMoved = !1), (n.startMoving = !1))
    let m
    if (
        ((m = a.followFinger
            ? s
                ? t.translate
                : -t.translate
            : -n.currentTranslate),
        a.cssMode)
    )
        return
    if (a.freeMode && a.freeMode.enabled) {
        t.freeMode.onTouchEnd({ currentPos: m })
        return
    }
    let h = m >= -t.maxTranslate() && !t.params.loop,
        g = 0,
        _ = t.slidesSizesGrid[0]
    for (
        let e = 0;
        e < u.length;
        e += e < a.slidesPerGroupSkip ? 1 : a.slidesPerGroup
    ) {
        let t = e < a.slidesPerGroupSkip - 1 ? 1 : a.slidesPerGroup
        u[e + t] === void 0
            ? (h || m >= u[e]) &&
              ((g = e), (_ = u[u.length - 1] - u[u.length - 2]))
            : (h || (m >= u[e] && m < u[e + t])) &&
              ((g = e), (_ = u[e + t] - u[e]))
    }
    let v = null,
        y = null
    a.rewind &&
        (t.isBeginning
            ? (y =
                  a.virtual && a.virtual.enabled && t.virtual
                      ? t.virtual.slides.length - 1
                      : t.slides.length - 1)
            : t.isEnd && (v = 0))
    let b = (m - u[g]) / _,
        x = g < a.slidesPerGroupSkip - 1 ? 1 : a.slidesPerGroup
    if (p > a.longSwipesMs) {
        if (!a.longSwipes) {
            t.slideTo(t.activeIndex)
            return
        }
        ;(t.swipeDirection === `next` &&
            (b >= a.longSwipesRatio
                ? t.slideTo(a.rewind && t.isEnd ? v : g + x)
                : t.slideTo(g)),
            t.swipeDirection === `prev` &&
                (b > 1 - a.longSwipesRatio
                    ? t.slideTo(g + x)
                    : y !== null && b < 0 && Math.abs(b) > a.longSwipesRatio
                      ? t.slideTo(y)
                      : t.slideTo(g)))
    } else {
        if (!a.shortSwipes) {
            t.slideTo(t.activeIndex)
            return
        }
        t.navigation &&
        (r.target === t.navigation.nextEl || r.target === t.navigation.prevEl)
            ? r.target === t.navigation.nextEl
                ? t.slideTo(g + x)
                : t.slideTo(g)
            : (t.swipeDirection === `next` && t.slideTo(v === null ? g + x : v),
              t.swipeDirection === `prev` && t.slideTo(y === null ? g : y))
    }
}
function H() {
    let e = this,
        { params: t, el: n } = e
    if (n && n.offsetWidth === 0) return
    t.breakpoints && e.setBreakpoint()
    let { allowSlideNext: r, allowSlidePrev: i, snapGrid: a } = e,
        o = e.virtual && e.params.virtual.enabled
    ;((e.allowSlideNext = !0),
        (e.allowSlidePrev = !0),
        e.updateSize(),
        e.updateSlides(),
        e.updateSlidesClasses())
    let s = o && t.loop
    if (
        (t.slidesPerView === `auto` || t.slidesPerView > 1) &&
        e.isEnd &&
        !e.isBeginning &&
        !e.params.centeredSlides &&
        !s
    ) {
        let t = o ? e.virtual.slides : e.slides
        e.slideTo(t.length - 1, 0, !1, !0)
    } else
        e.params.loop && !o
            ? e.slideToLoop(e.realIndex, 0, !1, !0)
            : e.slideTo(e.activeIndex, 0, !1, !0)
    ;(e.autoplay &&
        e.autoplay.running &&
        e.autoplay.paused &&
        (clearTimeout(e.autoplay.resizeTimeout),
        (e.autoplay.resizeTimeout = setTimeout(() => {
            e.autoplay &&
                e.autoplay.running &&
                e.autoplay.paused &&
                e.autoplay.resume()
        }, 500))),
        (e.allowSlidePrev = i),
        (e.allowSlideNext = r),
        e.params.watchOverflow && a !== e.snapGrid && e.checkOverflow())
}
function Ye(e) {
    let t = this
    t.enabled &&
        (t.allowClick ||
            (t.params.preventClicks && e.preventDefault(),
            t.params.preventClicksPropagation &&
                t.animating &&
                (e.stopPropagation(), e.stopImmediatePropagation())))
}
function Xe() {
    let e = this,
        { wrapperEl: t, rtlTranslate: n, enabled: r } = e
    if (!r) return
    ;((e.previousTranslate = e.translate),
        e.isHorizontal()
            ? (e.translate = -t.scrollLeft)
            : (e.translate = -t.scrollTop),
        e.translate === 0 && (e.translate = 0),
        e.updateActiveIndex(),
        e.updateSlidesClasses())
    let i,
        a = e.maxTranslate() - e.minTranslate()
    ;((i = a === 0 ? 0 : (e.translate - e.minTranslate()) / a),
        i !== e.progress && e.updateProgress(n ? -e.translate : e.translate),
        e.emit(`setTranslate`, e.translate, !1))
}
function Ze(e) {
    let t = this
    ;(L(t, e.target),
        !(
            t.params.cssMode ||
            (t.params.slidesPerView !== `auto` && !t.params.autoHeight)
        ) && t.update())
}
function Qe() {
    let e = this
    e.documentTouchHandlerProceeded ||
        ((e.documentTouchHandlerProceeded = !0),
        e.params.touchReleaseOnEdges && (e.el.style.touchAction = `auto`))
}
var $e = (e, t) => {
    let n = r(),
        { params: i, el: a, wrapperEl: o, device: s } = e,
        c = !!i.nested,
        l = t === `on` ? `addEventListener` : `removeEventListener`,
        u = t
    !a ||
        typeof a == `string` ||
        (n[l](`touchstart`, e.onDocumentTouchStart, {
            passive: !1,
            capture: c,
        }),
        a[l](`touchstart`, e.onTouchStart, { passive: !1 }),
        a[l](`pointerdown`, e.onTouchStart, { passive: !1 }),
        n[l](`touchmove`, e.onTouchMove, { passive: !1, capture: c }),
        n[l](`pointermove`, e.onTouchMove, { passive: !1, capture: c }),
        n[l](`touchend`, e.onTouchEnd, { passive: !0 }),
        n[l](`pointerup`, e.onTouchEnd, { passive: !0 }),
        n[l](`pointercancel`, e.onTouchEnd, { passive: !0 }),
        n[l](`touchcancel`, e.onTouchEnd, { passive: !0 }),
        n[l](`pointerout`, e.onTouchEnd, { passive: !0 }),
        n[l](`pointerleave`, e.onTouchEnd, { passive: !0 }),
        n[l](`contextmenu`, e.onTouchEnd, { passive: !0 }),
        (i.preventClicks || i.preventClicksPropagation) &&
            a[l](`click`, e.onClick, !0),
        i.cssMode && o[l](`scroll`, e.onScroll),
        i.updateOnWindowResize
            ? e[u](
                  s.ios || s.android
                      ? `resize orientationchange observerUpdate`
                      : `resize observerUpdate`,
                  H,
                  !0,
              )
            : e[u](`observerUpdate`, H, !0),
        a[l](`load`, e.onLoad, { capture: !0 }))
}
function et() {
    let e = this,
        { params: t } = e
    ;((e.onTouchStart = Ke.bind(e)),
        (e.onTouchMove = qe.bind(e)),
        (e.onTouchEnd = Je.bind(e)),
        (e.onDocumentTouchStart = Qe.bind(e)),
        t.cssMode && (e.onScroll = Xe.bind(e)),
        (e.onClick = Ye.bind(e)),
        (e.onLoad = Ze.bind(e)),
        $e(e, `on`))
}
function tt() {
    $e(this, `off`)
}
var nt = { attachEvents: et, detachEvents: tt },
    rt = (e, t) => e.grid && t.grid && t.grid.rows > 1
function it() {
    let e = this,
        { realIndex: t, initialized: n, params: i, el: a } = e,
        o = i.breakpoints
    if (!o || (o && Object.keys(o).length === 0)) return
    let s = r(),
        c =
            i.breakpointsBase === `window` || !i.breakpointsBase
                ? i.breakpointsBase
                : `container`,
        l =
            [`window`, `container`].includes(i.breakpointsBase) ||
            !i.breakpointsBase
                ? e.el
                : s.querySelector(i.breakpointsBase),
        u = e.getBreakpoint(o, c, l)
    if (!u || e.currentBreakpoint === u) return
    let d = (u in o ? o[u] : void 0) || e.originalParams,
        f = rt(e, i),
        p = rt(e, d),
        h = e.params.grabCursor,
        g = d.grabCursor,
        _ = i.enabled
    ;(f && !p
        ? (a.classList.remove(
              `${i.containerModifierClass}grid`,
              `${i.containerModifierClass}grid-column`,
          ),
          e.emitContainerClasses())
        : !f &&
          p &&
          (a.classList.add(`${i.containerModifierClass}grid`),
          ((d.grid.fill && d.grid.fill === `column`) ||
              (!d.grid.fill && i.grid.fill === `column`)) &&
              a.classList.add(`${i.containerModifierClass}grid-column`),
          e.emitContainerClasses()),
        h && !g ? e.unsetGrabCursor() : !h && g && e.setGrabCursor(),
        [`navigation`, `pagination`, `scrollbar`].forEach((t) => {
            if (d[t] === void 0) return
            let n = i[t] && i[t].enabled,
                r = d[t] && d[t].enabled
            ;(n && !r && e[t].disable(), !n && r && e[t].enable())
        }))
    let v = d.direction && d.direction !== i.direction,
        y = i.loop && (d.slidesPerView !== i.slidesPerView || v),
        b = i.loop
    ;(v && n && e.changeDirection(), m(e.params, d))
    let x = e.params.enabled,
        S = e.params.loop
    ;(Object.assign(e, {
        allowTouchMove: e.params.allowTouchMove,
        allowSlideNext: e.params.allowSlideNext,
        allowSlidePrev: e.params.allowSlidePrev,
    }),
        _ && !x ? e.disable() : !_ && x && e.enable(),
        (e.currentBreakpoint = u),
        e.emit(`_beforeBreakpoint`, d),
        n &&
            (y
                ? (e.loopDestroy(), e.loopCreate(t), e.updateSlides())
                : !b && S
                  ? (e.loopCreate(t), e.updateSlides())
                  : b && !S && e.loopDestroy()),
        e.emit(`breakpoint`, d))
}
function at(e, t = `window`, n) {
    if (!e || (t === `container` && !n)) return
    let r = !1,
        i = a(),
        o = t === `window` ? i.innerHeight : n.clientHeight,
        s = Object.keys(e).map((e) =>
            typeof e == `string` && e.indexOf(`@`) === 0
                ? { value: o * parseFloat(e.substr(1)), point: e }
                : { value: e, point: e },
        )
    s.sort((e, t) => parseInt(e.value, 10) - parseInt(t.value, 10))
    for (let e = 0; e < s.length; e += 1) {
        let { point: a, value: o } = s[e]
        t === `window`
            ? i.matchMedia(`(min-width: ${o}px)`).matches && (r = a)
            : o <= n.clientWidth && (r = a)
    }
    return r || `max`
}
var ot = { setBreakpoint: it, getBreakpoint: at }
function st(e, t) {
    let n = []
    return (
        e.forEach((e) => {
            typeof e == `object`
                ? Object.keys(e).forEach((r) => {
                      e[r] && n.push(t + r)
                  })
                : typeof e == `string` && n.push(t + e)
        }),
        n
    )
}
function ct() {
    let e = this,
        { classNames: t, params: n, rtl: r, el: i, device: a } = e,
        o = st(
            [
                `initialized`,
                n.direction,
                { 'free-mode': e.params.freeMode && n.freeMode.enabled },
                { autoheight: n.autoHeight },
                { rtl: r },
                { grid: n.grid && n.grid.rows > 1 },
                {
                    'grid-column':
                        n.grid && n.grid.rows > 1 && n.grid.fill === `column`,
                },
                { android: a.android },
                { ios: a.ios },
                { 'css-mode': n.cssMode },
                { centered: n.cssMode && n.centeredSlides },
                { 'watch-progress': n.watchSlidesProgress },
            ],
            n.containerModifierClass,
        )
    ;(t.push(...o), i.classList.add(...t), e.emitContainerClasses())
}
function lt() {
    let e = this,
        { el: t, classNames: n } = e
    !t ||
        typeof t == `string` ||
        (t.classList.remove(...n), e.emitContainerClasses())
}
var ut = { addClasses: ct, removeClasses: lt }
function dt() {
    let e = this,
        { isLocked: t, params: n } = e,
        { slidesOffsetBefore: r } = n
    if (r) {
        let t = e.slides.length - 1,
            n = e.slidesGrid[t] + e.slidesSizesGrid[t] + r * 2
        e.isLocked = e.size > n
    } else e.isLocked = e.snapGrid.length === 1
    ;(n.allowSlideNext === !0 && (e.allowSlideNext = !e.isLocked),
        n.allowSlidePrev === !0 && (e.allowSlidePrev = !e.isLocked),
        t && t !== e.isLocked && (e.isEnd = !1),
        t !== e.isLocked && e.emit(e.isLocked ? `lock` : `unlock`))
}
var ft = { checkOverflow: dt },
    pt = {
        init: !0,
        direction: `horizontal`,
        oneWayMovement: !1,
        swiperElementNodeName: `SWIPER-CONTAINER`,
        touchEventsTarget: `wrapper`,
        initialSlide: 0,
        speed: 300,
        cssMode: !1,
        updateOnWindowResize: !0,
        resizeObserver: !0,
        nested: !1,
        createElements: !1,
        eventsPrefix: `swiper`,
        enabled: !0,
        focusableElements: `input, select, option, textarea, button, video, label`,
        width: null,
        height: null,
        preventInteractionOnTransition: !1,
        userAgent: null,
        url: null,
        edgeSwipeDetection: !1,
        edgeSwipeThreshold: 20,
        autoHeight: !1,
        setWrapperSize: !1,
        virtualTranslate: !1,
        effect: `slide`,
        breakpoints: void 0,
        breakpointsBase: `window`,
        spaceBetween: 0,
        slidesPerView: 1,
        slidesPerGroup: 1,
        slidesPerGroupSkip: 0,
        slidesPerGroupAuto: !1,
        centeredSlides: !1,
        centeredSlidesBounds: !1,
        slidesOffsetBefore: 0,
        slidesOffsetAfter: 0,
        normalizeSlideIndex: !0,
        centerInsufficientSlides: !1,
        snapToSlideEdge: !1,
        watchOverflow: !0,
        roundLengths: !1,
        touchRatio: 1,
        touchAngle: 45,
        simulateTouch: !0,
        shortSwipes: !0,
        longSwipes: !0,
        longSwipesRatio: 0.5,
        longSwipesMs: 300,
        followFinger: !0,
        allowTouchMove: !0,
        threshold: 5,
        touchMoveStopPropagation: !1,
        touchStartPreventDefault: !0,
        touchStartForcePreventDefault: !1,
        touchReleaseOnEdges: !1,
        uniqueNavElements: !0,
        resistance: !0,
        resistanceRatio: 0.85,
        watchSlidesProgress: !1,
        grabCursor: !1,
        preventClicks: !0,
        preventClicksPropagation: !0,
        slideToClickedSlide: !1,
        loop: !1,
        loopAddBlankSlides: !0,
        loopAdditionalSlides: 0,
        loopPreventsSliding: !0,
        rewind: !1,
        allowSlidePrev: !0,
        allowSlideNext: !0,
        swipeHandler: null,
        noSwiping: !0,
        noSwipingClass: `swiper-no-swiping`,
        noSwipingSelector: null,
        passiveListeners: !0,
        maxBackfaceHiddenSlides: 10,
        containerModifierClass: `swiper-`,
        slideClass: `swiper-slide`,
        slideBlankClass: `swiper-slide-blank`,
        slideActiveClass: `swiper-slide-active`,
        slideVisibleClass: `swiper-slide-visible`,
        slideFullyVisibleClass: `swiper-slide-fully-visible`,
        slideNextClass: `swiper-slide-next`,
        slidePrevClass: `swiper-slide-prev`,
        wrapperClass: `swiper-wrapper`,
        lazyPreloaderClass: `swiper-lazy-preloader`,
        lazyPreloadPrevNext: 0,
        runCallbacksOnInit: !0,
        _emitClasses: !1,
    }
function mt(e, t) {
    return function (n = {}) {
        let r = Object.keys(n)[0],
            i = n[r]
        if (typeof i != `object` || !i) {
            m(t, n)
            return
        }
        if (
            (e[r] === !0 && (e[r] = { enabled: !0 }),
            r === `navigation` &&
                e[r] &&
                e[r].enabled &&
                !e[r].prevEl &&
                !e[r].nextEl &&
                (e[r].auto = !0),
            [`pagination`, `scrollbar`].indexOf(r) >= 0 &&
                e[r] &&
                e[r].enabled &&
                !e[r].el &&
                (e[r].auto = !0),
            !(r in e && `enabled` in i))
        ) {
            m(t, n)
            return
        }
        ;(typeof e[r] == `object` &&
            !(`enabled` in e[r]) &&
            (e[r].enabled = !0),
            e[r] || (e[r] = { enabled: !1 }),
            m(t, n))
    }
}
var U = {
        eventsEmitter: ce,
        update: ye,
        translate: Te,
        transition: ke,
        slide: Le,
        loop: Ve,
        grabCursor: We,
        events: nt,
        breakpoints: ot,
        checkOverflow: ft,
        classes: ut,
    },
    W = {},
    G = class e {
        constructor(...t) {
            let n, i
            ;(t.length === 1 &&
            t[0].constructor &&
            Object.prototype.toString.call(t[0]).slice(8, -1) === `Object`
                ? (i = t[0])
                : ([n, i] = t),
                (i ||= {}),
                (i = m({}, i)),
                n && !i.el && (i.el = n))
            let a = r()
            if (
                i.el &&
                typeof i.el == `string` &&
                a.querySelectorAll(i.el).length > 1
            ) {
                let t = []
                return (
                    a.querySelectorAll(i.el).forEach((n) => {
                        let r = m({}, i, { el: n })
                        t.push(new e(r))
                    }),
                    t
                )
            }
            let o = this
            ;((o.__swiper__ = !0),
                (o.support = P()),
                (o.device = ne({ userAgent: i.userAgent })),
                (o.browser = ae()),
                (o.eventsListeners = {}),
                (o.eventsAnyListeners = []),
                (o.modules = [...o.__modules__]),
                i.modules &&
                    Array.isArray(i.modules) &&
                    i.modules.forEach((e) => {
                        typeof e == `function` &&
                            o.modules.indexOf(e) < 0 &&
                            o.modules.push(e)
                    }))
            let s = {}
            return (
                o.modules.forEach((e) => {
                    e({
                        params: i,
                        swiper: o,
                        extendParams: mt(i, s),
                        on: o.on.bind(o),
                        once: o.once.bind(o),
                        off: o.off.bind(o),
                        emit: o.emit.bind(o),
                    })
                }),
                (o.params = m({}, m({}, pt, s), W, i)),
                (o.originalParams = m({}, o.params)),
                (o.passedParams = m({}, i)),
                o.params &&
                    o.params.on &&
                    Object.keys(o.params.on).forEach((e) => {
                        o.on(e, o.params.on[e])
                    }),
                o.params && o.params.onAny && o.onAny(o.params.onAny),
                Object.assign(o, {
                    enabled: o.params.enabled,
                    el: n,
                    classNames: [],
                    slides: [],
                    slidesGrid: [],
                    snapGrid: [],
                    slidesSizesGrid: [],
                    isHorizontal() {
                        return o.params.direction === `horizontal`
                    },
                    isVertical() {
                        return o.params.direction === `vertical`
                    },
                    activeIndex: 0,
                    realIndex: 0,
                    isBeginning: !0,
                    isEnd: !1,
                    translate: 0,
                    previousTranslate: 0,
                    progress: 0,
                    velocity: 0,
                    animating: !1,
                    cssOverflowAdjustment() {
                        return Math.trunc(this.translate / 2 ** 23) * 2 ** 23
                    },
                    allowSlideNext: o.params.allowSlideNext,
                    allowSlidePrev: o.params.allowSlidePrev,
                    touchEventsData: {
                        isTouched: void 0,
                        isMoved: void 0,
                        allowTouchCallbacks: void 0,
                        touchStartTime: void 0,
                        isScrolling: void 0,
                        currentTranslate: void 0,
                        startTranslate: void 0,
                        allowThresholdMove: void 0,
                        focusableElements: o.params.focusableElements,
                        lastClickTime: 0,
                        clickTimeout: void 0,
                        velocities: [],
                        allowMomentumBounce: void 0,
                        startMoving: void 0,
                        pointerId: null,
                        touchId: null,
                    },
                    allowClick: !0,
                    allowTouchMove: o.params.allowTouchMove,
                    touches: {
                        startX: 0,
                        startY: 0,
                        currentX: 0,
                        currentY: 0,
                        diff: 0,
                    },
                    imagesToLoad: [],
                    imagesLoaded: 0,
                }),
                o.emit(`_swiper`),
                o.params.init && o.init(),
                o
            )
        }
        getDirectionLabel(e) {
            return this.isHorizontal()
                ? e
                : {
                      width: `height`,
                      'margin-top': `margin-left`,
                      'margin-bottom ': `margin-right`,
                      'margin-left': `margin-top`,
                      'margin-right': `margin-bottom`,
                      'padding-left': `padding-top`,
                      'padding-right': `padding-bottom`,
                      marginRight: `marginBottom`,
                  }[e]
        }
        getSlideIndex(e) {
            let { slidesEl: t, params: n } = this,
                r = E(v(t, `.${n.slideClass}, swiper-slide`)[0])
            return E(e) - r
        }
        getSlideIndexByData(e) {
            return this.getSlideIndex(
                this.slides.find(
                    (t) => t.getAttribute(`data-swiper-slide-index`) * 1 === e,
                ),
            )
        }
        getSlideIndexWhenGrid(e) {
            return (
                this.grid &&
                    this.params.grid &&
                    this.params.grid.rows > 1 &&
                    (this.params.grid.fill === `column`
                        ? (e = Math.floor(e / this.params.grid.rows))
                        : this.params.grid.fill === `row` &&
                          (e %= Math.ceil(
                              this.slides.length / this.params.grid.rows,
                          ))),
                e
            )
        }
        recalcSlides() {
            let e = this,
                { slidesEl: t, params: n } = e
            e.slides = v(t, `.${n.slideClass}, swiper-slide`)
        }
        enable() {
            let e = this
            e.enabled ||
                ((e.enabled = !0),
                e.params.grabCursor && e.setGrabCursor(),
                e.emit(`enable`))
        }
        disable() {
            let e = this
            e.enabled &&
                ((e.enabled = !1),
                e.params.grabCursor && e.unsetGrabCursor(),
                e.emit(`disable`))
        }
        setProgress(e, t) {
            let n = this
            e = Math.min(Math.max(e, 0), 1)
            let r = n.minTranslate(),
                i = (n.maxTranslate() - r) * e + r
            ;(n.translateTo(i, t === void 0 ? 0 : t),
                n.updateActiveIndex(),
                n.updateSlidesClasses())
        }
        emitContainerClasses() {
            let e = this
            if (!e.params._emitClasses || !e.el) return
            let t = e.el.className
                .split(` `)
                .filter(
                    (t) =>
                        t.indexOf(`swiper`) === 0 ||
                        t.indexOf(e.params.containerModifierClass) === 0,
                )
            e.emit(`_containerClasses`, t.join(` `))
        }
        getSlideClasses(e) {
            let t = this
            return t.destroyed
                ? ``
                : e.className
                      .split(` `)
                      .filter(
                          (e) =>
                              e.indexOf(`swiper-slide`) === 0 ||
                              e.indexOf(t.params.slideClass) === 0,
                      )
                      .join(` `)
        }
        emitSlidesClasses() {
            let e = this
            if (!e.params._emitClasses || !e.el) return
            let t = []
            ;(e.slides.forEach((n) => {
                let r = e.getSlideClasses(n)
                ;(t.push({ slideEl: n, classNames: r }),
                    e.emit(`_slideClass`, n, r))
            }),
                e.emit(`_slideClasses`, t))
        }
        slidesPerViewDynamic(e = `current`, t = !1) {
            let {
                    params: n,
                    slides: r,
                    slidesGrid: i,
                    slidesSizesGrid: a,
                    size: o,
                    activeIndex: s,
                } = this,
                c = 1
            if (typeof n.slidesPerView == `number`) return n.slidesPerView
            if (n.centeredSlides) {
                let e = r[s] ? Math.ceil(r[s].swiperSlideSize) : 0,
                    t
                for (let n = s + 1; n < r.length; n += 1)
                    r[n] &&
                        !t &&
                        ((e += Math.ceil(r[n].swiperSlideSize)),
                        (c += 1),
                        e > o && (t = !0))
                for (let n = s - 1; n >= 0; --n)
                    r[n] &&
                        !t &&
                        ((e += r[n].swiperSlideSize),
                        (c += 1),
                        e > o && (t = !0))
            } else if (e === `current`)
                for (let e = s + 1; e < r.length; e += 1)
                    (t ? i[e] + a[e] - i[s] < o : i[e] - i[s] < o) && (c += 1)
            else for (let e = s - 1; e >= 0; --e) i[s] - i[e] < o && (c += 1)
            return c
        }
        update() {
            let e = this
            if (!e || e.destroyed) return
            let { snapGrid: t, params: n } = e
            ;(n.breakpoints && e.setBreakpoint(),
                [...e.el.querySelectorAll(`[loading="lazy"]`)].forEach((t) => {
                    t.complete && L(e, t)
                }),
                e.updateSize(),
                e.updateSlides(),
                e.updateProgress(),
                e.updateSlidesClasses())
            function r() {
                let t = e.rtlTranslate ? e.translate * -1 : e.translate,
                    n = Math.min(
                        Math.max(t, e.maxTranslate()),
                        e.minTranslate(),
                    )
                ;(e.setTranslate(n),
                    e.updateActiveIndex(),
                    e.updateSlidesClasses())
            }
            let i
            if (n.freeMode && n.freeMode.enabled && !n.cssMode)
                (r(), n.autoHeight && e.updateAutoHeight())
            else {
                if (
                    (n.slidesPerView === `auto` || n.slidesPerView > 1) &&
                    e.isEnd &&
                    !n.centeredSlides
                ) {
                    let t =
                        e.virtual && n.virtual.enabled
                            ? e.virtual.slides
                            : e.slides
                    i = e.slideTo(t.length - 1, 0, !1, !0)
                } else i = e.slideTo(e.activeIndex, 0, !1, !0)
                i || r()
            }
            ;(n.watchOverflow && t !== e.snapGrid && e.checkOverflow(),
                e.emit(`update`))
        }
        changeDirection(e, t = !0) {
            let n = this,
                r = n.params.direction
            return (
                (e ||= r === `horizontal` ? `vertical` : `horizontal`),
                e === r || (e !== `horizontal` && e !== `vertical`)
                    ? n
                    : (n.el.classList.remove(
                          `${n.params.containerModifierClass}${r}`,
                      ),
                      n.el.classList.add(
                          `${n.params.containerModifierClass}${e}`,
                      ),
                      n.emitContainerClasses(),
                      (n.params.direction = e),
                      n.slides.forEach((t) => {
                          e === `vertical`
                              ? (t.style.width = ``)
                              : (t.style.height = ``)
                      }),
                      n.emit(`changeDirection`),
                      t && n.update(),
                      n)
            )
        }
        changeLanguageDirection(e) {
            let t = this
            ;(t.rtl && e === `rtl`) ||
                (!t.rtl && e === `ltr`) ||
                ((t.rtl = e === `rtl`),
                (t.rtlTranslate = t.params.direction === `horizontal` && t.rtl),
                t.rtl
                    ? (t.el.classList.add(
                          `${t.params.containerModifierClass}rtl`,
                      ),
                      (t.el.dir = `rtl`))
                    : (t.el.classList.remove(
                          `${t.params.containerModifierClass}rtl`,
                      ),
                      (t.el.dir = `ltr`)),
                t.update())
        }
        mount(e) {
            let t = this
            if (t.mounted) return !0
            let n = e || t.params.el
            if ((typeof n == `string` && (n = document.querySelector(n)), !n))
                return !1
            ;((n.swiper = t),
                n.parentNode &&
                    n.parentNode.host &&
                    n.parentNode.host.nodeName ===
                        t.params.swiperElementNodeName.toUpperCase() &&
                    (t.isElement = !0))
            let r = () =>
                    `.${(t.params.wrapperClass || ``).trim().split(` `).join(`.`)}`,
                i =
                    n && n.shadowRoot && n.shadowRoot.querySelector
                        ? n.shadowRoot.querySelector(r())
                        : v(n, r())[0]
            return (
                !i &&
                    t.params.createElements &&
                    ((i = S(`div`, t.params.wrapperClass)),
                    n.append(i),
                    v(n, `.${t.params.slideClass}`).forEach((e) => {
                        i.append(e)
                    })),
                Object.assign(t, {
                    el: n,
                    wrapperEl: i,
                    slidesEl:
                        t.isElement && !n.parentNode.host.slideSlots
                            ? n.parentNode.host
                            : i,
                    hostEl: t.isElement ? n.parentNode.host : n,
                    mounted: !0,
                    rtl:
                        n.dir.toLowerCase() === `rtl` ||
                        T(n, `direction`) === `rtl`,
                    rtlTranslate:
                        t.params.direction === `horizontal` &&
                        (n.dir.toLowerCase() === `rtl` ||
                            T(n, `direction`) === `rtl`),
                    wrongRTL: T(i, `display`) === `-webkit-box`,
                }),
                !0
            )
        }
        init(e) {
            let t = this
            if (t.initialized || t.mount(e) === !1) return t
            ;(t.emit(`beforeInit`),
                t.params.breakpoints && t.setBreakpoint(),
                t.addClasses(),
                t.updateSize(),
                t.updateSlides(),
                t.params.watchOverflow && t.checkOverflow(),
                t.params.grabCursor && t.enabled && t.setGrabCursor(),
                t.params.loop && t.virtual && t.params.virtual.enabled
                    ? t.slideTo(
                          t.params.initialSlide + t.virtual.slidesBefore,
                          0,
                          t.params.runCallbacksOnInit,
                          !1,
                          !0,
                      )
                    : t.slideTo(
                          t.params.initialSlide,
                          0,
                          t.params.runCallbacksOnInit,
                          !1,
                          !0,
                      ),
                t.params.loop && t.loopCreate(void 0, !0),
                t.attachEvents())
            let n = [...t.el.querySelectorAll(`[loading="lazy"]`)]
            return (
                t.isElement &&
                    n.push(...t.hostEl.querySelectorAll(`[loading="lazy"]`)),
                n.forEach((e) => {
                    e.complete
                        ? L(t, e)
                        : e.addEventListener(`load`, (e) => {
                              L(t, e.target)
                          })
                }),
                z(t),
                (t.initialized = !0),
                z(t),
                t.emit(`init`),
                t.emit(`afterInit`),
                t
            )
        }
        destroy(e = !0, t = !0) {
            let n = this,
                { params: r, el: i, wrapperEl: a, slides: o } = n
            return n.params === void 0 || n.destroyed
                ? null
                : (n.emit(`beforeDestroy`),
                  (n.initialized = !1),
                  n.detachEvents(),
                  r.loop && n.loopDestroy(),
                  t &&
                      (n.removeClasses(),
                      i && typeof i != `string` && i.removeAttribute(`style`),
                      a && a.removeAttribute(`style`),
                      o &&
                          o.length &&
                          o.forEach((e) => {
                              ;(e.classList.remove(
                                  r.slideVisibleClass,
                                  r.slideFullyVisibleClass,
                                  r.slideActiveClass,
                                  r.slideNextClass,
                                  r.slidePrevClass,
                              ),
                                  e.removeAttribute(`style`),
                                  e.removeAttribute(`data-swiper-slide-index`))
                          })),
                  n.emit(`destroy`),
                  Object.keys(n.eventsListeners).forEach((e) => {
                      n.off(e)
                  }),
                  e !== !1 &&
                      (n.el && typeof n.el != `string` && (n.el.swiper = null),
                      s(n)),
                  (n.destroyed = !0),
                  null)
        }
        static extendDefaults(e) {
            m(W, e)
        }
        static get extendedDefaults() {
            return W
        }
        static get defaults() {
            return pt
        }
        static installModule(t) {
            e.prototype.__modules__ || (e.prototype.__modules__ = [])
            let n = e.prototype.__modules__
            typeof t == `function` && n.indexOf(t) < 0 && n.push(t)
        }
        static use(t) {
            return Array.isArray(t)
                ? (t.forEach((t) => e.installModule(t)), e)
                : (e.installModule(t), e)
        }
    }
;(Object.keys(U).forEach((e) => {
    Object.keys(U[e]).forEach((t) => {
        G.prototype[t] = U[e][t]
    })
}),
    G.use([oe, se]))
function ht({ swiper: e, extendParams: t, on: n, emit: r }) {
    let i = a()
    ;(t({
        mousewheel: {
            enabled: !1,
            releaseOnEdges: !1,
            invert: !1,
            forceToAxis: !1,
            sensitivity: 1,
            eventsTarget: `container`,
            thresholdDelta: null,
            thresholdTime: null,
            noMousewheelClass: `swiper-no-mousewheel`,
        },
    }),
        (e.mousewheel = { enabled: !1 }))
    let o,
        s = l(),
        u,
        d = []
    function f(e) {
        let t = 0,
            n = 0,
            r = 0,
            i = 0
        return (
            `detail` in e && (n = e.detail),
            `wheelDelta` in e && (n = -e.wheelDelta / 120),
            `wheelDeltaY` in e && (n = -e.wheelDeltaY / 120),
            `wheelDeltaX` in e && (t = -e.wheelDeltaX / 120),
            `axis` in e && e.axis === e.HORIZONTAL_AXIS && ((t = n), (n = 0)),
            (r = t * 10),
            (i = n * 10),
            `deltaY` in e && (i = e.deltaY),
            `deltaX` in e && (r = e.deltaX),
            e.shiftKey && !r && ((r = i), (i = 0)),
            (r || i) &&
                e.deltaMode &&
                (e.deltaMode === 1
                    ? ((r *= 40), (i *= 40))
                    : ((r *= 800), (i *= 800))),
            r && !t && (t = r < 1 ? -1 : 1),
            i && !n && (n = i < 1 ? -1 : 1),
            { spinX: t, spinY: n, pixelX: r, pixelY: i }
        )
    }
    function p() {
        e.enabled && (e.mouseEntered = !0)
    }
    function m() {
        e.enabled && (e.mouseEntered = !1)
    }
    function h(t) {
        return (e.params.mousewheel.thresholdDelta &&
            t.delta < e.params.mousewheel.thresholdDelta) ||
            (e.params.mousewheel.thresholdTime &&
                l() - s < e.params.mousewheel.thresholdTime)
            ? !1
            : t.delta >= 6 && l() - s < 60
              ? !0
              : (t.direction < 0
                    ? (!e.isEnd || e.params.loop) &&
                      !e.animating &&
                      (e.slideNext(), r(`scroll`, t.raw))
                    : (!e.isBeginning || e.params.loop) &&
                      !e.animating &&
                      (e.slidePrev(), r(`scroll`, t.raw)),
                (s = new i.Date().getTime()),
                !1)
    }
    function g(t) {
        let n = e.params.mousewheel
        if (t.direction < 0) {
            if (e.isEnd && !e.params.loop && n.releaseOnEdges) return !0
        } else if (e.isBeginning && !e.params.loop && n.releaseOnEdges)
            return !0
        return !1
    }
    function _(t) {
        let n = t,
            i = !0
        if (
            !e.enabled ||
            t.target.closest(`.${e.params.mousewheel.noMousewheelClass}`)
        )
            return
        let a = e.params.mousewheel
        e.params.cssMode && n.preventDefault()
        let s = e.el
        e.params.mousewheel.eventsTarget !== `container` &&
            (s = document.querySelector(e.params.mousewheel.eventsTarget))
        let p = s && s.contains(n.target)
        if (!e.mouseEntered && !p && !a.releaseOnEdges) return !0
        n.originalEvent && (n = n.originalEvent)
        let m = 0,
            _ = e.rtlTranslate ? -1 : 1,
            v = f(n)
        if (a.forceToAxis)
            if (e.isHorizontal())
                if (Math.abs(v.pixelX) > Math.abs(v.pixelY)) m = -v.pixelX * _
                else return !0
            else if (Math.abs(v.pixelY) > Math.abs(v.pixelX)) m = -v.pixelY
            else return !0
        else
            m =
                Math.abs(v.pixelX) > Math.abs(v.pixelY)
                    ? -v.pixelX * _
                    : -v.pixelY
        if (m === 0) return !0
        a.invert && (m = -m)
        let y = e.getTranslate() + m * a.sensitivity
        if (
            (y >= e.minTranslate() && (y = e.minTranslate()),
            y <= e.maxTranslate() && (y = e.maxTranslate()),
            (i = e.params.loop
                ? !0
                : !(y === e.minTranslate() || y === e.maxTranslate())),
            i && e.params.nested && n.stopPropagation(),
            !e.params.freeMode || !e.params.freeMode.enabled)
        ) {
            let e = {
                time: l(),
                delta: Math.abs(m),
                direction: Math.sign(m),
                raw: t,
            }
            d.length >= 2 && d.shift()
            let n = d.length ? d[d.length - 1] : void 0
            if (
                (d.push(e),
                n
                    ? (e.direction !== n.direction ||
                          e.delta > n.delta ||
                          e.time > n.time + 150) &&
                      h(e)
                    : h(e),
                g(e))
            )
                return !0
        } else {
            let t = { time: l(), delta: Math.abs(m), direction: Math.sign(m) },
                i =
                    u &&
                    t.time < u.time + 500 &&
                    t.delta <= u.delta &&
                    t.direction === u.direction
            if (!i) {
                u = void 0
                let s = e.getTranslate() + m * a.sensitivity,
                    l = e.isBeginning,
                    f = e.isEnd
                if (
                    (s >= e.minTranslate() && (s = e.minTranslate()),
                    s <= e.maxTranslate() && (s = e.maxTranslate()),
                    e.setTransition(0),
                    e.setTranslate(s),
                    e.updateProgress(),
                    e.updateActiveIndex(),
                    e.updateSlidesClasses(),
                    ((!l && e.isBeginning) || (!f && e.isEnd)) &&
                        e.updateSlidesClasses(),
                    e.params.loop &&
                        e.loopFix({
                            direction: t.direction < 0 ? `next` : `prev`,
                            byMousewheel: !0,
                        }),
                    e.params.freeMode.sticky)
                ) {
                    ;(clearTimeout(o),
                        (o = void 0),
                        d.length >= 15 && d.shift())
                    let n = d.length ? d[d.length - 1] : void 0,
                        r = d[0]
                    if (
                        (d.push(t),
                        n && (t.delta > n.delta || t.direction !== n.direction))
                    )
                        d.splice(0)
                    else if (
                        d.length >= 15 &&
                        t.time - r.time < 500 &&
                        r.delta - t.delta >= 1 &&
                        t.delta <= 6
                    ) {
                        let n = m > 0 ? 0.8 : 0.2
                        ;((u = t),
                            d.splice(0),
                            (o = c(() => {
                                e.destroyed ||
                                    !e.params ||
                                    e.slideToClosest(
                                        e.params.speed,
                                        !0,
                                        void 0,
                                        n,
                                    )
                            }, 0)))
                    }
                    o ||= c(() => {
                        e.destroyed ||
                            !e.params ||
                            ((u = t),
                            d.splice(0),
                            e.slideToClosest(e.params.speed, !0, void 0, 0.5))
                    }, 500)
                }
                if (
                    (i || r(`scroll`, n),
                    e.params.autoplay &&
                        e.params.autoplay.disableOnInteraction &&
                        e.autoplay.stop(),
                    a.releaseOnEdges &&
                        (s === e.minTranslate() || s === e.maxTranslate()))
                )
                    return !0
            }
        }
        return (
            n.preventDefault ? n.preventDefault() : (n.returnValue = !1),
            !1
        )
    }
    function v(t) {
        let n = e.el
        ;(e.params.mousewheel.eventsTarget !== `container` &&
            (n = document.querySelector(e.params.mousewheel.eventsTarget)),
            n[t](`mouseenter`, p),
            n[t](`mouseleave`, m),
            n[t](`wheel`, _))
    }
    function y() {
        return e.params.cssMode
            ? (e.wrapperEl.removeEventListener(`wheel`, _), !0)
            : e.mousewheel.enabled
              ? !1
              : (v(`addEventListener`), (e.mousewheel.enabled = !0), !0)
    }
    function b() {
        return e.params.cssMode
            ? (e.wrapperEl.addEventListener(event, _), !0)
            : e.mousewheel.enabled
              ? (v(`removeEventListener`), (e.mousewheel.enabled = !1), !0)
              : !1
    }
    ;(n(`init`, () => {
        ;(!e.params.mousewheel.enabled && e.params.cssMode && b(),
            e.params.mousewheel.enabled && y())
    }),
        n(`destroy`, () => {
            ;(e.params.cssMode && y(), e.mousewheel.enabled && b())
        }),
        Object.assign(e.mousewheel, { enable: y, disable: b }))
}
function gt(e, t, n, r) {
    return (
        e.params.createElements &&
            Object.keys(r).forEach((i) => {
                if (!n[i] && n.auto === !0) {
                    let a = v(e.el, `.${r[i]}`)[0]
                    ;(a ||
                        ((a = S(`div`, r[i])),
                        (a.className = r[i]),
                        e.el.append(a)),
                        (n[i] = a),
                        (t[i] = a))
                }
            }),
        n
    )
}
var _t = `<svg class="swiper-navigation-icon" width="11" height="20" viewBox="0 0 11 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.38296 20.0762C0.111788 19.805 0.111788 19.3654 0.38296 19.0942L9.19758 10.2796L0.38296 1.46497C0.111788 1.19379 0.111788 0.754138 0.38296 0.482966C0.654131 0.211794 1.09379 0.211794 1.36496 0.482966L10.4341 9.55214C10.8359 9.9539 10.8359 10.6053 10.4341 11.007L1.36496 20.0762C1.09379 20.3474 0.654131 20.3474 0.38296 20.0762Z" fill="currentColor"/></svg>`
function vt({ swiper: e, extendParams: t, on: n, emit: r }) {
    ;(t({
        navigation: {
            nextEl: null,
            prevEl: null,
            addIcons: !0,
            hideOnClick: !1,
            disabledClass: `swiper-button-disabled`,
            hiddenClass: `swiper-button-hidden`,
            lockClass: `swiper-button-lock`,
            navigationDisabledClass: `swiper-navigation-disabled`,
        },
    }),
        (e.navigation = { nextEl: null, prevEl: null, arrowSvg: _t }))
    function i(t) {
        let n
        return t &&
            typeof t == `string` &&
            e.isElement &&
            ((n = e.el.querySelector(t) || e.hostEl.querySelector(t)), n)
            ? n
            : (t &&
                  (typeof t == `string` &&
                      (n = [...document.querySelectorAll(t)]),
                  e.params.uniqueNavElements &&
                  typeof t == `string` &&
                  n &&
                  n.length > 1 &&
                  e.el.querySelectorAll(t).length === 1
                      ? (n = e.el.querySelector(t))
                      : n && n.length === 1 && (n = n[0])),
              t && !n ? t : n)
    }
    function a(t, n) {
        let r = e.params.navigation
        ;((t = A(t)),
            t.forEach((t) => {
                t &&
                    (t.classList[n ? `add` : `remove`](
                        ...r.disabledClass.split(` `),
                    ),
                    t.tagName === `BUTTON` && (t.disabled = n),
                    e.params.watchOverflow &&
                        e.enabled &&
                        t.classList[e.isLocked ? `add` : `remove`](r.lockClass))
            }))
    }
    function o() {
        let { nextEl: t, prevEl: n } = e.navigation
        if (e.params.loop) {
            ;(a(n, !1), a(t, !1))
            return
        }
        ;(a(n, e.isBeginning && !e.params.rewind),
            a(t, e.isEnd && !e.params.rewind))
    }
    function s(t) {
        ;(t.preventDefault(),
            !(e.isBeginning && !e.params.loop && !e.params.rewind) &&
                (e.slidePrev(), r(`navigationPrev`)))
    }
    function c(t) {
        ;(t.preventDefault(),
            !(e.isEnd && !e.params.loop && !e.params.rewind) &&
                (e.slideNext(), r(`navigationNext`)))
    }
    function l() {
        let t = e.params.navigation
        if (
            ((e.params.navigation = gt(
                e,
                e.originalParams.navigation,
                e.params.navigation,
                { nextEl: `swiper-button-next`, prevEl: `swiper-button-prev` },
            )),
            !(t.nextEl || t.prevEl))
        )
            return
        let n = i(t.nextEl),
            r = i(t.prevEl)
        ;(Object.assign(e.navigation, { nextEl: n, prevEl: r }),
            (n = A(n)),
            (r = A(r)))
        let a = (n, r) => {
            if (n) {
                if (
                    t.addIcons &&
                    n.matches(`.swiper-button-next,.swiper-button-prev`) &&
                    !n.querySelector(`svg`)
                ) {
                    let e = document.createElement(`div`)
                    ;(j(e, _t),
                        n.appendChild(e.querySelector(`svg`)),
                        e.remove())
                }
                n.addEventListener(`click`, r === `next` ? c : s)
            }
            !e.enabled && n && n.classList.add(...t.lockClass.split(` `))
        }
        ;(n.forEach((e) => a(e, `next`)), r.forEach((e) => a(e, `prev`)))
    }
    function u() {
        let { nextEl: t, prevEl: n } = e.navigation
        ;((t = A(t)), (n = A(n)))
        let r = (t, n) => {
            ;(t.removeEventListener(`click`, n === `next` ? c : s),
                t.classList.remove(
                    ...e.params.navigation.disabledClass.split(` `),
                ))
        }
        ;(t.forEach((e) => r(e, `next`)), n.forEach((e) => r(e, `prev`)))
    }
    ;(n(`init`, () => {
        e.params.navigation.enabled === !1 ? f() : (l(), o())
    }),
        n(`toEdge fromEdge lock unlock`, () => {
            o()
        }),
        n(`destroy`, () => {
            u()
        }),
        n(`enable disable`, () => {
            let { nextEl: t, prevEl: n } = e.navigation
            if (((t = A(t)), (n = A(n)), e.enabled)) {
                o()
                return
            }
            ;[...t, ...n]
                .filter((e) => !!e)
                .forEach((t) => t.classList.add(e.params.navigation.lockClass))
        }),
        n(`click`, (t, n) => {
            let { nextEl: i, prevEl: a } = e.navigation
            ;((i = A(i)), (a = A(a)))
            let o = n.target,
                s = a.includes(o) || i.includes(o)
            if (e.isElement && !s) {
                let e = n.path || (n.composedPath && n.composedPath())
                e && (s = e.find((e) => i.includes(e) || a.includes(e)))
            }
            if (e.params.navigation.hideOnClick && !s) {
                if (
                    e.pagination &&
                    e.params.pagination &&
                    e.params.pagination.clickable &&
                    (e.pagination.el === o || e.pagination.el.contains(o))
                )
                    return
                let t
                ;(i.length
                    ? (t = i[0].classList.contains(
                          e.params.navigation.hiddenClass,
                      ))
                    : a.length &&
                      (t = a[0].classList.contains(
                          e.params.navigation.hiddenClass,
                      )),
                    r(t === !0 ? `navigationShow` : `navigationHide`),
                    [...i, ...a]
                        .filter((e) => !!e)
                        .forEach((t) =>
                            t.classList.toggle(e.params.navigation.hiddenClass),
                        ))
            }
        }))
    let d = () => {
            ;(e.el.classList.remove(
                ...e.params.navigation.navigationDisabledClass.split(` `),
            ),
                l(),
                o())
        },
        f = () => {
            ;(e.el.classList.add(
                ...e.params.navigation.navigationDisabledClass.split(` `),
            ),
                u())
        }
    Object.assign(e.navigation, {
        enable: d,
        disable: f,
        update: o,
        init: l,
        destroy: u,
    })
}
function K(e = ``) {
    return `.${e
        .trim()
        .replace(/([\.:!+\/()[\]#>~*^$|=,'"@{}\\])/g, `\\$1`)
        .replace(/ /g, `.`)}`
}
function yt({ swiper: e, extendParams: t, on: n, emit: r }) {
    let i = `swiper-pagination`
    ;(t({
        pagination: {
            el: null,
            bulletElement: `span`,
            clickable: !1,
            hideOnClick: !1,
            renderBullet: null,
            renderProgressbar: null,
            renderFraction: null,
            renderCustom: null,
            progressbarOpposite: !1,
            type: `bullets`,
            dynamicBullets: !1,
            dynamicMainBullets: 1,
            formatFractionCurrent: (e) => e,
            formatFractionTotal: (e) => e,
            bulletClass: `${i}-bullet`,
            bulletActiveClass: `${i}-bullet-active`,
            modifierClass: `${i}-`,
            currentClass: `${i}-current`,
            totalClass: `${i}-total`,
            hiddenClass: `${i}-hidden`,
            progressbarFillClass: `${i}-progressbar-fill`,
            progressbarOppositeClass: `${i}-progressbar-opposite`,
            clickableClass: `${i}-clickable`,
            lockClass: `${i}-lock`,
            horizontalClass: `${i}-horizontal`,
            verticalClass: `${i}-vertical`,
            paginationDisabledClass: `${i}-disabled`,
        },
    }),
        (e.pagination = { el: null, bullets: [] }))
    let a,
        o = 0
    function s() {
        return (
            !e.params.pagination.el ||
            !e.pagination.el ||
            (Array.isArray(e.pagination.el) && e.pagination.el.length === 0)
        )
    }
    function c(t, n) {
        let { bulletActiveClass: r } = e.params.pagination
        t &&
            ((t = t[`${n === `prev` ? `previous` : `next`}ElementSibling`]),
            t &&
                (t.classList.add(`${r}-${n}`),
                (t = t[`${n === `prev` ? `previous` : `next`}ElementSibling`]),
                t && t.classList.add(`${r}-${n}-${n}`)))
    }
    function l(e, t, n) {
        if (((e %= n), (t %= n), t === e + 1)) return `next`
        if (t === e - 1) return `previous`
    }
    function u(t) {
        let n = t.target.closest(K(e.params.pagination.bulletClass))
        if (!n) return
        t.preventDefault()
        let r = E(n) * e.params.slidesPerGroup
        if (e.params.loop) {
            if (e.realIndex === r) return
            let t = l(e.realIndex, r, e.slides.length)
            t === `next`
                ? e.slideNext()
                : t === `previous`
                  ? e.slidePrev()
                  : e.slideToLoop(r)
        } else e.slideTo(r)
    }
    function d() {
        let t = e.rtl,
            n = e.params.pagination
        if (s()) return
        let i = e.pagination.el
        i = A(i)
        let l,
            u,
            d =
                e.virtual && e.params.virtual.enabled
                    ? e.virtual.slides.length
                    : e.slides.length,
            f = e.params.loop
                ? Math.ceil(d / e.params.slidesPerGroup)
                : e.snapGrid.length
        if (
            (e.params.loop
                ? ((u = e.previousRealIndex || 0),
                  (l =
                      e.params.slidesPerGroup > 1
                          ? Math.floor(e.realIndex / e.params.slidesPerGroup)
                          : e.realIndex))
                : e.snapIndex === void 0
                  ? ((u = e.previousIndex || 0), (l = e.activeIndex || 0))
                  : ((l = e.snapIndex), (u = e.previousSnapIndex)),
            n.type === `bullets` &&
                e.pagination.bullets &&
                e.pagination.bullets.length > 0)
        ) {
            let r = e.pagination.bullets,
                s,
                d,
                f
            if (
                (n.dynamicBullets &&
                    ((a = k(r[0], e.isHorizontal() ? `width` : `height`, !0)),
                    i.forEach((t) => {
                        t.style[e.isHorizontal() ? `width` : `height`] =
                            `${a * (n.dynamicMainBullets + 4)}px`
                    }),
                    n.dynamicMainBullets > 1 &&
                        u !== void 0 &&
                        ((o += l - (u || 0)),
                        o > n.dynamicMainBullets - 1
                            ? (o = n.dynamicMainBullets - 1)
                            : o < 0 && (o = 0)),
                    (s = Math.max(l - o, 0)),
                    (d = s + (Math.min(r.length, n.dynamicMainBullets) - 1)),
                    (f = (d + s) / 2)),
                r.forEach((e) => {
                    let t = [
                        ...[
                            ``,
                            `-next`,
                            `-next-next`,
                            `-prev`,
                            `-prev-prev`,
                            `-main`,
                        ].map((e) => `${n.bulletActiveClass}${e}`),
                    ]
                        .map((e) =>
                            typeof e == `string` && e.includes(` `)
                                ? e.split(` `)
                                : e,
                        )
                        .flat()
                    e.classList.remove(...t)
                }),
                i.length > 1)
            )
                r.forEach((t) => {
                    let r = E(t)
                    ;(r === l
                        ? t.classList.add(...n.bulletActiveClass.split(` `))
                        : e.isElement && t.setAttribute(`part`, `bullet`),
                        n.dynamicBullets &&
                            (r >= s &&
                                r <= d &&
                                t.classList.add(
                                    ...`${n.bulletActiveClass}-main`.split(` `),
                                ),
                            r === s && c(t, `prev`),
                            r === d && c(t, `next`)))
                })
            else {
                let t = r[l]
                if (
                    (t && t.classList.add(...n.bulletActiveClass.split(` `)),
                    e.isElement &&
                        r.forEach((e, t) => {
                            e.setAttribute(
                                `part`,
                                t === l ? `bullet-active` : `bullet`,
                            )
                        }),
                    n.dynamicBullets)
                ) {
                    let e = r[s],
                        t = r[d]
                    for (let e = s; e <= d; e += 1)
                        r[e] &&
                            r[e].classList.add(
                                ...`${n.bulletActiveClass}-main`.split(` `),
                            )
                    ;(c(e, `prev`), c(t, `next`))
                }
            }
            if (n.dynamicBullets) {
                let i = Math.min(r.length, n.dynamicMainBullets + 4),
                    o = (a * i - a) / 2 - f * a,
                    s = t ? `right` : `left`
                r.forEach((t) => {
                    t.style[e.isHorizontal() ? s : `top`] = `${o}px`
                })
            }
        }
        i.forEach((t, i) => {
            if (
                (n.type === `fraction` &&
                    (t.querySelectorAll(K(n.currentClass)).forEach((e) => {
                        e.textContent = n.formatFractionCurrent(l + 1)
                    }),
                    t.querySelectorAll(K(n.totalClass)).forEach((e) => {
                        e.textContent = n.formatFractionTotal(f)
                    })),
                n.type === `progressbar`)
            ) {
                let r
                r = n.progressbarOpposite
                    ? e.isHorizontal()
                        ? `vertical`
                        : `horizontal`
                    : e.isHorizontal()
                      ? `horizontal`
                      : `vertical`
                let i = (l + 1) / f,
                    a = 1,
                    o = 1
                ;(r === `horizontal` ? (a = i) : (o = i),
                    t
                        .querySelectorAll(K(n.progressbarFillClass))
                        .forEach((t) => {
                            ;((t.style.transform = `translate3d(0,0,0) scaleX(${a}) scaleY(${o})`),
                                (t.style.transitionDuration = `${e.params.speed}ms`))
                        }))
            }
            ;(n.type === `custom` && n.renderCustom
                ? (j(t, n.renderCustom(e, l + 1, f)),
                  i === 0 && r(`paginationRender`, t))
                : (i === 0 && r(`paginationRender`, t),
                  r(`paginationUpdate`, t)),
                e.params.watchOverflow &&
                    e.enabled &&
                    t.classList[e.isLocked ? `add` : `remove`](n.lockClass))
        })
    }
    function f() {
        let t = e.params.pagination
        if (s()) return
        let n =
                e.virtual && e.params.virtual.enabled
                    ? e.virtual.slides.length
                    : e.grid && e.params.grid.rows > 1
                      ? e.slides.length / Math.ceil(e.params.grid.rows)
                      : e.slides.length,
            i = e.pagination.el
        i = A(i)
        let a = ``
        if (t.type === `bullets`) {
            let r = e.params.loop
                ? Math.ceil(n / e.params.slidesPerGroup)
                : e.snapGrid.length
            e.params.freeMode && e.params.freeMode.enabled && r > n && (r = n)
            for (let n = 0; n < r; n += 1)
                t.renderBullet
                    ? (a += t.renderBullet.call(e, n, t.bulletClass))
                    : (a += `<${t.bulletElement} ${e.isElement ? `part="bullet"` : ``} class="${t.bulletClass}"></${t.bulletElement}>`)
        }
        ;(t.type === `fraction` &&
            (a = t.renderFraction
                ? t.renderFraction.call(e, t.currentClass, t.totalClass)
                : `<span class="${t.currentClass}"></span> / <span class="${t.totalClass}"></span>`),
            t.type === `progressbar` &&
                (a = t.renderProgressbar
                    ? t.renderProgressbar.call(e, t.progressbarFillClass)
                    : `<span class="${t.progressbarFillClass}"></span>`),
            (e.pagination.bullets = []),
            i.forEach((n) => {
                ;(t.type !== `custom` && j(n, a || ``),
                    t.type === `bullets` &&
                        e.pagination.bullets.push(
                            ...n.querySelectorAll(K(t.bulletClass)),
                        ))
            }),
            t.type !== `custom` && r(`paginationRender`, i[0]))
    }
    function p() {
        e.params.pagination = gt(
            e,
            e.originalParams.pagination,
            e.params.pagination,
            { el: `swiper-pagination` },
        )
        let t = e.params.pagination
        if (!t.el) return
        let n
        ;(typeof t.el == `string` &&
            e.isElement &&
            (n = e.el.querySelector(t.el)),
            !n &&
                typeof t.el == `string` &&
                (n = [...document.querySelectorAll(t.el)]),
            (n ||= t.el),
            !(!n || n.length === 0) &&
                (e.params.uniqueNavElements &&
                    typeof t.el == `string` &&
                    Array.isArray(n) &&
                    n.length > 1 &&
                    ((n = [...e.el.querySelectorAll(t.el)]),
                    n.length > 1 &&
                        (n = n.find((t) => D(t, `.swiper`)[0] === e.el))),
                Array.isArray(n) && n.length === 1 && (n = n[0]),
                Object.assign(e.pagination, { el: n }),
                (n = A(n)),
                n.forEach((n) => {
                    ;(t.type === `bullets` &&
                        t.clickable &&
                        n.classList.add(...(t.clickableClass || ``).split(` `)),
                        n.classList.add(t.modifierClass + t.type),
                        n.classList.add(
                            e.isHorizontal()
                                ? t.horizontalClass
                                : t.verticalClass,
                        ),
                        t.type === `bullets` &&
                            t.dynamicBullets &&
                            (n.classList.add(
                                `${t.modifierClass}${t.type}-dynamic`,
                            ),
                            (o = 0),
                            t.dynamicMainBullets < 1 &&
                                (t.dynamicMainBullets = 1)),
                        t.type === `progressbar` &&
                            t.progressbarOpposite &&
                            n.classList.add(t.progressbarOppositeClass),
                        t.clickable && n.addEventListener(`click`, u),
                        e.enabled || n.classList.add(t.lockClass))
                })))
    }
    function m() {
        let t = e.params.pagination
        if (s()) return
        let n = e.pagination.el
        ;(n &&
            ((n = A(n)),
            n.forEach((n) => {
                ;(n.classList.remove(t.hiddenClass),
                    n.classList.remove(t.modifierClass + t.type),
                    n.classList.remove(
                        e.isHorizontal() ? t.horizontalClass : t.verticalClass,
                    ),
                    t.clickable &&
                        (n.classList.remove(
                            ...(t.clickableClass || ``).split(` `),
                        ),
                        n.removeEventListener(`click`, u)))
            })),
            e.pagination.bullets &&
                e.pagination.bullets.forEach((e) =>
                    e.classList.remove(...t.bulletActiveClass.split(` `)),
                ))
    }
    ;(n(`changeDirection`, () => {
        if (!e.pagination || !e.pagination.el) return
        let t = e.params.pagination,
            { el: n } = e.pagination
        ;((n = A(n)),
            n.forEach((n) => {
                ;(n.classList.remove(t.horizontalClass, t.verticalClass),
                    n.classList.add(
                        e.isHorizontal() ? t.horizontalClass : t.verticalClass,
                    ))
            }))
    }),
        n(`init`, () => {
            e.params.pagination.enabled === !1 ? g() : (p(), f(), d())
        }),
        n(`activeIndexChange`, () => {
            e.snapIndex === void 0 && d()
        }),
        n(`snapIndexChange`, () => {
            d()
        }),
        n(`snapGridLengthChange`, () => {
            ;(f(), d())
        }),
        n(`destroy`, () => {
            m()
        }),
        n(`enable disable`, () => {
            let { el: t } = e.pagination
            t &&
                ((t = A(t)),
                t.forEach((t) =>
                    t.classList[e.enabled ? `remove` : `add`](
                        e.params.pagination.lockClass,
                    ),
                ))
        }),
        n(`lock unlock`, () => {
            d()
        }),
        n(`click`, (t, n) => {
            let i = n.target,
                a = A(e.pagination.el)
            if (
                e.params.pagination.el &&
                e.params.pagination.hideOnClick &&
                a &&
                a.length > 0 &&
                !i.classList.contains(e.params.pagination.bulletClass)
            ) {
                if (
                    e.navigation &&
                    ((e.navigation.nextEl && i === e.navigation.nextEl) ||
                        (e.navigation.prevEl && i === e.navigation.prevEl))
                )
                    return
                ;(a[0].classList.contains(e.params.pagination.hiddenClass) ===
                !0
                    ? r(`paginationShow`)
                    : r(`paginationHide`),
                    a.forEach((t) =>
                        t.classList.toggle(e.params.pagination.hiddenClass),
                    ))
            }
        }))
    let h = () => {
            e.el.classList.remove(e.params.pagination.paginationDisabledClass)
            let { el: t } = e.pagination
            ;(t &&
                ((t = A(t)),
                t.forEach((t) =>
                    t.classList.remove(
                        e.params.pagination.paginationDisabledClass,
                    ),
                )),
                p(),
                f(),
                d())
        },
        g = () => {
            e.el.classList.add(e.params.pagination.paginationDisabledClass)
            let { el: t } = e.pagination
            ;(t &&
                ((t = A(t)),
                t.forEach((t) =>
                    t.classList.add(
                        e.params.pagination.paginationDisabledClass,
                    ),
                )),
                m())
        }
    Object.assign(e.pagination, {
        enable: h,
        disable: g,
        render: f,
        update: d,
        init: p,
        destroy: m,
    })
}
function bt({ swiper: e, extendParams: t, on: n, emit: i, params: a }) {
    ;((e.autoplay = { running: !1, paused: !1, timeLeft: 0 }),
        t({
            autoplay: {
                enabled: !1,
                delay: 3e3,
                waitForTransition: !0,
                disableOnInteraction: !1,
                stopOnLastSlide: !1,
                reverseDirection: !1,
                pauseOnMouseEnter: !1,
            },
        }))
    let o,
        s,
        c = a && a.autoplay ? a.autoplay.delay : 3e3,
        l = a && a.autoplay ? a.autoplay.delay : 3e3,
        u,
        d = new Date().getTime(),
        f,
        p,
        m,
        h,
        g,
        _
    function v(t) {
        !e ||
            e.destroyed ||
            !e.wrapperEl ||
            (t.target === e.wrapperEl &&
                (e.wrapperEl.removeEventListener(`transitionend`, v),
                !(_ || (t.detail && t.detail.bySwiperTouchMove)) && E()))
    }
    let y = () => {
            if (e.destroyed || !e.autoplay.running) return
            e.autoplay.paused ? (f = !0) : (f &&= ((l = u), !1))
            let t = e.autoplay.paused ? u : d + l - new Date().getTime()
            ;((e.autoplay.timeLeft = t),
                i(`autoplayTimeLeft`, t, t / c),
                (s = requestAnimationFrame(() => {
                    y()
                })))
        },
        b = () => {
            let t
            if (
                ((t =
                    e.virtual && e.params.virtual.enabled
                        ? e.slides.find((e) =>
                              e.classList.contains(`swiper-slide-active`),
                          )
                        : e.slides[e.activeIndex]),
                t)
            )
                return parseInt(t.getAttribute(`data-swiper-autoplay`), 10)
        },
        x = () => {
            let t = e.params.autoplay.delay,
                n = b()
            return (!Number.isNaN(n) && n > 0 && (t = n), t)
        },
        S = (t) => {
            if (e.destroyed || !e.autoplay.running) return
            ;(cancelAnimationFrame(s), y())
            let n = t
            ;(n === void 0 && ((n = x()), (c = n), (l = n)), (u = n))
            let r = e.params.speed,
                a = () => {
                    !e ||
                        e.destroyed ||
                        (e.params.autoplay.reverseDirection
                            ? !e.isBeginning || e.params.loop || e.params.rewind
                                ? (e.slidePrev(r, !0, !0), i(`autoplay`))
                                : e.params.autoplay.stopOnLastSlide ||
                                  (e.slideTo(e.slides.length - 1, r, !0, !0),
                                  i(`autoplay`))
                            : !e.isEnd || e.params.loop || e.params.rewind
                              ? (e.slideNext(r, !0, !0), i(`autoplay`))
                              : e.params.autoplay.stopOnLastSlide ||
                                (e.slideTo(0, r, !0, !0), i(`autoplay`)),
                        e.params.cssMode &&
                            ((d = new Date().getTime()),
                            requestAnimationFrame(() => {
                                S()
                            })))
                }
            return (
                n > 0
                    ? (clearTimeout(o),
                      (o = setTimeout(() => {
                          a()
                      }, n)))
                    : requestAnimationFrame(() => {
                          a()
                      }),
                n
            )
        },
        C = () => {
            ;((d = new Date().getTime()),
                (e.autoplay.running = !0),
                S(),
                i(`autoplayStart`))
        },
        w = () => {
            ;((e.autoplay.running = !1),
                clearTimeout(o),
                cancelAnimationFrame(s),
                i(`autoplayStop`))
        },
        T = (t, n) => {
            if (e.destroyed || !e.autoplay.running) return
            ;(clearTimeout(o), t || (g = !0))
            let r = () => {
                ;(i(`autoplayPause`),
                    e.params.autoplay.waitForTransition
                        ? e.wrapperEl.addEventListener(`transitionend`, v)
                        : E())
            }
            if (((e.autoplay.paused = !0), n)) {
                r()
                return
            }
            ;((u = (u || e.params.autoplay.delay) - (new Date().getTime() - d)),
                !(e.isEnd && u < 0 && !e.params.loop) &&
                    (u < 0 && (u = 0), r()))
        },
        E = () => {
            ;(e.isEnd && u < 0 && !e.params.loop) ||
                e.destroyed ||
                !e.autoplay.running ||
                ((d = new Date().getTime()),
                g ? ((g = !1), S(u)) : S(),
                (e.autoplay.paused = !1),
                i(`autoplayResume`))
        },
        D = () => {
            if (e.destroyed || !e.autoplay.running) return
            let t = r()
            ;(t.visibilityState === `hidden` && ((g = !0), T(!0)),
                t.visibilityState === `visible` && E())
        },
        O = (t) => {
            t.pointerType === `mouse` &&
                ((g = !0),
                (_ = !0),
                !(e.animating || e.autoplay.paused) && T(!0))
        },
        k = (t) => {
            t.pointerType === `mouse` && ((_ = !1), e.autoplay.paused && E())
        },
        A = () => {
            e.params.autoplay.pauseOnMouseEnter &&
                (e.el.addEventListener(`pointerenter`, O),
                e.el.addEventListener(`pointerleave`, k))
        },
        j = () => {
            e.el &&
                typeof e.el != `string` &&
                (e.el.removeEventListener(`pointerenter`, O),
                e.el.removeEventListener(`pointerleave`, k))
        },
        M = () => {
            r().addEventListener(`visibilitychange`, D)
        },
        N = () => {
            r().removeEventListener(`visibilitychange`, D)
        }
    ;(n(`init`, () => {
        e.params.autoplay.enabled && (A(), M(), C())
    }),
        n(`destroy`, () => {
            ;(j(), N(), e.autoplay.running && w())
        }),
        n(`_freeModeStaticRelease`, () => {
            ;(m || g) && E()
        }),
        n(`_freeModeNoMomentumRelease`, () => {
            e.params.autoplay.disableOnInteraction ? w() : T(!0, !0)
        }),
        n(`beforeTransitionStart`, (t, n, r) => {
            e.destroyed ||
                !e.autoplay.running ||
                (r || !e.params.autoplay.disableOnInteraction ? T(!0, !0) : w())
        }),
        n(`sliderFirstMove`, () => {
            if (!(e.destroyed || !e.autoplay.running)) {
                if (e.params.autoplay.disableOnInteraction) {
                    w()
                    return
                }
                ;((p = !0),
                    (m = !1),
                    (g = !1),
                    (h = setTimeout(() => {
                        ;((g = !0), (m = !0), T(!0))
                    }, 200)))
            }
        }),
        n(`touchEnd`, () => {
            if (!(e.destroyed || !e.autoplay.running || !p)) {
                if (
                    (clearTimeout(h),
                    clearTimeout(o),
                    e.params.autoplay.disableOnInteraction)
                ) {
                    ;((m = !1), (p = !1))
                    return
                }
                ;(m && e.params.cssMode && E(), (m = !1), (p = !1))
            }
        }),
        n(`slideChange`, () => {
            e.destroyed ||
                !e.autoplay.running ||
                (e.autoplay.paused && ((u = x()), (c = x())))
        }),
        Object.assign(e.autoplay, { start: C, stop: w, pause: T, resume: E }))
}
function xt(e) {
    let {
        effect: t,
        swiper: n,
        on: r,
        setTranslate: i,
        setTransition: a,
        overwriteParams: o,
        perspective: s,
        recreateShadows: c,
        getEffectParams: l,
    } = e
    ;(r(`beforeInit`, () => {
        if (n.params.effect !== t) return
        ;(n.classNames.push(`${n.params.containerModifierClass}${t}`),
            s &&
                s() &&
                n.classNames.push(`${n.params.containerModifierClass}3d`))
        let e = o ? o() : {}
        ;(Object.assign(n.params, e), Object.assign(n.originalParams, e))
    }),
        r(`setTranslate _virtualUpdated`, () => {
            n.params.effect === t && i()
        }),
        r(`setTransition`, (e, r) => {
            n.params.effect === t && a(r)
        }),
        r(`transitionEnd`, () => {
            if (n.params.effect === t && c) {
                if (!l || !l().slideShadows) return
                ;(n.slides.forEach((e) => {
                    e.querySelectorAll(
                        `.swiper-slide-shadow-top, .swiper-slide-shadow-right, .swiper-slide-shadow-bottom, .swiper-slide-shadow-left`,
                    ).forEach((e) => e.remove())
                }),
                    c())
            }
        }))
    let u
    r(`virtualUpdate`, () => {
        n.params.effect === t &&
            (n.slides.length || (u = !0),
            requestAnimationFrame(() => {
                u && n.slides && n.slides.length && (i(), (u = !1))
            }))
    })
}
function St(e, t) {
    let n = _(t)
    return (
        n !== t &&
            ((n.style.backfaceVisibility = `hidden`),
            (n.style[`-webkit-backface-visibility`] = `hidden`)),
        n
    )
}
function Ct({ swiper: e, duration: t, transformElements: n, allSlides: r }) {
    let { activeIndex: i } = e,
        a = (t) =>
            t.parentElement
                ? t.parentElement
                : e.slides.find(
                      (e) => e.shadowRoot && e.shadowRoot === t.parentNode,
                  )
    if (e.params.virtualTranslate && t !== 0) {
        let t = !1,
            o
        ;((o = r
            ? n
            : n.filter((t) => {
                  let n = t.classList.contains(`swiper-slide-transform`)
                      ? a(t)
                      : t
                  return e.getSlideIndex(n) === i
              })),
            o.forEach((n) => {
                O(n, () => {
                    if (t || !e || e.destroyed) return
                    ;((t = !0), (e.animating = !1))
                    let n = new window.CustomEvent(`transitionend`, {
                        bubbles: !0,
                        cancelable: !0,
                    })
                    e.wrapperEl.dispatchEvent(n)
                })
            }))
    }
}
function wt({ swiper: e, extendParams: t, on: n }) {
    ;(t({ fadeEffect: { crossFade: !1 } }),
        xt({
            effect: `fade`,
            swiper: e,
            on: n,
            setTranslate: () => {
                let { slides: t } = e,
                    n = e.params.fadeEffect
                for (let r = 0; r < t.length; r += 1) {
                    let t = e.slides[r],
                        i = -t.swiperSlideOffset
                    e.params.virtualTranslate || (i -= e.translate)
                    let a = 0
                    e.isHorizontal() || ((a = i), (i = 0))
                    let o = e.params.fadeEffect.crossFade
                            ? Math.max(1 - Math.abs(t.progress), 0)
                            : 1 + Math.min(Math.max(t.progress, -1), 0),
                        s = St(n, t)
                    ;((s.style.opacity = o),
                        (s.style.transform = `translate3d(${i}px, ${a}px, 0px)`))
                }
            },
            setTransition: (t) => {
                let n = e.slides.map((e) => _(e))
                ;(n.forEach((e) => {
                    e.style.transitionDuration = `${t}ms`
                }),
                    Ct({
                        swiper: e,
                        duration: t,
                        transformElements: n,
                        allSlides: !0,
                    }))
            },
            overwriteParams: () => ({
                slidesPerView: 1,
                slidesPerGroup: 1,
                watchSlidesProgress: !0,
                spaceBetween: 0,
                virtualTranslate: !e.params.cssMode,
            }),
        }))
}
var q = new WeakMap(),
    Tt = 300,
    Et = 5e3
function J(e, t) {
    for (let n of t) {
        let t = e.getAttribute(n)
        if (t !== null && t !== ``) return t
    }
    return null
}
function Y(e, t) {
    let n = J(e, t)
    if (n === null) return null
    let r = n.toLowerCase()
    return [`1`, `true`, `yes`, `on`].includes(r)
        ? !0
        : [`0`, `false`, `no`, `off`].includes(r)
          ? !1
          : null
}
function X(e, t) {
    let n = J(e, t)
    if (n === null) return null
    let r = Number.parseInt(n, 10)
    return Number.isNaN(r) ? null : r
}
function Dt(e) {
    let t = J(e, [`data-carousel-per-view`, `data-perview`])
    if (t === null) return 1
    if (t === `auto`) return `auto`
    let n = Number.parseInt(t, 10)
    return Number.isNaN(n) ? 1 : n
}
function Ot(e) {
    let t = J(e, [`data-carousel-breakpoints`, `data-breakpoint`])
    if (t === null) return null
    try {
        return JSON.parse(t)
    } catch (e) {
        return (console.error(`Invalid JSON in carousel breakpoints:`, e), null)
    }
}
function kt(e) {
    let t = J(e, [`data-carousel-id`])
    if (t !== null) return t
    let n = `carousel-${Math.random().toString(36).slice(2, 10)}`
    return (e.setAttribute(`data-carousel-id`, n), n)
}
function At(e) {
    let t =
            J(e, [`data-carousel-effect`]) ??
            (Y(e, [`data-fade`]) ? `fade` : `slide`),
        n = t === `fade` || Y(e, [`data-carousel-fade`]) === !0,
        r = e.querySelectorAll(`.swiper-wrapper > .swiper-slide`).length,
        i = Y(e, [`data-carousel-loop`, `data-loop`]) ?? !1,
        a = Y(e, [`data-carousel-autoplay`, `data-auto`]) ?? !1,
        o = Y(e, [`data-carousel-drag`, `data-drag`]),
        s = Y(e, [`data-carousel-touch`, `data-carousel-swipe`]),
        c = Y(e, [`data-carousel-wheel`, `data-wheel`]),
        l = Y(e, [`data-carousel-pagination`]),
        u = Y(e, [`data-carousel-navigation`]),
        d = n || t === `fade`,
        f = o ?? s ?? !d
    return {
        align: J(e, [`data-carousel-align`, `data-align`]) ?? `center`,
        autoplayDelay:
            X(e, [`data-carousel-autoplay-delay`, `data-delay`]) ?? Et,
        autoplayDisableOnInteraction:
            Y(e, [`data-carousel-disable-on-interaction`]) ?? !0,
        autoplayEnabled: a,
        breakpoints: Ot(e),
        carouselId: kt(e),
        effect: d ? `fade` : t,
        fadeEnabled: d,
        grabCursor: f,
        initialSlide: X(e, [`data-carousel-initial-slide`]) ?? 0,
        interactionEnabled: f,
        loop: i,
        navigationEnabled: u,
        paginationEnabled: l,
        pauseOnMouseEnter: Y(e, [`data-carousel-pause-on-hover`]) ?? !0,
        perView: Dt(e),
        rewind: Y(e, [`data-carousel-rewind`]) ?? !1,
        slideCount: r,
        speed: X(e, [`data-carousel-speed`]) ?? Tt,
        watchOverflow: Y(e, [`data-carousel-watch-overflow`]) ?? !0,
        wheelEnabled: c ?? !1,
    }
}
function jt(e, t = At(e)) {
    let n = `[data-carousel-controls="${t.carouselId}"]`,
        r =
            e.querySelector(`.swiper-controls`) ??
            e.parentElement?.querySelector(n) ??
            e.parentElement?.querySelector(`.swiper-controls`) ??
            e.closest(`[data-carousel-scope]`)?.querySelector(n) ??
            e.ownerDocument.querySelector(n)
    return {
        controls: r,
        dotsNode: r?.querySelector(`.swiper-pagination`) ?? null,
        nextBtn: r?.querySelector(`.swiper-button-next`) ?? null,
        prevBtn: r?.querySelector(`.swiper-button-prev`) ?? null,
    }
}
function Mt() {
    return function (e, t) {
        return `<button type="button" class="${t}" data-carousel-bullet-index="${e}" aria-label="Go to slide ${e + 1}"></button>`
    }
}
function Nt(e, t) {
    let n = t.realIndex ?? t.activeIndex
    e.querySelectorAll(
        `.swiper-wrapper > .swiper-slide:not(.swiper-slide-duplicate)`,
    ).forEach((e, t) => {
        e.classList.toggle(`swiper-slide-selected`, t === n)
    })
}
function Z(e, t, n, r) {
    if (!t.dotsNode) return
    let i = t.dotsNode.querySelectorAll(`.swiper-pagination-bullet`)
    i.forEach((t, a) => {
        t.dataset.carouselPaginationBound !== n.carouselId &&
            ((t.dataset.carouselPaginationBound = n.carouselId),
            t.addEventListener(
                `click`,
                (r) => {
                    if ((r.preventDefault(), e.destroyed)) return
                    let o = Number.parseInt(
                            t.dataset.carouselBulletIndex ?? ``,
                            10,
                        ),
                        s = Number.isNaN(o) ? a : o
                    if (n.loop && typeof e.slideToLoop == `function`) {
                        let t = e.realIndex ?? e.activeIndex,
                            n = i.length - 1
                        if (t === 0 && s === n) {
                            e.slidePrev()
                            return
                        }
                        e.slideToLoop(s)
                        return
                    }
                    e.slideTo(s)
                },
                { signal: r },
            ))
    })
}
function Q(e, t, n) {
    if ((e.classList.toggle(`swiper-disabled`, !n), t.autoplay)) {
        if (n) {
            t.autoplay.start()
            return
        }
        t.autoplay.stop()
    }
}
function Pt(e, t) {
    let n = new IntersectionObserver((n) => {
        n.forEach((n) => {
            Q(e, t, n.isIntersecting)
        })
    })
    return (n.observe(e), n)
}
function Ft(e, t, n) {
    e.querySelectorAll(`img`).forEach((r) => {
        r.addEventListener(
            `load`,
            () => {
                ;(t.update(), Q(e, t, !0))
            },
            { signal: n },
        )
    })
}
function It(e, t, n, r) {
    let i = [],
        a = {
            allowTouchMove: t.interactionEnabled,
            centeredSlides: t.align === `center`,
            grabCursor: t.grabCursor,
            initialSlide: t.initialSlide,
            loop: t.loop,
            loopAdditionalSlides: t.loop ? t.slideCount : void 0,
            observeParents: !0,
            observer: !0,
            rewind: t.loop ? !1 : t.rewind,
            slidesPerView: t.fadeEnabled ? 1 : t.perView,
            spaceBetween: 0,
            speed: t.speed,
            watchOverflow: t.watchOverflow,
            watchSlidesProgress: !0,
        }
    return (
        t.breakpoints && (a.breakpoints = t.breakpoints),
        (t.navigationEnabled ?? !!(n.prevBtn || n.nextBtn)) &&
            (n.prevBtn || n.nextBtn) &&
            (i.push(vt),
            (a.navigation = {
                disabledClass: `swiper-button-disabled`,
                nextEl: n.nextBtn,
                prevEl: n.prevBtn,
            })),
        (t.paginationEnabled ?? !!n.dotsNode) &&
            n.dotsNode &&
            (i.push(yt),
            (a.pagination = {
                bulletActiveClass: `swiper-pagination-bullet-active`,
                bulletClass: `swiper-pagination-bullet`,
                clickable: !1,
                el: n.dotsNode,
                renderBullet: Mt(),
            })),
        t.autoplayEnabled &&
            (i.push(bt),
            (a.autoplay = {
                delay: t.autoplayDelay,
                disableOnInteraction: t.autoplayDisableOnInteraction,
                pauseOnMouseEnter: t.pauseOnMouseEnter,
                stopOnLastSlide: !1,
            })),
        t.fadeEnabled &&
            (i.push(wt),
            (a.centeredSlides = !0),
            (a.effect = `fade`),
            (a.fadeEffect = { crossFade: !0 })),
        t.wheelEnabled &&
            (i.push(ht),
            (a.mousewheel = { forceToAxis: !0, releaseOnEdges: !0 })),
        (a.modules = i),
        (a.on = {
            init() {
                ;(e.classList.add(`swiper-ready`),
                    Nt(e, this),
                    Z(this, n, t, r))
            },
            paginationRender() {
                Z(this, n, t, r)
            },
            paginationUpdate() {
                Z(this, n, t, r)
            },
            resize() {
                this.update()
            },
            slideChange() {
                ;(Nt(e, this), Z(this, n, t, r))
            },
        }),
        a
    )
}
function Lt(e) {
    let t = q.get(e)
    t &&
        (t.abortController.abort(),
        t.observer?.disconnect(),
        t.swiper && !t.swiper.destroyed && t.swiper.destroy(!0, !0),
        delete e.swiper,
        delete e.dataset.initialized,
        e.classList.remove(`swiper-disabled`, `swiper-ready`),
        q.delete(e))
}
function Rt(e) {
    if (!(e instanceof HTMLElement) || !e.querySelector(`.swiper-wrapper`))
        return null
    Lt(e)
    let t = At(e),
        n = jt(e, t),
        r = new AbortController(),
        i = new G(e, It(e, t, n, r.signal))
    ;(Ft(e, i, r.signal),
        e.addEventListener(`enable-carousel`, () => Q(e, i, !0), {
            signal: r.signal,
        }),
        e.addEventListener(`disable-carousel`, () => Q(e, i, !1), {
            signal: r.signal,
        }))
    let a = Pt(e, i)
    return (
        q.set(e, { abortController: r, observer: a, options: t, swiper: i }),
        (e.dataset.initialized = `true`),
        (e.swiper = i),
        i
    )
}
function $(e = document) {
    e.querySelectorAll(`.swiper`).forEach((e) => {
        e.dataset.initialized || Rt(e)
    })
}
typeof document < `u` &&
    ($(),
    document.addEventListener(`livewire:navigated`, () => {
        $()
    }))
