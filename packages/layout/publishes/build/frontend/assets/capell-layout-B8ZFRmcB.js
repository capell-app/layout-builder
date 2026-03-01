function ce(i) {
    return (
        i !== null &&
        typeof i == 'object' &&
        'constructor' in i &&
        i.constructor === Object
    )
}
function de(i, e) {
    ;(i === void 0 && (i = {}), e === void 0 && (e = {}))
    const t = ['__proto__', 'constructor', 'prototype']
    Object.keys(e)
        .filter((s) => t.indexOf(s) < 0)
        .forEach((s) => {
            typeof i[s] > 'u'
                ? (i[s] = e[s])
                : ce(e[s]) &&
                  ce(i[s]) &&
                  Object.keys(e[s]).length > 0 &&
                  de(i[s], e[s])
        })
}
const ve = {
    body: {},
    addEventListener() {},
    removeEventListener() {},
    activeElement: { blur() {}, nodeName: '' },
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
        hash: '',
        host: '',
        hostname: '',
        href: '',
        origin: '',
        pathname: '',
        protocol: '',
        search: '',
    },
}
function R() {
    const i = typeof document < 'u' ? document : {}
    return (de(i, ve), i)
}
const Ie = {
    document: ve,
    navigator: { userAgent: '' },
    location: {
        hash: '',
        host: '',
        hostname: '',
        href: '',
        origin: '',
        pathname: '',
        protocol: '',
        search: '',
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
                return ''
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
    requestAnimationFrame(i) {
        return typeof setTimeout > 'u' ? (i(), null) : setTimeout(i, 0)
    },
    cancelAnimationFrame(i) {
        typeof setTimeout > 'u' || clearTimeout(i)
    },
}
function $() {
    const i = typeof window < 'u' ? window : {}
    return (de(i, Ie), i)
}
function Oe(i) {
    return (
        i === void 0 && (i = ''),
        i
            .trim()
            .split(' ')
            .filter((e) => !!e.trim())
    )
}
function Ae(i) {
    const e = i
    Object.keys(e).forEach((t) => {
        try {
            e[t] = null
        } catch {}
        try {
            delete e[t]
        } catch {}
    })
}
function we(i, e) {
    return (e === void 0 && (e = 0), setTimeout(i, e))
}
function K() {
    return Date.now()
}
function ze(i) {
    const e = $()
    let t
    return (
        e.getComputedStyle && (t = e.getComputedStyle(i, null)),
        !t && i.currentStyle && (t = i.currentStyle),
        t || (t = i.style),
        t
    )
}
function ke(i, e) {
    e === void 0 && (e = 'x')
    const t = $()
    let s, n, r
    const l = ze(i)
    return (
        t.WebKitCSSMatrix
            ? ((n = l.transform || l.webkitTransform),
              n.split(',').length > 6 &&
                  (n = n
                      .split(', ')
                      .map((a) => a.replace(',', '.'))
                      .join(', ')),
              (r = new t.WebKitCSSMatrix(n === 'none' ? '' : n)))
            : ((r =
                  l.MozTransform ||
                  l.OTransform ||
                  l.MsTransform ||
                  l.msTransform ||
                  l.transform ||
                  l
                      .getPropertyValue('transform')
                      .replace('translate(', 'matrix(1, 0, 0, 1,')),
              (s = r.toString().split(','))),
        e === 'x' &&
            (t.WebKitCSSMatrix
                ? (n = r.m41)
                : s.length === 16
                  ? (n = parseFloat(s[12]))
                  : (n = parseFloat(s[4]))),
        e === 'y' &&
            (t.WebKitCSSMatrix
                ? (n = r.m42)
                : s.length === 16
                  ? (n = parseFloat(s[13]))
                  : (n = parseFloat(s[5]))),
        n || 0
    )
}
function Y(i) {
    return (
        typeof i == 'object' &&
        i !== null &&
        i.constructor &&
        Object.prototype.toString.call(i).slice(8, -1) === 'Object'
    )
}
function De(i) {
    return typeof window < 'u' && typeof window.HTMLElement < 'u'
        ? i instanceof HTMLElement
        : i && (i.nodeType === 1 || i.nodeType === 11)
}
function F() {
    const i = Object(arguments.length <= 0 ? void 0 : arguments[0]),
        e = ['__proto__', 'constructor', 'prototype']
    for (let t = 1; t < arguments.length; t += 1) {
        const s = t < 0 || arguments.length <= t ? void 0 : arguments[t]
        if (s != null && !De(s)) {
            const n = Object.keys(Object(s)).filter((r) => e.indexOf(r) < 0)
            for (let r = 0, l = n.length; r < l; r += 1) {
                const a = n[r],
                    d = Object.getOwnPropertyDescriptor(s, a)
                d !== void 0 &&
                    d.enumerable &&
                    (Y(i[a]) && Y(s[a])
                        ? s[a].__swiper__
                            ? (i[a] = s[a])
                            : F(i[a], s[a])
                        : !Y(i[a]) && Y(s[a])
                          ? ((i[a] = {}),
                            s[a].__swiper__ ? (i[a] = s[a]) : F(i[a], s[a]))
                          : (i[a] = s[a]))
            }
        }
    }
    return i
}
function X(i, e, t) {
    i.style.setProperty(e, t)
}
function ye(i) {
    let { swiper: e, targetPosition: t, side: s } = i
    const n = $(),
        r = -e.translate
    let l = null,
        a
    const d = e.params.speed
    ;((e.wrapperEl.style.scrollSnapType = 'none'),
        n.cancelAnimationFrame(e.cssModeFrameID))
    const o = t > r ? 'next' : 'prev',
        u = (h, g) => (o === 'next' && h >= g) || (o === 'prev' && h <= g),
        p = () => {
            ;((a = new Date().getTime()), l === null && (l = a))
            const h = Math.max(Math.min((a - l) / d, 1), 0),
                g = 0.5 - Math.cos(h * Math.PI) / 2
            let m = r + g * (t - r)
            if (
                (u(m, t) && (m = t), e.wrapperEl.scrollTo({ [s]: m }), u(m, t))
            ) {
                ;((e.wrapperEl.style.overflow = 'hidden'),
                    (e.wrapperEl.style.scrollSnapType = ''),
                    setTimeout(() => {
                        ;((e.wrapperEl.style.overflow = ''),
                            e.wrapperEl.scrollTo({ [s]: m }))
                    }),
                    n.cancelAnimationFrame(e.cssModeFrameID))
                return
            }
            e.cssModeFrameID = n.requestAnimationFrame(p)
        }
    p()
}
function Se(i) {
    return (
        i.querySelector('.swiper-slide-transform') ||
        (i.shadowRoot &&
            i.shadowRoot.querySelector('.swiper-slide-transform')) ||
        i
    )
}
function H(i, e) {
    e === void 0 && (e = '')
    const t = $(),
        s = [...i.children]
    return (
        t.HTMLSlotElement &&
            i instanceof HTMLSlotElement &&
            s.push(...i.assignedElements()),
        e ? s.filter((n) => n.matches(e)) : s
    )
}
function Ge(i, e) {
    const t = [e]
    for (; t.length > 0; ) {
        const s = t.shift()
        if (i === s) return !0
        t.push(
            ...s.children,
            ...(s.shadowRoot ? s.shadowRoot.children : []),
            ...(s.assignedElements ? s.assignedElements() : []),
        )
    }
}
function Be(i, e) {
    const t = $()
    let s = e.contains(i)
    return (
        !s &&
            t.HTMLSlotElement &&
            e instanceof HTMLSlotElement &&
            ((s = [...e.assignedElements()].includes(i)), s || (s = Ge(i, e))),
        s
    )
}
function J(i) {
    try {
        console.warn(i)
        return
    } catch {}
}
function Q(i, e) {
    e === void 0 && (e = [])
    const t = document.createElement(i)
    return (t.classList.add(...(Array.isArray(e) ? e : Oe(e))), t)
}
function Ve(i, e) {
    const t = []
    for (; i.previousElementSibling; ) {
        const s = i.previousElementSibling
        ;(e ? s.matches(e) && t.push(s) : t.push(s), (i = s))
    }
    return t
}
function $e(i, e) {
    const t = []
    for (; i.nextElementSibling; ) {
        const s = i.nextElementSibling
        ;(e ? s.matches(e) && t.push(s) : t.push(s), (i = s))
    }
    return t
}
function N(i, e) {
    return $().getComputedStyle(i, null).getPropertyValue(e)
}
function Z(i) {
    let e = i,
        t
    if (e) {
        for (t = 0; (e = e.previousSibling) !== null; )
            e.nodeType === 1 && (t += 1)
        return t
    }
}
function Te(i, e) {
    const t = []
    let s = i.parentElement
    for (; s; )
        (e ? s.matches(e) && t.push(s) : t.push(s), (s = s.parentElement))
    return t
}
function Fe(i, e) {
    function t(s) {
        s.target === i &&
            (e.call(i, s), i.removeEventListener('transitionend', t))
    }
    e && i.addEventListener('transitionend', t)
}
function le(i, e, t) {
    const s = $()
    return (
        i[e === 'width' ? 'offsetWidth' : 'offsetHeight'] +
        parseFloat(
            s
                .getComputedStyle(i, null)
                .getPropertyValue(
                    e === 'width' ? 'margin-right' : 'margin-top',
                ),
        ) +
        parseFloat(
            s
                .getComputedStyle(i, null)
                .getPropertyValue(
                    e === 'width' ? 'margin-left' : 'margin-bottom',
                ),
        )
    )
}
function B(i) {
    return (Array.isArray(i) ? i : [i]).filter((e) => !!e)
}
function fe(i, e) {
    ;(e === void 0 && (e = ''),
        typeof trustedTypes < 'u'
            ? (i.innerHTML = trustedTypes
                  .createPolicy('html', { createHTML: (t) => t })
                  .createHTML(e))
            : (i.innerHTML = e))
}
let ee
function _e() {
    const i = $(),
        e = R()
    return {
        smoothScroll:
            e.documentElement &&
            e.documentElement.style &&
            'scrollBehavior' in e.documentElement.style,
        touch: !!(
            'ontouchstart' in i ||
            (i.DocumentTouch && e instanceof i.DocumentTouch)
        ),
    }
}
function be() {
    return (ee || (ee = _e()), ee)
}
let te
function He(i) {
    let { userAgent: e } = i === void 0 ? {} : i
    const t = be(),
        s = $(),
        n = s.navigator.platform,
        r = e || s.navigator.userAgent,
        l = { ios: !1, android: !1 },
        a = s.screen.width,
        d = s.screen.height,
        o = r.match(/(Android);?[\s\/]+([\d.]+)?/)
    let u = r.match(/(iPad).*OS\s([\d_]+)/)
    const p = r.match(/(iPod)(.*OS\s([\d_]+))?/),
        h = !u && r.match(/(iPhone\sOS|iOS)\s([\d_]+)/),
        g = n === 'Win32'
    let m = n === 'MacIntel'
    const v = [
        '1024x1366',
        '1366x1024',
        '834x1194',
        '1194x834',
        '834x1112',
        '1112x834',
        '768x1024',
        '1024x768',
        '820x1180',
        '1180x820',
        '810x1080',
        '1080x810',
    ]
    return (
        !u &&
            m &&
            t.touch &&
            v.indexOf(`${a}x${d}`) >= 0 &&
            ((u = r.match(/(Version)\/([\d.]+)/)),
            u || (u = [0, 1, '13_0_0']),
            (m = !1)),
        o && !g && ((l.os = 'android'), (l.android = !0)),
        (u || h || p) && ((l.os = 'ios'), (l.ios = !0)),
        l
    )
}
function xe(i) {
    return (i === void 0 && (i = {}), te || (te = He(i)), te)
}
let ie
function Re() {
    const i = $(),
        e = xe()
    let t = !1
    function s() {
        const a = i.navigator.userAgent.toLowerCase()
        return (
            a.indexOf('safari') >= 0 &&
            a.indexOf('chrome') < 0 &&
            a.indexOf('android') < 0
        )
    }
    if (s()) {
        const a = String(i.navigator.userAgent)
        if (a.includes('Version/')) {
            const [d, o] = a
                .split('Version/')[1]
                .split(' ')[0]
                .split('.')
                .map((u) => Number(u))
            t = d < 16 || (d === 16 && o < 2)
        }
    }
    const n = /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(
            i.navigator.userAgent,
        ),
        r = s(),
        l = r || (n && e.ios)
    return {
        isSafari: t || r,
        needPerspectiveFix: t,
        need3dFix: l,
        isWebView: n,
    }
}
function Ee() {
    return (ie || (ie = Re()), ie)
}
function Ne(i) {
    let { swiper: e, on: t, emit: s } = i
    const n = $()
    let r = null,
        l = null
    const a = () => {
            !e ||
                e.destroyed ||
                !e.initialized ||
                (s('beforeResize'), s('resize'))
        },
        d = () => {
            !e ||
                e.destroyed ||
                !e.initialized ||
                ((r = new ResizeObserver((p) => {
                    l = n.requestAnimationFrame(() => {
                        const { width: h, height: g } = e
                        let m = h,
                            v = g
                        ;(p.forEach((b) => {
                            let {
                                contentBoxSize: y,
                                contentRect: c,
                                target: f,
                            } = b
                            ;(f && f !== e.el) ||
                                ((m = c ? c.width : (y[0] || y).inlineSize),
                                (v = c ? c.height : (y[0] || y).blockSize))
                        }),
                            (m !== h || v !== g) && a())
                    })
                })),
                r.observe(e.el))
        },
        o = () => {
            ;(l && n.cancelAnimationFrame(l),
                r && r.unobserve && e.el && (r.unobserve(e.el), (r = null)))
        },
        u = () => {
            !e || e.destroyed || !e.initialized || s('orientationchange')
        }
    ;(t('init', () => {
        if (e.params.resizeObserver && typeof n.ResizeObserver < 'u') {
            d()
            return
        }
        ;(n.addEventListener('resize', a),
            n.addEventListener('orientationchange', u))
    }),
        t('destroy', () => {
            ;(o(),
                n.removeEventListener('resize', a),
                n.removeEventListener('orientationchange', u))
        }))
}
function qe(i) {
    let { swiper: e, extendParams: t, on: s, emit: n } = i
    const r = [],
        l = $(),
        a = function (u, p) {
            p === void 0 && (p = {})
            const h = l.MutationObserver || l.WebkitMutationObserver,
                g = new h((m) => {
                    if (e.__preventObserver__) return
                    if (m.length === 1) {
                        n('observerUpdate', m[0])
                        return
                    }
                    const v = function () {
                        n('observerUpdate', m[0])
                    }
                    l.requestAnimationFrame
                        ? l.requestAnimationFrame(v)
                        : l.setTimeout(v, 0)
                })
            ;(g.observe(u, {
                attributes: typeof p.attributes > 'u' ? !0 : p.attributes,
                childList:
                    e.isElement ||
                    (typeof p.childList > 'u' ? !0 : p).childList,
                characterData:
                    typeof p.characterData > 'u' ? !0 : p.characterData,
            }),
                r.push(g))
        },
        d = () => {
            if (e.params.observer) {
                if (e.params.observeParents) {
                    const u = Te(e.hostEl)
                    for (let p = 0; p < u.length; p += 1) a(u[p])
                }
                ;(a(e.hostEl, { childList: e.params.observeSlideChildren }),
                    a(e.wrapperEl, { attributes: !1 }))
            }
        },
        o = () => {
            ;(r.forEach((u) => {
                u.disconnect()
            }),
                r.splice(0, r.length))
        }
    ;(t({ observer: !1, observeParents: !1, observeSlideChildren: !1 }),
        s('init', d),
        s('destroy', o))
}
var We = {
    on(i, e, t) {
        const s = this
        if (!s.eventsListeners || s.destroyed || typeof e != 'function')
            return s
        const n = t ? 'unshift' : 'push'
        return (
            i.split(' ').forEach((r) => {
                ;(s.eventsListeners[r] || (s.eventsListeners[r] = []),
                    s.eventsListeners[r][n](e))
            }),
            s
        )
    },
    once(i, e, t) {
        const s = this
        if (!s.eventsListeners || s.destroyed || typeof e != 'function')
            return s
        function n() {
            ;(s.off(i, n), n.__emitterProxy && delete n.__emitterProxy)
            for (var r = arguments.length, l = new Array(r), a = 0; a < r; a++)
                l[a] = arguments[a]
            e.apply(s, l)
        }
        return ((n.__emitterProxy = e), s.on(i, n, t))
    },
    onAny(i, e) {
        const t = this
        if (!t.eventsListeners || t.destroyed || typeof i != 'function')
            return t
        const s = e ? 'unshift' : 'push'
        return (
            t.eventsAnyListeners.indexOf(i) < 0 && t.eventsAnyListeners[s](i),
            t
        )
    },
    offAny(i) {
        const e = this
        if (!e.eventsListeners || e.destroyed || !e.eventsAnyListeners) return e
        const t = e.eventsAnyListeners.indexOf(i)
        return (t >= 0 && e.eventsAnyListeners.splice(t, 1), e)
    },
    off(i, e) {
        const t = this
        return (
            !t.eventsListeners ||
                t.destroyed ||
                !t.eventsListeners ||
                i.split(' ').forEach((s) => {
                    typeof e > 'u'
                        ? (t.eventsListeners[s] = [])
                        : t.eventsListeners[s] &&
                          t.eventsListeners[s].forEach((n, r) => {
                              ;(n === e ||
                                  (n.__emitterProxy &&
                                      n.__emitterProxy === e)) &&
                                  t.eventsListeners[s].splice(r, 1)
                          })
                }),
            t
        )
    },
    emit() {
        const i = this
        if (!i.eventsListeners || i.destroyed || !i.eventsListeners) return i
        let e, t, s
        for (var n = arguments.length, r = new Array(n), l = 0; l < n; l++)
            r[l] = arguments[l]
        return (
            typeof r[0] == 'string' || Array.isArray(r[0])
                ? ((e = r[0]), (t = r.slice(1, r.length)), (s = i))
                : ((e = r[0].events), (t = r[0].data), (s = r[0].context || i)),
            t.unshift(s),
            (Array.isArray(e) ? e : e.split(' ')).forEach((d) => {
                ;(i.eventsAnyListeners &&
                    i.eventsAnyListeners.length &&
                    i.eventsAnyListeners.forEach((o) => {
                        o.apply(s, [d, ...t])
                    }),
                    i.eventsListeners &&
                        i.eventsListeners[d] &&
                        i.eventsListeners[d].forEach((o) => {
                            o.apply(s, t)
                        }))
            }),
            i
        )
    },
}
function je() {
    const i = this
    let e, t
    const s = i.el
    ;(typeof i.params.width < 'u' && i.params.width !== null
        ? (e = i.params.width)
        : (e = s.clientWidth),
        typeof i.params.height < 'u' && i.params.height !== null
            ? (t = i.params.height)
            : (t = s.clientHeight),
        !((e === 0 && i.isHorizontal()) || (t === 0 && i.isVertical())) &&
            ((e =
                e -
                parseInt(N(s, 'padding-left') || 0, 10) -
                parseInt(N(s, 'padding-right') || 0, 10)),
            (t =
                t -
                parseInt(N(s, 'padding-top') || 0, 10) -
                parseInt(N(s, 'padding-bottom') || 0, 10)),
            Number.isNaN(e) && (e = 0),
            Number.isNaN(t) && (t = 0),
            Object.assign(i, {
                width: e,
                height: t,
                size: i.isHorizontal() ? e : t,
            })))
}
function Ye() {
    const i = this
    function e(T, x) {
        return parseFloat(T.getPropertyValue(i.getDirectionLabel(x)) || 0)
    }
    const t = i.params,
        {
            wrapperEl: s,
            slidesEl: n,
            size: r,
            rtlTranslate: l,
            wrongRTL: a,
        } = i,
        d = i.virtual && t.virtual.enabled,
        o = d ? i.virtual.slides.length : i.slides.length,
        u = H(n, `.${i.params.slideClass}, swiper-slide`),
        p = d ? i.virtual.slides.length : u.length
    let h = []
    const g = [],
        m = []
    let v = t.slidesOffsetBefore
    typeof v == 'function' && (v = t.slidesOffsetBefore.call(i))
    let b = t.slidesOffsetAfter
    typeof b == 'function' && (b = t.slidesOffsetAfter.call(i))
    const y = i.snapGrid.length,
        c = i.slidesGrid.length
    let f = t.spaceBetween,
        w = -v,
        S = 0,
        E = 0
    if (typeof r > 'u') return
    ;(typeof f == 'string' && f.indexOf('%') >= 0
        ? (f = (parseFloat(f.replace('%', '')) / 100) * r)
        : typeof f == 'string' && (f = parseFloat(f)),
        (i.virtualSize = -f),
        u.forEach((T) => {
            ;(l ? (T.style.marginLeft = '') : (T.style.marginRight = ''),
                (T.style.marginBottom = ''),
                (T.style.marginTop = ''))
        }),
        t.centeredSlides &&
            t.cssMode &&
            (X(s, '--swiper-centered-offset-before', ''),
            X(s, '--swiper-centered-offset-after', '')))
    const M = t.grid && t.grid.rows > 1 && i.grid
    M ? i.grid.initSlides(u) : i.grid && i.grid.unsetSlides()
    let C
    const P =
        t.slidesPerView === 'auto' &&
        t.breakpoints &&
        Object.keys(t.breakpoints).filter(
            (T) => typeof t.breakpoints[T].slidesPerView < 'u',
        ).length > 0
    for (let T = 0; T < p; T += 1) {
        C = 0
        let x
        if (
            (u[T] && (x = u[T]),
            M && i.grid.updateSlide(T, x, u),
            !(u[T] && N(x, 'display') === 'none'))
        ) {
            if (t.slidesPerView === 'auto') {
                P && (u[T].style[i.getDirectionLabel('width')] = '')
                const O = getComputedStyle(x),
                    A = x.style.transform,
                    z = x.style.webkitTransform
                if (
                    (A && (x.style.transform = 'none'),
                    z && (x.style.webkitTransform = 'none'),
                    t.roundLengths)
                )
                    C = i.isHorizontal() ? le(x, 'width') : le(x, 'height')
                else {
                    const k = e(O, 'width'),
                        L = e(O, 'padding-left'),
                        G = e(O, 'padding-right'),
                        I = e(O, 'margin-left'),
                        D = e(O, 'margin-right'),
                        V = O.getPropertyValue('box-sizing')
                    if (V && V === 'border-box') C = k + I + D
                    else {
                        const { clientWidth: q, offsetWidth: j } = x
                        C = k + L + G + I + D + (j - q)
                    }
                }
                ;(A && (x.style.transform = A),
                    z && (x.style.webkitTransform = z),
                    t.roundLengths && (C = Math.floor(C)))
            } else
                ((C = (r - (t.slidesPerView - 1) * f) / t.slidesPerView),
                    t.roundLengths && (C = Math.floor(C)),
                    u[T] &&
                        (u[T].style[i.getDirectionLabel('width')] = `${C}px`))
            ;(u[T] && (u[T].swiperSlideSize = C),
                m.push(C),
                t.centeredSlides
                    ? ((w = w + C / 2 + S / 2 + f),
                      S === 0 && T !== 0 && (w = w - r / 2 - f),
                      T === 0 && (w = w - r / 2 - f),
                      Math.abs(w) < 1 / 1e3 && (w = 0),
                      t.roundLengths && (w = Math.floor(w)),
                      E % t.slidesPerGroup === 0 && h.push(w),
                      g.push(w))
                    : (t.roundLengths && (w = Math.floor(w)),
                      (E - Math.min(i.params.slidesPerGroupSkip, E)) %
                          i.params.slidesPerGroup ===
                          0 && h.push(w),
                      g.push(w),
                      (w = w + C + f)),
                (i.virtualSize += C + f),
                (S = C),
                (E += 1))
        }
    }
    if (
        ((i.virtualSize = Math.max(i.virtualSize, r) + b),
        l &&
            a &&
            (t.effect === 'slide' || t.effect === 'coverflow') &&
            (s.style.width = `${i.virtualSize + f}px`),
        t.setWrapperSize &&
            (s.style[i.getDirectionLabel('width')] = `${i.virtualSize + f}px`),
        M && i.grid.updateWrapperSize(C, h),
        !t.centeredSlides)
    ) {
        const T = []
        for (let x = 0; x < h.length; x += 1) {
            let O = h[x]
            ;(t.roundLengths && (O = Math.floor(O)),
                h[x] <= i.virtualSize - r && T.push(O))
        }
        ;((h = T),
            Math.floor(i.virtualSize - r) - Math.floor(h[h.length - 1]) > 1 &&
                h.push(i.virtualSize - r))
    }
    if (d && t.loop) {
        const T = m[0] + f
        if (t.slidesPerGroup > 1) {
            const x = Math.ceil(
                    (i.virtual.slidesBefore + i.virtual.slidesAfter) /
                        t.slidesPerGroup,
                ),
                O = T * t.slidesPerGroup
            for (let A = 0; A < x; A += 1) h.push(h[h.length - 1] + O)
        }
        for (
            let x = 0;
            x < i.virtual.slidesBefore + i.virtual.slidesAfter;
            x += 1
        )
            (t.slidesPerGroup === 1 && h.push(h[h.length - 1] + T),
                g.push(g[g.length - 1] + T),
                (i.virtualSize += T))
    }
    if ((h.length === 0 && (h = [0]), f !== 0)) {
        const T =
            i.isHorizontal() && l
                ? 'marginLeft'
                : i.getDirectionLabel('marginRight')
        u.filter((x, O) =>
            !t.cssMode || t.loop ? !0 : O !== u.length - 1,
        ).forEach((x) => {
            x.style[T] = `${f}px`
        })
    }
    if (t.centeredSlides && t.centeredSlidesBounds) {
        let T = 0
        ;(m.forEach((O) => {
            T += O + (f || 0)
        }),
            (T -= f))
        const x = T > r ? T - r : 0
        h = h.map((O) => (O <= 0 ? -v : O > x ? x + b : O))
    }
    if (t.centerInsufficientSlides) {
        let T = 0
        ;(m.forEach((O) => {
            T += O + (f || 0)
        }),
            (T -= f))
        const x = (t.slidesOffsetBefore || 0) + (t.slidesOffsetAfter || 0)
        if (T + x < r) {
            const O = (r - T - x) / 2
            ;(h.forEach((A, z) => {
                h[z] = A - O
            }),
                g.forEach((A, z) => {
                    g[z] = A + O
                }))
        }
    }
    if (
        (Object.assign(i, {
            slides: u,
            snapGrid: h,
            slidesGrid: g,
            slidesSizesGrid: m,
        }),
        t.centeredSlides && t.cssMode && !t.centeredSlidesBounds)
    ) {
        ;(X(s, '--swiper-centered-offset-before', `${-h[0]}px`),
            X(
                s,
                '--swiper-centered-offset-after',
                `${i.size / 2 - m[m.length - 1] / 2}px`,
            ))
        const T = -i.snapGrid[0],
            x = -i.slidesGrid[0]
        ;((i.snapGrid = i.snapGrid.map((O) => O + T)),
            (i.slidesGrid = i.slidesGrid.map((O) => O + x)))
    }
    if (
        (p !== o && i.emit('slidesLengthChange'),
        h.length !== y &&
            (i.params.watchOverflow && i.checkOverflow(),
            i.emit('snapGridLengthChange')),
        g.length !== c && i.emit('slidesGridLengthChange'),
        t.watchSlidesProgress && i.updateSlidesOffset(),
        i.emit('slidesUpdated'),
        !d && !t.cssMode && (t.effect === 'slide' || t.effect === 'fade'))
    ) {
        const T = `${t.containerModifierClass}backface-hidden`,
            x = i.el.classList.contains(T)
        p <= t.maxBackfaceHiddenSlides
            ? x || i.el.classList.add(T)
            : x && i.el.classList.remove(T)
    }
}
function Xe(i) {
    const e = this,
        t = [],
        s = e.virtual && e.params.virtual.enabled
    let n = 0,
        r
    typeof i == 'number'
        ? e.setTransition(i)
        : i === !0 && e.setTransition(e.params.speed)
    const l = (a) => (s ? e.slides[e.getSlideIndexByData(a)] : e.slides[a])
    if (e.params.slidesPerView !== 'auto' && e.params.slidesPerView > 1)
        if (e.params.centeredSlides)
            (e.visibleSlides || []).forEach((a) => {
                t.push(a)
            })
        else
            for (r = 0; r < Math.ceil(e.params.slidesPerView); r += 1) {
                const a = e.activeIndex + r
                if (a > e.slides.length && !s) break
                t.push(l(a))
            }
    else t.push(l(e.activeIndex))
    for (r = 0; r < t.length; r += 1)
        if (typeof t[r] < 'u') {
            const a = t[r].offsetHeight
            n = a > n ? a : n
        }
    ;(n || n === 0) && (e.wrapperEl.style.height = `${n}px`)
}
function Ue() {
    const i = this,
        e = i.slides,
        t = i.isElement
            ? i.isHorizontal()
                ? i.wrapperEl.offsetLeft
                : i.wrapperEl.offsetTop
            : 0
    for (let s = 0; s < e.length; s += 1)
        e[s].swiperSlideOffset =
            (i.isHorizontal() ? e[s].offsetLeft : e[s].offsetTop) -
            t -
            i.cssOverflowAdjustment()
}
const ue = (i, e, t) => {
    e && !i.classList.contains(t)
        ? i.classList.add(t)
        : !e && i.classList.contains(t) && i.classList.remove(t)
}
function Ke(i) {
    i === void 0 && (i = (this && this.translate) || 0)
    const e = this,
        t = e.params,
        { slides: s, rtlTranslate: n, snapGrid: r } = e
    if (s.length === 0) return
    typeof s[0].swiperSlideOffset > 'u' && e.updateSlidesOffset()
    let l = -i
    ;(n && (l = i), (e.visibleSlidesIndexes = []), (e.visibleSlides = []))
    let a = t.spaceBetween
    typeof a == 'string' && a.indexOf('%') >= 0
        ? (a = (parseFloat(a.replace('%', '')) / 100) * e.size)
        : typeof a == 'string' && (a = parseFloat(a))
    for (let d = 0; d < s.length; d += 1) {
        const o = s[d]
        let u = o.swiperSlideOffset
        t.cssMode && t.centeredSlides && (u -= s[0].swiperSlideOffset)
        const p =
                (l + (t.centeredSlides ? e.minTranslate() : 0) - u) /
                (o.swiperSlideSize + a),
            h =
                (l - r[0] + (t.centeredSlides ? e.minTranslate() : 0) - u) /
                (o.swiperSlideSize + a),
            g = -(l - u),
            m = g + e.slidesSizesGrid[d],
            v = g >= 0 && g <= e.size - e.slidesSizesGrid[d],
            b =
                (g >= 0 && g < e.size - 1) ||
                (m > 1 && m <= e.size) ||
                (g <= 0 && m >= e.size)
        ;(b && (e.visibleSlides.push(o), e.visibleSlidesIndexes.push(d)),
            ue(o, b, t.slideVisibleClass),
            ue(o, v, t.slideFullyVisibleClass),
            (o.progress = n ? -p : p),
            (o.originalProgress = n ? -h : h))
    }
}
function Je(i) {
    const e = this
    if (typeof i > 'u') {
        const u = e.rtlTranslate ? -1 : 1
        i = (e && e.translate && e.translate * u) || 0
    }
    const t = e.params,
        s = e.maxTranslate() - e.minTranslate()
    let { progress: n, isBeginning: r, isEnd: l, progressLoop: a } = e
    const d = r,
        o = l
    if (s === 0) ((n = 0), (r = !0), (l = !0))
    else {
        n = (i - e.minTranslate()) / s
        const u = Math.abs(i - e.minTranslate()) < 1,
            p = Math.abs(i - e.maxTranslate()) < 1
        ;((r = u || n <= 0), (l = p || n >= 1), u && (n = 0), p && (n = 1))
    }
    if (t.loop) {
        const u = e.getSlideIndexByData(0),
            p = e.getSlideIndexByData(e.slides.length - 1),
            h = e.slidesGrid[u],
            g = e.slidesGrid[p],
            m = e.slidesGrid[e.slidesGrid.length - 1],
            v = Math.abs(i)
        ;(v >= h ? (a = (v - h) / m) : (a = (v + m - g) / m), a > 1 && (a -= 1))
    }
    ;(Object.assign(e, {
        progress: n,
        progressLoop: a,
        isBeginning: r,
        isEnd: l,
    }),
        (t.watchSlidesProgress || (t.centeredSlides && t.autoHeight)) &&
            e.updateSlidesProgress(i),
        r && !d && e.emit('reachBeginning toEdge'),
        l && !o && e.emit('reachEnd toEdge'),
        ((d && !r) || (o && !l)) && e.emit('fromEdge'),
        e.emit('progress', n))
}
const se = (i, e, t) => {
    e && !i.classList.contains(t)
        ? i.classList.add(t)
        : !e && i.classList.contains(t) && i.classList.remove(t)
}
function Qe() {
    const i = this,
        { slides: e, params: t, slidesEl: s, activeIndex: n } = i,
        r = i.virtual && t.virtual.enabled,
        l = i.grid && t.grid && t.grid.rows > 1,
        a = (p) => H(s, `.${t.slideClass}${p}, swiper-slide${p}`)[0]
    let d, o, u
    if (r)
        if (t.loop) {
            let p = n - i.virtual.slidesBefore
            ;(p < 0 && (p = i.virtual.slides.length + p),
                p >= i.virtual.slides.length && (p -= i.virtual.slides.length),
                (d = a(`[data-swiper-slide-index="${p}"]`)))
        } else d = a(`[data-swiper-slide-index="${n}"]`)
    else
        l
            ? ((d = e.find((p) => p.column === n)),
              (u = e.find((p) => p.column === n + 1)),
              (o = e.find((p) => p.column === n - 1)))
            : (d = e[n])
    ;(d &&
        (l ||
            ((u = $e(d, `.${t.slideClass}, swiper-slide`)[0]),
            t.loop && !u && (u = e[0]),
            (o = Ve(d, `.${t.slideClass}, swiper-slide`)[0]),
            t.loop && !o === 0 && (o = e[e.length - 1]))),
        e.forEach((p) => {
            ;(se(p, p === d, t.slideActiveClass),
                se(p, p === u, t.slideNextClass),
                se(p, p === o, t.slidePrevClass))
        }),
        i.emitSlidesClasses())
}
const U = (i, e) => {
        if (!i || i.destroyed || !i.params) return
        const t = () =>
                i.isElement ? 'swiper-slide' : `.${i.params.slideClass}`,
            s = e.closest(t())
        if (s) {
            let n = s.querySelector(`.${i.params.lazyPreloaderClass}`)
            ;(!n &&
                i.isElement &&
                (s.shadowRoot
                    ? (n = s.shadowRoot.querySelector(
                          `.${i.params.lazyPreloaderClass}`,
                      ))
                    : requestAnimationFrame(() => {
                          s.shadowRoot &&
                              ((n = s.shadowRoot.querySelector(
                                  `.${i.params.lazyPreloaderClass}`,
                              )),
                              n && n.remove())
                      })),
                n && n.remove())
        }
    },
    re = (i, e) => {
        if (!i.slides[e]) return
        const t = i.slides[e].querySelector('[loading="lazy"]')
        t && t.removeAttribute('loading')
    },
    oe = (i) => {
        if (!i || i.destroyed || !i.params) return
        let e = i.params.lazyPreloadPrevNext
        const t = i.slides.length
        if (!t || !e || e < 0) return
        e = Math.min(e, t)
        const s =
                i.params.slidesPerView === 'auto'
                    ? i.slidesPerViewDynamic()
                    : Math.ceil(i.params.slidesPerView),
            n = i.activeIndex
        if (i.params.grid && i.params.grid.rows > 1) {
            const l = n,
                a = [l - e]
            ;(a.push(...Array.from({ length: e }).map((d, o) => l + s + o)),
                i.slides.forEach((d, o) => {
                    a.includes(d.column) && re(i, o)
                }))
            return
        }
        const r = n + s - 1
        if (i.params.rewind || i.params.loop)
            for (let l = n - e; l <= r + e; l += 1) {
                const a = ((l % t) + t) % t
                ;(a < n || a > r) && re(i, a)
            }
        else
            for (
                let l = Math.max(n - e, 0);
                l <= Math.min(r + e, t - 1);
                l += 1
            )
                l !== n && (l > r || l < n) && re(i, l)
    }
function Ze(i) {
    const { slidesGrid: e, params: t } = i,
        s = i.rtlTranslate ? i.translate : -i.translate
    let n
    for (let r = 0; r < e.length; r += 1)
        typeof e[r + 1] < 'u'
            ? s >= e[r] && s < e[r + 1] - (e[r + 1] - e[r]) / 2
                ? (n = r)
                : s >= e[r] && s < e[r + 1] && (n = r + 1)
            : s >= e[r] && (n = r)
    return (t.normalizeSlideIndex && (n < 0 || typeof n > 'u') && (n = 0), n)
}
function et(i) {
    const e = this,
        t = e.rtlTranslate ? e.translate : -e.translate,
        {
            snapGrid: s,
            params: n,
            activeIndex: r,
            realIndex: l,
            snapIndex: a,
        } = e
    let d = i,
        o
    const u = (g) => {
        let m = g - e.virtual.slidesBefore
        return (
            m < 0 && (m = e.virtual.slides.length + m),
            m >= e.virtual.slides.length && (m -= e.virtual.slides.length),
            m
        )
    }
    if ((typeof d > 'u' && (d = Ze(e)), s.indexOf(t) >= 0)) o = s.indexOf(t)
    else {
        const g = Math.min(n.slidesPerGroupSkip, d)
        o = g + Math.floor((d - g) / n.slidesPerGroup)
    }
    if ((o >= s.length && (o = s.length - 1), d === r && !e.params.loop)) {
        o !== a && ((e.snapIndex = o), e.emit('snapIndexChange'))
        return
    }
    if (d === r && e.params.loop && e.virtual && e.params.virtual.enabled) {
        e.realIndex = u(d)
        return
    }
    const p = e.grid && n.grid && n.grid.rows > 1
    let h
    if (e.virtual && n.virtual.enabled && n.loop) h = u(d)
    else if (p) {
        const g = e.slides.find((v) => v.column === d)
        let m = parseInt(g.getAttribute('data-swiper-slide-index'), 10)
        ;(Number.isNaN(m) && (m = Math.max(e.slides.indexOf(g), 0)),
            (h = Math.floor(m / n.grid.rows)))
    } else if (e.slides[d]) {
        const g = e.slides[d].getAttribute('data-swiper-slide-index')
        g ? (h = parseInt(g, 10)) : (h = d)
    } else h = d
    ;(Object.assign(e, {
        previousSnapIndex: a,
        snapIndex: o,
        previousRealIndex: l,
        realIndex: h,
        previousIndex: r,
        activeIndex: d,
    }),
        e.initialized && oe(e),
        e.emit('activeIndexChange'),
        e.emit('snapIndexChange'),
        (e.initialized || e.params.runCallbacksOnInit) &&
            (l !== h && e.emit('realIndexChange'), e.emit('slideChange')))
}
function tt(i, e) {
    const t = this,
        s = t.params
    let n = i.closest(`.${s.slideClass}, swiper-slide`)
    !n &&
        t.isElement &&
        e &&
        e.length > 1 &&
        e.includes(i) &&
        [...e.slice(e.indexOf(i) + 1, e.length)].forEach((a) => {
            !n &&
                a.matches &&
                a.matches(`.${s.slideClass}, swiper-slide`) &&
                (n = a)
        })
    let r = !1,
        l
    if (n) {
        for (let a = 0; a < t.slides.length; a += 1)
            if (t.slides[a] === n) {
                ;((r = !0), (l = a))
                break
            }
    }
    if (n && r)
        ((t.clickedSlide = n),
            t.virtual && t.params.virtual.enabled
                ? (t.clickedIndex = parseInt(
                      n.getAttribute('data-swiper-slide-index'),
                      10,
                  ))
                : (t.clickedIndex = l))
    else {
        ;((t.clickedSlide = void 0), (t.clickedIndex = void 0))
        return
    }
    s.slideToClickedSlide &&
        t.clickedIndex !== void 0 &&
        t.clickedIndex !== t.activeIndex &&
        t.slideToClickedSlide()
}
var it = {
    updateSize: je,
    updateSlides: Ye,
    updateAutoHeight: Xe,
    updateSlidesOffset: Ue,
    updateSlidesProgress: Ke,
    updateProgress: Je,
    updateSlidesClasses: Qe,
    updateActiveIndex: et,
    updateClickedSlide: tt,
}
function st(i) {
    i === void 0 && (i = this.isHorizontal() ? 'x' : 'y')
    const e = this,
        { params: t, rtlTranslate: s, translate: n, wrapperEl: r } = e
    if (t.virtualTranslate) return s ? -n : n
    if (t.cssMode) return n
    let l = ke(r, i)
    return ((l += e.cssOverflowAdjustment()), s && (l = -l), l || 0)
}
function rt(i, e) {
    const t = this,
        { rtlTranslate: s, params: n, wrapperEl: r, progress: l } = t
    let a = 0,
        d = 0
    const o = 0
    ;(t.isHorizontal() ? (a = s ? -i : i) : (d = i),
        n.roundLengths && ((a = Math.floor(a)), (d = Math.floor(d))),
        (t.previousTranslate = t.translate),
        (t.translate = t.isHorizontal() ? a : d),
        n.cssMode
            ? (r[t.isHorizontal() ? 'scrollLeft' : 'scrollTop'] =
                  t.isHorizontal() ? -a : -d)
            : n.virtualTranslate ||
              (t.isHorizontal()
                  ? (a -= t.cssOverflowAdjustment())
                  : (d -= t.cssOverflowAdjustment()),
              (r.style.transform = `translate3d(${a}px, ${d}px, ${o}px)`)))
    let u
    const p = t.maxTranslate() - t.minTranslate()
    ;(p === 0 ? (u = 0) : (u = (i - t.minTranslate()) / p),
        u !== l && t.updateProgress(i),
        t.emit('setTranslate', t.translate, e))
}
function nt() {
    return -this.snapGrid[0]
}
function at() {
    return -this.snapGrid[this.snapGrid.length - 1]
}
function lt(i, e, t, s, n) {
    ;(i === void 0 && (i = 0),
        e === void 0 && (e = this.params.speed),
        t === void 0 && (t = !0),
        s === void 0 && (s = !0))
    const r = this,
        { params: l, wrapperEl: a } = r
    if (r.animating && l.preventInteractionOnTransition) return !1
    const d = r.minTranslate(),
        o = r.maxTranslate()
    let u
    if (
        (s && i > d ? (u = d) : s && i < o ? (u = o) : (u = i),
        r.updateProgress(u),
        l.cssMode)
    ) {
        const p = r.isHorizontal()
        if (e === 0) a[p ? 'scrollLeft' : 'scrollTop'] = -u
        else {
            if (!r.support.smoothScroll)
                return (
                    ye({
                        swiper: r,
                        targetPosition: -u,
                        side: p ? 'left' : 'top',
                    }),
                    !0
                )
            a.scrollTo({ [p ? 'left' : 'top']: -u, behavior: 'smooth' })
        }
        return !0
    }
    return (
        e === 0
            ? (r.setTransition(0),
              r.setTranslate(u),
              t &&
                  (r.emit('beforeTransitionStart', e, n),
                  r.emit('transitionEnd')))
            : (r.setTransition(e),
              r.setTranslate(u),
              t &&
                  (r.emit('beforeTransitionStart', e, n),
                  r.emit('transitionStart')),
              r.animating ||
                  ((r.animating = !0),
                  r.onTranslateToWrapperTransitionEnd ||
                      (r.onTranslateToWrapperTransitionEnd = function (h) {
                          !r ||
                              r.destroyed ||
                              (h.target === this &&
                                  (r.wrapperEl.removeEventListener(
                                      'transitionend',
                                      r.onTranslateToWrapperTransitionEnd,
                                  ),
                                  (r.onTranslateToWrapperTransitionEnd = null),
                                  delete r.onTranslateToWrapperTransitionEnd,
                                  (r.animating = !1),
                                  t && r.emit('transitionEnd')))
                      }),
                  r.wrapperEl.addEventListener(
                      'transitionend',
                      r.onTranslateToWrapperTransitionEnd,
                  ))),
        !0
    )
}
var ot = {
    getTranslate: st,
    setTranslate: rt,
    minTranslate: nt,
    maxTranslate: at,
    translateTo: lt,
}
function dt(i, e) {
    const t = this
    ;(t.params.cssMode ||
        ((t.wrapperEl.style.transitionDuration = `${i}ms`),
        (t.wrapperEl.style.transitionDelay = i === 0 ? '0ms' : '')),
        t.emit('setTransition', i, e))
}
function Ce(i) {
    let { swiper: e, runCallbacks: t, direction: s, step: n } = i
    const { activeIndex: r, previousIndex: l } = e
    let a = s
    ;(a || (r > l ? (a = 'next') : r < l ? (a = 'prev') : (a = 'reset')),
        e.emit(`transition${n}`),
        t && a === 'reset'
            ? e.emit(`slideResetTransition${n}`)
            : t &&
              r !== l &&
              (e.emit(`slideChangeTransition${n}`),
              a === 'next'
                  ? e.emit(`slideNextTransition${n}`)
                  : e.emit(`slidePrevTransition${n}`)))
}
function ct(i, e) {
    i === void 0 && (i = !0)
    const t = this,
        { params: s } = t
    s.cssMode ||
        (s.autoHeight && t.updateAutoHeight(),
        Ce({ swiper: t, runCallbacks: i, direction: e, step: 'Start' }))
}
function ft(i, e) {
    i === void 0 && (i = !0)
    const t = this,
        { params: s } = t
    ;((t.animating = !1),
        !s.cssMode &&
            (t.setTransition(0),
            Ce({ swiper: t, runCallbacks: i, direction: e, step: 'End' })))
}
var ut = { setTransition: dt, transitionStart: ct, transitionEnd: ft }
function pt(i, e, t, s, n) {
    ;(i === void 0 && (i = 0),
        t === void 0 && (t = !0),
        typeof i == 'string' && (i = parseInt(i, 10)))
    const r = this
    let l = i
    l < 0 && (l = 0)
    const {
        params: a,
        snapGrid: d,
        slidesGrid: o,
        previousIndex: u,
        activeIndex: p,
        rtlTranslate: h,
        wrapperEl: g,
        enabled: m,
    } = r
    if (
        (!m && !s && !n) ||
        r.destroyed ||
        (r.animating && a.preventInteractionOnTransition)
    )
        return !1
    typeof e > 'u' && (e = r.params.speed)
    const v = Math.min(r.params.slidesPerGroupSkip, l)
    let b = v + Math.floor((l - v) / r.params.slidesPerGroup)
    b >= d.length && (b = d.length - 1)
    const y = -d[b]
    if (a.normalizeSlideIndex)
        for (let M = 0; M < o.length; M += 1) {
            const C = -Math.floor(y * 100),
                P = Math.floor(o[M] * 100),
                T = Math.floor(o[M + 1] * 100)
            typeof o[M + 1] < 'u'
                ? C >= P && C < T - (T - P) / 2
                    ? (l = M)
                    : C >= P && C < T && (l = M + 1)
                : C >= P && (l = M)
        }
    if (
        r.initialized &&
        l !== p &&
        ((!r.allowSlideNext &&
            (h
                ? y > r.translate && y > r.minTranslate()
                : y < r.translate && y < r.minTranslate())) ||
            (!r.allowSlidePrev &&
                y > r.translate &&
                y > r.maxTranslate() &&
                (p || 0) !== l))
    )
        return !1
    ;(l !== (u || 0) && t && r.emit('beforeSlideChangeStart'),
        r.updateProgress(y))
    let c
    l > p ? (c = 'next') : l < p ? (c = 'prev') : (c = 'reset')
    const f = r.virtual && r.params.virtual.enabled
    if (!(f && n) && ((h && -y === r.translate) || (!h && y === r.translate)))
        return (
            r.updateActiveIndex(l),
            a.autoHeight && r.updateAutoHeight(),
            r.updateSlidesClasses(),
            a.effect !== 'slide' && r.setTranslate(y),
            c !== 'reset' && (r.transitionStart(t, c), r.transitionEnd(t, c)),
            !1
        )
    if (a.cssMode) {
        const M = r.isHorizontal(),
            C = h ? y : -y
        if (e === 0)
            (f &&
                ((r.wrapperEl.style.scrollSnapType = 'none'),
                (r._immediateVirtual = !0)),
                f && !r._cssModeVirtualInitialSet && r.params.initialSlide > 0
                    ? ((r._cssModeVirtualInitialSet = !0),
                      requestAnimationFrame(() => {
                          g[M ? 'scrollLeft' : 'scrollTop'] = C
                      }))
                    : (g[M ? 'scrollLeft' : 'scrollTop'] = C),
                f &&
                    requestAnimationFrame(() => {
                        ;((r.wrapperEl.style.scrollSnapType = ''),
                            (r._immediateVirtual = !1))
                    }))
        else {
            if (!r.support.smoothScroll)
                return (
                    ye({
                        swiper: r,
                        targetPosition: C,
                        side: M ? 'left' : 'top',
                    }),
                    !0
                )
            g.scrollTo({ [M ? 'left' : 'top']: C, behavior: 'smooth' })
        }
        return !0
    }
    const E = Ee().isSafari
    return (
        f && !n && E && r.isElement && r.virtual.update(!1, !1, l),
        r.setTransition(e),
        r.setTranslate(y),
        r.updateActiveIndex(l),
        r.updateSlidesClasses(),
        r.emit('beforeTransitionStart', e, s),
        r.transitionStart(t, c),
        e === 0
            ? r.transitionEnd(t, c)
            : r.animating ||
              ((r.animating = !0),
              r.onSlideToWrapperTransitionEnd ||
                  (r.onSlideToWrapperTransitionEnd = function (C) {
                      !r ||
                          r.destroyed ||
                          (C.target === this &&
                              (r.wrapperEl.removeEventListener(
                                  'transitionend',
                                  r.onSlideToWrapperTransitionEnd,
                              ),
                              (r.onSlideToWrapperTransitionEnd = null),
                              delete r.onSlideToWrapperTransitionEnd,
                              r.transitionEnd(t, c)))
                  }),
              r.wrapperEl.addEventListener(
                  'transitionend',
                  r.onSlideToWrapperTransitionEnd,
              )),
        !0
    )
}
function mt(i, e, t, s) {
    ;(i === void 0 && (i = 0),
        t === void 0 && (t = !0),
        typeof i == 'string' && (i = parseInt(i, 10)))
    const n = this
    if (n.destroyed) return
    typeof e > 'u' && (e = n.params.speed)
    const r = n.grid && n.params.grid && n.params.grid.rows > 1
    let l = i
    if (n.params.loop)
        if (n.virtual && n.params.virtual.enabled)
            l = l + n.virtual.slidesBefore
        else {
            let a
            if (r) {
                const h = l * n.params.grid.rows
                a = n.slides.find(
                    (g) => g.getAttribute('data-swiper-slide-index') * 1 === h,
                ).column
            } else a = n.getSlideIndexByData(l)
            const d = r
                    ? Math.ceil(n.slides.length / n.params.grid.rows)
                    : n.slides.length,
                { centeredSlides: o } = n.params
            let u = n.params.slidesPerView
            u === 'auto'
                ? (u = n.slidesPerViewDynamic())
                : ((u = Math.ceil(parseFloat(n.params.slidesPerView, 10))),
                  o && u % 2 === 0 && (u = u + 1))
            let p = d - a < u
            if (
                (o && (p = p || a < Math.ceil(u / 2)),
                s && o && n.params.slidesPerView !== 'auto' && !r && (p = !1),
                p)
            ) {
                const h = o
                    ? a < n.activeIndex
                        ? 'prev'
                        : 'next'
                    : a - n.activeIndex - 1 < n.params.slidesPerView
                      ? 'next'
                      : 'prev'
                n.loopFix({
                    direction: h,
                    slideTo: !0,
                    activeSlideIndex: h === 'next' ? a + 1 : a - d + 1,
                    slideRealIndex: h === 'next' ? n.realIndex : void 0,
                })
            }
            if (r) {
                const h = l * n.params.grid.rows
                l = n.slides.find(
                    (g) => g.getAttribute('data-swiper-slide-index') * 1 === h,
                ).column
            } else l = n.getSlideIndexByData(l)
        }
    return (
        requestAnimationFrame(() => {
            n.slideTo(l, e, t, s)
        }),
        n
    )
}
function ht(i, e, t) {
    e === void 0 && (e = !0)
    const s = this,
        { enabled: n, params: r, animating: l } = s
    if (!n || s.destroyed) return s
    typeof i > 'u' && (i = s.params.speed)
    let a = r.slidesPerGroup
    r.slidesPerView === 'auto' &&
        r.slidesPerGroup === 1 &&
        r.slidesPerGroupAuto &&
        (a = Math.max(s.slidesPerViewDynamic('current', !0), 1))
    const d = s.activeIndex < r.slidesPerGroupSkip ? 1 : a,
        o = s.virtual && r.virtual.enabled
    if (r.loop) {
        if (l && !o && r.loopPreventsSliding) return !1
        if (
            (s.loopFix({ direction: 'next' }),
            (s._clientLeft = s.wrapperEl.clientLeft),
            s.activeIndex === s.slides.length - 1 && r.cssMode)
        )
            return (
                requestAnimationFrame(() => {
                    s.slideTo(s.activeIndex + d, i, e, t)
                }),
                !0
            )
    }
    return r.rewind && s.isEnd
        ? s.slideTo(0, i, e, t)
        : s.slideTo(s.activeIndex + d, i, e, t)
}
function gt(i, e, t) {
    e === void 0 && (e = !0)
    const s = this,
        {
            params: n,
            snapGrid: r,
            slidesGrid: l,
            rtlTranslate: a,
            enabled: d,
            animating: o,
        } = s
    if (!d || s.destroyed) return s
    typeof i > 'u' && (i = s.params.speed)
    const u = s.virtual && n.virtual.enabled
    if (n.loop) {
        if (o && !u && n.loopPreventsSliding) return !1
        ;(s.loopFix({ direction: 'prev' }),
            (s._clientLeft = s.wrapperEl.clientLeft))
    }
    const p = a ? s.translate : -s.translate
    function h(c) {
        return c < 0 ? -Math.floor(Math.abs(c)) : Math.floor(c)
    }
    const g = h(p),
        m = r.map((c) => h(c)),
        v = n.freeMode && n.freeMode.enabled
    let b = r[m.indexOf(g) - 1]
    if (typeof b > 'u' && (n.cssMode || v)) {
        let c
        ;(r.forEach((f, w) => {
            g >= f && (c = w)
        }),
            typeof c < 'u' && (b = v ? r[c] : r[c > 0 ? c - 1 : c]))
    }
    let y = 0
    if (
        (typeof b < 'u' &&
            ((y = l.indexOf(b)),
            y < 0 && (y = s.activeIndex - 1),
            n.slidesPerView === 'auto' &&
                n.slidesPerGroup === 1 &&
                n.slidesPerGroupAuto &&
                ((y = y - s.slidesPerViewDynamic('previous', !0) + 1),
                (y = Math.max(y, 0)))),
        n.rewind && s.isBeginning)
    ) {
        const c =
            s.params.virtual && s.params.virtual.enabled && s.virtual
                ? s.virtual.slides.length - 1
                : s.slides.length - 1
        return s.slideTo(c, i, e, t)
    } else if (n.loop && s.activeIndex === 0 && n.cssMode)
        return (
            requestAnimationFrame(() => {
                s.slideTo(y, i, e, t)
            }),
            !0
        )
    return s.slideTo(y, i, e, t)
}
function vt(i, e, t) {
    e === void 0 && (e = !0)
    const s = this
    if (!s.destroyed)
        return (
            typeof i > 'u' && (i = s.params.speed),
            s.slideTo(s.activeIndex, i, e, t)
        )
}
function wt(i, e, t, s) {
    ;(e === void 0 && (e = !0), s === void 0 && (s = 0.5))
    const n = this
    if (n.destroyed) return
    typeof i > 'u' && (i = n.params.speed)
    let r = n.activeIndex
    const l = Math.min(n.params.slidesPerGroupSkip, r),
        a = l + Math.floor((r - l) / n.params.slidesPerGroup),
        d = n.rtlTranslate ? n.translate : -n.translate
    if (d >= n.snapGrid[a]) {
        const o = n.snapGrid[a],
            u = n.snapGrid[a + 1]
        d - o > (u - o) * s && (r += n.params.slidesPerGroup)
    } else {
        const o = n.snapGrid[a - 1],
            u = n.snapGrid[a]
        d - o <= (u - o) * s && (r -= n.params.slidesPerGroup)
    }
    return (
        (r = Math.max(r, 0)),
        (r = Math.min(r, n.slidesGrid.length - 1)),
        n.slideTo(r, i, e, t)
    )
}
function yt() {
    const i = this
    if (i.destroyed) return
    const { params: e, slidesEl: t } = i,
        s =
            e.slidesPerView === 'auto'
                ? i.slidesPerViewDynamic()
                : e.slidesPerView
    let n = i.getSlideIndexWhenGrid(i.clickedIndex),
        r
    const l = i.isElement ? 'swiper-slide' : `.${e.slideClass}`,
        a = i.grid && i.params.grid && i.params.grid.rows > 1
    if (e.loop) {
        if (i.animating) return
        ;((r = parseInt(
            i.clickedSlide.getAttribute('data-swiper-slide-index'),
            10,
        )),
            e.centeredSlides
                ? i.slideToLoop(r)
                : n >
                    (a
                        ? (i.slides.length - s) / 2 - (i.params.grid.rows - 1)
                        : i.slides.length - s)
                  ? (i.loopFix(),
                    (n = i.getSlideIndex(
                        H(t, `${l}[data-swiper-slide-index="${r}"]`)[0],
                    )),
                    we(() => {
                        i.slideTo(n)
                    }))
                  : i.slideTo(n))
    } else i.slideTo(n)
}
var St = {
    slideTo: pt,
    slideToLoop: mt,
    slideNext: ht,
    slidePrev: gt,
    slideReset: vt,
    slideToClosest: wt,
    slideToClickedSlide: yt,
}
function Tt(i, e) {
    const t = this,
        { params: s, slidesEl: n } = t
    if (!s.loop || (t.virtual && t.params.virtual.enabled)) return
    const r = () => {
            H(n, `.${s.slideClass}, swiper-slide`).forEach((g, m) => {
                g.setAttribute('data-swiper-slide-index', m)
            })
        },
        l = () => {
            const h = H(n, `.${s.slideBlankClass}`)
            ;(h.forEach((g) => {
                g.remove()
            }),
                h.length > 0 && (t.recalcSlides(), t.updateSlides()))
        },
        a = t.grid && s.grid && s.grid.rows > 1
    s.loopAddBlankSlides && (s.slidesPerGroup > 1 || a) && l()
    const d = s.slidesPerGroup * (a ? s.grid.rows : 1),
        o = t.slides.length % d !== 0,
        u = a && t.slides.length % s.grid.rows !== 0,
        p = (h) => {
            for (let g = 0; g < h; g += 1) {
                const m = t.isElement
                    ? Q('swiper-slide', [s.slideBlankClass])
                    : Q('div', [s.slideClass, s.slideBlankClass])
                t.slidesEl.append(m)
            }
        }
    if (o) {
        if (s.loopAddBlankSlides) {
            const h = d - (t.slides.length % d)
            ;(p(h), t.recalcSlides(), t.updateSlides())
        } else
            J(
                'Swiper Loop Warning: The number of slides is not even to slidesPerGroup, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)',
            )
        r()
    } else if (u) {
        if (s.loopAddBlankSlides) {
            const h = s.grid.rows - (t.slides.length % s.grid.rows)
            ;(p(h), t.recalcSlides(), t.updateSlides())
        } else
            J(
                'Swiper Loop Warning: The number of slides is not even to grid.rows, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)',
            )
        r()
    } else r()
    t.loopFix({
        slideRealIndex: i,
        direction: s.centeredSlides ? void 0 : 'next',
        initial: e,
    })
}
function bt(i) {
    let {
        slideRealIndex: e,
        slideTo: t = !0,
        direction: s,
        setTranslate: n,
        activeSlideIndex: r,
        initial: l,
        byController: a,
        byMousewheel: d,
    } = i === void 0 ? {} : i
    const o = this
    if (!o.params.loop) return
    o.emit('beforeLoopFix')
    const {
            slides: u,
            allowSlidePrev: p,
            allowSlideNext: h,
            slidesEl: g,
            params: m,
        } = o,
        { centeredSlides: v, initialSlide: b } = m
    if (
        ((o.allowSlidePrev = !0),
        (o.allowSlideNext = !0),
        o.virtual && m.virtual.enabled)
    ) {
        ;(t &&
            (!m.centeredSlides && o.snapIndex === 0
                ? o.slideTo(o.virtual.slides.length, 0, !1, !0)
                : m.centeredSlides && o.snapIndex < m.slidesPerView
                  ? o.slideTo(o.virtual.slides.length + o.snapIndex, 0, !1, !0)
                  : o.snapIndex === o.snapGrid.length - 1 &&
                    o.slideTo(o.virtual.slidesBefore, 0, !1, !0)),
            (o.allowSlidePrev = p),
            (o.allowSlideNext = h),
            o.emit('loopFix'))
        return
    }
    let y = m.slidesPerView
    y === 'auto'
        ? (y = o.slidesPerViewDynamic())
        : ((y = Math.ceil(parseFloat(m.slidesPerView, 10))),
          v && y % 2 === 0 && (y = y + 1))
    const c = m.slidesPerGroupAuto ? y : m.slidesPerGroup
    let f = v ? Math.max(c, Math.ceil(y / 2)) : c
    ;(f % c !== 0 && (f += c - (f % c)),
        (f += m.loopAdditionalSlides),
        (o.loopedSlides = f))
    const w = o.grid && m.grid && m.grid.rows > 1
    u.length < y + f || (o.params.effect === 'cards' && u.length < y + f * 2)
        ? J(
              'Swiper Loop Warning: The number of slides is not enough for loop mode, it will be disabled or not function properly. You need to add more slides (or make duplicates) or lower the values of slidesPerView and slidesPerGroup parameters',
          )
        : w &&
          m.grid.fill === 'row' &&
          J(
              'Swiper Loop Warning: Loop mode is not compatible with grid.fill = `row`',
          )
    const S = [],
        E = [],
        M = w ? Math.ceil(u.length / m.grid.rows) : u.length,
        C = l && M - b < y && !v
    let P = C ? b : o.activeIndex
    typeof r > 'u'
        ? (r = o.getSlideIndex(
              u.find((L) => L.classList.contains(m.slideActiveClass)),
          ))
        : (P = r)
    const T = s === 'next' || !s,
        x = s === 'prev' || !s
    let O = 0,
        A = 0
    const k = (w ? u[r].column : r) + (v && typeof n > 'u' ? -y / 2 + 0.5 : 0)
    if (k < f) {
        O = Math.max(f - k, c)
        for (let L = 0; L < f - k; L += 1) {
            const G = L - Math.floor(L / M) * M
            if (w) {
                const I = M - G - 1
                for (let D = u.length - 1; D >= 0; D -= 1)
                    u[D].column === I && S.push(D)
            } else S.push(M - G - 1)
        }
    } else if (k + y > M - f) {
        ;((A = Math.max(k - (M - f * 2), c)),
            C && (A = Math.max(A, y - M + b + 1)))
        for (let L = 0; L < A; L += 1) {
            const G = L - Math.floor(L / M) * M
            w
                ? u.forEach((I, D) => {
                      I.column === G && E.push(D)
                  })
                : E.push(G)
        }
    }
    if (
        ((o.__preventObserver__ = !0),
        requestAnimationFrame(() => {
            o.__preventObserver__ = !1
        }),
        o.params.effect === 'cards' &&
            u.length < y + f * 2 &&
            (E.includes(r) && E.splice(E.indexOf(r), 1),
            S.includes(r) && S.splice(S.indexOf(r), 1)),
        x &&
            S.forEach((L) => {
                ;((u[L].swiperLoopMoveDOM = !0),
                    g.prepend(u[L]),
                    (u[L].swiperLoopMoveDOM = !1))
            }),
        T &&
            E.forEach((L) => {
                ;((u[L].swiperLoopMoveDOM = !0),
                    g.append(u[L]),
                    (u[L].swiperLoopMoveDOM = !1))
            }),
        o.recalcSlides(),
        m.slidesPerView === 'auto'
            ? o.updateSlides()
            : w &&
              ((S.length > 0 && x) || (E.length > 0 && T)) &&
              o.slides.forEach((L, G) => {
                  o.grid.updateSlide(G, L, o.slides)
              }),
        m.watchSlidesProgress && o.updateSlidesOffset(),
        t)
    ) {
        if (S.length > 0 && x) {
            if (typeof e > 'u') {
                const L = o.slidesGrid[P],
                    I = o.slidesGrid[P + O] - L
                d
                    ? o.setTranslate(o.translate - I)
                    : (o.slideTo(P + Math.ceil(O), 0, !1, !0),
                      n &&
                          ((o.touchEventsData.startTranslate =
                              o.touchEventsData.startTranslate - I),
                          (o.touchEventsData.currentTranslate =
                              o.touchEventsData.currentTranslate - I)))
            } else if (n) {
                const L = w ? S.length / m.grid.rows : S.length
                ;(o.slideTo(o.activeIndex + L, 0, !1, !0),
                    (o.touchEventsData.currentTranslate = o.translate))
            }
        } else if (E.length > 0 && T)
            if (typeof e > 'u') {
                const L = o.slidesGrid[P],
                    I = o.slidesGrid[P - A] - L
                d
                    ? o.setTranslate(o.translate - I)
                    : (o.slideTo(P - A, 0, !1, !0),
                      n &&
                          ((o.touchEventsData.startTranslate =
                              o.touchEventsData.startTranslate - I),
                          (o.touchEventsData.currentTranslate =
                              o.touchEventsData.currentTranslate - I)))
            } else {
                const L = w ? E.length / m.grid.rows : E.length
                o.slideTo(o.activeIndex - L, 0, !1, !0)
            }
    }
    if (
        ((o.allowSlidePrev = p),
        (o.allowSlideNext = h),
        o.controller && o.controller.control && !a)
    ) {
        const L = {
            slideRealIndex: e,
            direction: s,
            setTranslate: n,
            activeSlideIndex: r,
            byController: !0,
        }
        Array.isArray(o.controller.control)
            ? o.controller.control.forEach((G) => {
                  !G.destroyed &&
                      G.params.loop &&
                      G.loopFix({
                          ...L,
                          slideTo:
                              G.params.slidesPerView === m.slidesPerView
                                  ? t
                                  : !1,
                      })
              })
            : o.controller.control instanceof o.constructor &&
              o.controller.control.params.loop &&
              o.controller.control.loopFix({
                  ...L,
                  slideTo:
                      o.controller.control.params.slidesPerView ===
                      m.slidesPerView
                          ? t
                          : !1,
              })
    }
    o.emit('loopFix')
}
function xt() {
    const i = this,
        { params: e, slidesEl: t } = i
    if (!e.loop || !t || (i.virtual && i.params.virtual.enabled)) return
    i.recalcSlides()
    const s = []
    ;(i.slides.forEach((n) => {
        const r =
            typeof n.swiperSlideIndex > 'u'
                ? n.getAttribute('data-swiper-slide-index') * 1
                : n.swiperSlideIndex
        s[r] = n
    }),
        i.slides.forEach((n) => {
            n.removeAttribute('data-swiper-slide-index')
        }),
        s.forEach((n) => {
            t.append(n)
        }),
        i.recalcSlides(),
        i.slideTo(i.realIndex, 0))
}
var Et = { loopCreate: Tt, loopFix: bt, loopDestroy: xt }
function Ct(i) {
    const e = this
    if (
        !e.params.simulateTouch ||
        (e.params.watchOverflow && e.isLocked) ||
        e.params.cssMode
    )
        return
    const t = e.params.touchEventsTarget === 'container' ? e.el : e.wrapperEl
    ;(e.isElement && (e.__preventObserver__ = !0),
        (t.style.cursor = 'move'),
        (t.style.cursor = i ? 'grabbing' : 'grab'),
        e.isElement &&
            requestAnimationFrame(() => {
                e.__preventObserver__ = !1
            }))
}
function Mt() {
    const i = this
    ;(i.params.watchOverflow && i.isLocked) ||
        i.params.cssMode ||
        (i.isElement && (i.__preventObserver__ = !0),
        (i[
            i.params.touchEventsTarget === 'container' ? 'el' : 'wrapperEl'
        ].style.cursor = ''),
        i.isElement &&
            requestAnimationFrame(() => {
                i.__preventObserver__ = !1
            }))
}
var Pt = { setGrabCursor: Ct, unsetGrabCursor: Mt }
function Lt(i, e) {
    e === void 0 && (e = this)
    function t(s) {
        if (!s || s === R() || s === $()) return null
        s.assignedSlot && (s = s.assignedSlot)
        const n = s.closest(i)
        return !n && !s.getRootNode ? null : n || t(s.getRootNode().host)
    }
    return t(e)
}
function pe(i, e, t) {
    const s = $(),
        { params: n } = i,
        r = n.edgeSwipeDetection,
        l = n.edgeSwipeThreshold
    return r && (t <= l || t >= s.innerWidth - l)
        ? r === 'prevent'
            ? (e.preventDefault(), !0)
            : !1
        : !0
}
function It(i) {
    const e = this,
        t = R()
    let s = i
    s.originalEvent && (s = s.originalEvent)
    const n = e.touchEventsData
    if (s.type === 'pointerdown') {
        if (n.pointerId !== null && n.pointerId !== s.pointerId) return
        n.pointerId = s.pointerId
    } else
        s.type === 'touchstart' &&
            s.targetTouches.length === 1 &&
            (n.touchId = s.targetTouches[0].identifier)
    if (s.type === 'touchstart') {
        pe(e, s, s.targetTouches[0].pageX)
        return
    }
    const { params: r, touches: l, enabled: a } = e
    if (
        !a ||
        (!r.simulateTouch && s.pointerType === 'mouse') ||
        (e.animating && r.preventInteractionOnTransition)
    )
        return
    !e.animating && r.cssMode && r.loop && e.loopFix()
    let d = s.target
    if (
        (r.touchEventsTarget === 'wrapper' && !Be(d, e.wrapperEl)) ||
        ('which' in s && s.which === 3) ||
        ('button' in s && s.button > 0) ||
        (n.isTouched && n.isMoved)
    )
        return
    const o = !!r.noSwipingClass && r.noSwipingClass !== '',
        u = s.composedPath ? s.composedPath() : s.path
    o && s.target && s.target.shadowRoot && u && (d = u[0])
    const p = r.noSwipingSelector
            ? r.noSwipingSelector
            : `.${r.noSwipingClass}`,
        h = !!(s.target && s.target.shadowRoot)
    if (r.noSwiping && (h ? Lt(p, d) : d.closest(p))) {
        e.allowClick = !0
        return
    }
    if (r.swipeHandler && !d.closest(r.swipeHandler)) return
    ;((l.currentX = s.pageX), (l.currentY = s.pageY))
    const g = l.currentX,
        m = l.currentY
    if (!pe(e, s, g)) return
    ;(Object.assign(n, {
        isTouched: !0,
        isMoved: !1,
        allowTouchCallbacks: !0,
        isScrolling: void 0,
        startMoving: void 0,
    }),
        (l.startX = g),
        (l.startY = m),
        (n.touchStartTime = K()),
        (e.allowClick = !0),
        e.updateSize(),
        (e.swipeDirection = void 0),
        r.threshold > 0 && (n.allowThresholdMove = !1))
    let v = !0
    ;(d.matches(n.focusableElements) &&
        ((v = !1), d.nodeName === 'SELECT' && (n.isTouched = !1)),
        t.activeElement &&
            t.activeElement.matches(n.focusableElements) &&
            t.activeElement !== d &&
            (s.pointerType === 'mouse' ||
                (s.pointerType !== 'mouse' &&
                    !d.matches(n.focusableElements))) &&
            t.activeElement.blur())
    const b = v && e.allowTouchMove && r.touchStartPreventDefault
    ;((r.touchStartForcePreventDefault || b) &&
        !d.isContentEditable &&
        s.preventDefault(),
        r.freeMode &&
            r.freeMode.enabled &&
            e.freeMode &&
            e.animating &&
            !r.cssMode &&
            e.freeMode.onTouchStart(),
        e.emit('touchStart', s))
}
function Ot(i) {
    const e = R(),
        t = this,
        s = t.touchEventsData,
        { params: n, touches: r, rtlTranslate: l, enabled: a } = t
    if (!a || (!n.simulateTouch && i.pointerType === 'mouse')) return
    let d = i
    if (
        (d.originalEvent && (d = d.originalEvent),
        d.type === 'pointermove' &&
            (s.touchId !== null || d.pointerId !== s.pointerId))
    )
        return
    let o
    if (d.type === 'touchmove') {
        if (
            ((o = [...d.changedTouches].find(
                (S) => S.identifier === s.touchId,
            )),
            !o || o.identifier !== s.touchId)
        )
            return
    } else o = d
    if (!s.isTouched) {
        s.startMoving && s.isScrolling && t.emit('touchMoveOpposite', d)
        return
    }
    const u = o.pageX,
        p = o.pageY
    if (d.preventedByNestedSwiper) {
        ;((r.startX = u), (r.startY = p))
        return
    }
    if (!t.allowTouchMove) {
        ;(d.target.matches(s.focusableElements) || (t.allowClick = !1),
            s.isTouched &&
                (Object.assign(r, {
                    startX: u,
                    startY: p,
                    currentX: u,
                    currentY: p,
                }),
                (s.touchStartTime = K())))
        return
    }
    if (n.touchReleaseOnEdges && !n.loop)
        if (t.isVertical()) {
            if (
                (p < r.startY && t.translate <= t.maxTranslate()) ||
                (p > r.startY && t.translate >= t.minTranslate())
            ) {
                ;((s.isTouched = !1), (s.isMoved = !1))
                return
            }
        } else {
            if (
                l &&
                ((u > r.startX && -t.translate <= t.maxTranslate()) ||
                    (u < r.startX && -t.translate >= t.minTranslate()))
            )
                return
            if (
                !l &&
                ((u < r.startX && t.translate <= t.maxTranslate()) ||
                    (u > r.startX && t.translate >= t.minTranslate()))
            )
                return
        }
    if (
        (e.activeElement &&
            e.activeElement.matches(s.focusableElements) &&
            e.activeElement !== d.target &&
            d.pointerType !== 'mouse' &&
            e.activeElement.blur(),
        e.activeElement &&
            d.target === e.activeElement &&
            d.target.matches(s.focusableElements))
    ) {
        ;((s.isMoved = !0), (t.allowClick = !1))
        return
    }
    ;(s.allowTouchCallbacks && t.emit('touchMove', d),
        (r.previousX = r.currentX),
        (r.previousY = r.currentY),
        (r.currentX = u),
        (r.currentY = p))
    const h = r.currentX - r.startX,
        g = r.currentY - r.startY
    if (t.params.threshold && Math.sqrt(h ** 2 + g ** 2) < t.params.threshold)
        return
    if (typeof s.isScrolling > 'u') {
        let S
        ;(t.isHorizontal() && r.currentY === r.startY) ||
        (t.isVertical() && r.currentX === r.startX)
            ? (s.isScrolling = !1)
            : h * h + g * g >= 25 &&
              ((S = (Math.atan2(Math.abs(g), Math.abs(h)) * 180) / Math.PI),
              (s.isScrolling = t.isHorizontal()
                  ? S > n.touchAngle
                  : 90 - S > n.touchAngle))
    }
    if (
        (s.isScrolling && t.emit('touchMoveOpposite', d),
        typeof s.startMoving > 'u' &&
            (r.currentX !== r.startX || r.currentY !== r.startY) &&
            (s.startMoving = !0),
        s.isScrolling ||
            (d.type === 'touchmove' && s.preventTouchMoveFromPointerMove))
    ) {
        s.isTouched = !1
        return
    }
    if (!s.startMoving) return
    ;((t.allowClick = !1),
        !n.cssMode && d.cancelable && d.preventDefault(),
        n.touchMoveStopPropagation && !n.nested && d.stopPropagation())
    let m = t.isHorizontal() ? h : g,
        v = t.isHorizontal()
            ? r.currentX - r.previousX
            : r.currentY - r.previousY
    ;(n.oneWayMovement &&
        ((m = Math.abs(m) * (l ? 1 : -1)), (v = Math.abs(v) * (l ? 1 : -1))),
        (r.diff = m),
        (m *= n.touchRatio),
        l && ((m = -m), (v = -v)))
    const b = t.touchesDirection
    ;((t.swipeDirection = m > 0 ? 'prev' : 'next'),
        (t.touchesDirection = v > 0 ? 'prev' : 'next'))
    const y = t.params.loop && !n.cssMode,
        c =
            (t.touchesDirection === 'next' && t.allowSlideNext) ||
            (t.touchesDirection === 'prev' && t.allowSlidePrev)
    if (!s.isMoved) {
        if (
            (y && c && t.loopFix({ direction: t.swipeDirection }),
            (s.startTranslate = t.getTranslate()),
            t.setTransition(0),
            t.animating)
        ) {
            const S = new window.CustomEvent('transitionend', {
                bubbles: !0,
                cancelable: !0,
                detail: { bySwiperTouchMove: !0 },
            })
            t.wrapperEl.dispatchEvent(S)
        }
        ;((s.allowMomentumBounce = !1),
            n.grabCursor &&
                (t.allowSlideNext === !0 || t.allowSlidePrev === !0) &&
                t.setGrabCursor(!0),
            t.emit('sliderFirstMove', d))
    }
    if (
        (new Date().getTime(),
        n._loopSwapReset !== !1 &&
            s.isMoved &&
            s.allowThresholdMove &&
            b !== t.touchesDirection &&
            y &&
            c &&
            Math.abs(m) >= 1)
    ) {
        ;(Object.assign(r, {
            startX: u,
            startY: p,
            currentX: u,
            currentY: p,
            startTranslate: s.currentTranslate,
        }),
            (s.loopSwapReset = !0),
            (s.startTranslate = s.currentTranslate))
        return
    }
    ;(t.emit('sliderMove', d),
        (s.isMoved = !0),
        (s.currentTranslate = m + s.startTranslate))
    let f = !0,
        w = n.resistanceRatio
    if (
        (n.touchReleaseOnEdges && (w = 0),
        m > 0
            ? (y &&
                  c &&
                  s.allowThresholdMove &&
                  s.currentTranslate >
                      (n.centeredSlides
                          ? t.minTranslate() -
                            t.slidesSizesGrid[t.activeIndex + 1] -
                            (n.slidesPerView !== 'auto' &&
                            t.slides.length - n.slidesPerView >= 2
                                ? t.slidesSizesGrid[t.activeIndex + 1] +
                                  t.params.spaceBetween
                                : 0) -
                            t.params.spaceBetween
                          : t.minTranslate()) &&
                  t.loopFix({
                      direction: 'prev',
                      setTranslate: !0,
                      activeSlideIndex: 0,
                  }),
              s.currentTranslate > t.minTranslate() &&
                  ((f = !1),
                  n.resistance &&
                      (s.currentTranslate =
                          t.minTranslate() -
                          1 +
                          (-t.minTranslate() + s.startTranslate + m) ** w)))
            : m < 0 &&
              (y &&
                  c &&
                  s.allowThresholdMove &&
                  s.currentTranslate <
                      (n.centeredSlides
                          ? t.maxTranslate() +
                            t.slidesSizesGrid[t.slidesSizesGrid.length - 1] +
                            t.params.spaceBetween +
                            (n.slidesPerView !== 'auto' &&
                            t.slides.length - n.slidesPerView >= 2
                                ? t.slidesSizesGrid[
                                      t.slidesSizesGrid.length - 1
                                  ] + t.params.spaceBetween
                                : 0)
                          : t.maxTranslate()) &&
                  t.loopFix({
                      direction: 'next',
                      setTranslate: !0,
                      activeSlideIndex:
                          t.slides.length -
                          (n.slidesPerView === 'auto'
                              ? t.slidesPerViewDynamic()
                              : Math.ceil(parseFloat(n.slidesPerView, 10))),
                  }),
              s.currentTranslate < t.maxTranslate() &&
                  ((f = !1),
                  n.resistance &&
                      (s.currentTranslate =
                          t.maxTranslate() +
                          1 -
                          (t.maxTranslate() - s.startTranslate - m) ** w))),
        f && (d.preventedByNestedSwiper = !0),
        !t.allowSlideNext &&
            t.swipeDirection === 'next' &&
            s.currentTranslate < s.startTranslate &&
            (s.currentTranslate = s.startTranslate),
        !t.allowSlidePrev &&
            t.swipeDirection === 'prev' &&
            s.currentTranslate > s.startTranslate &&
            (s.currentTranslate = s.startTranslate),
        !t.allowSlidePrev &&
            !t.allowSlideNext &&
            (s.currentTranslate = s.startTranslate),
        n.threshold > 0)
    )
        if (Math.abs(m) > n.threshold || s.allowThresholdMove) {
            if (!s.allowThresholdMove) {
                ;((s.allowThresholdMove = !0),
                    (r.startX = r.currentX),
                    (r.startY = r.currentY),
                    (s.currentTranslate = s.startTranslate),
                    (r.diff = t.isHorizontal()
                        ? r.currentX - r.startX
                        : r.currentY - r.startY))
                return
            }
        } else {
            s.currentTranslate = s.startTranslate
            return
        }
    !n.followFinger ||
        n.cssMode ||
        (((n.freeMode && n.freeMode.enabled && t.freeMode) ||
            n.watchSlidesProgress) &&
            (t.updateActiveIndex(), t.updateSlidesClasses()),
        n.freeMode &&
            n.freeMode.enabled &&
            t.freeMode &&
            t.freeMode.onTouchMove(),
        t.updateProgress(s.currentTranslate),
        t.setTranslate(s.currentTranslate))
}
function At(i) {
    const e = this,
        t = e.touchEventsData
    let s = i
    s.originalEvent && (s = s.originalEvent)
    let n
    if (s.type === 'touchend' || s.type === 'touchcancel') {
        if (
            ((n = [...s.changedTouches].find(
                (S) => S.identifier === t.touchId,
            )),
            !n || n.identifier !== t.touchId)
        )
            return
    } else {
        if (t.touchId !== null || s.pointerId !== t.pointerId) return
        n = s
    }
    if (
        ['pointercancel', 'pointerout', 'pointerleave', 'contextmenu'].includes(
            s.type,
        ) &&
        !(
            ['pointercancel', 'contextmenu'].includes(s.type) &&
            (e.browser.isSafari || e.browser.isWebView)
        )
    )
        return
    ;((t.pointerId = null), (t.touchId = null))
    const {
        params: l,
        touches: a,
        rtlTranslate: d,
        slidesGrid: o,
        enabled: u,
    } = e
    if (!u || (!l.simulateTouch && s.pointerType === 'mouse')) return
    if (
        (t.allowTouchCallbacks && e.emit('touchEnd', s),
        (t.allowTouchCallbacks = !1),
        !t.isTouched)
    ) {
        ;(t.isMoved && l.grabCursor && e.setGrabCursor(!1),
            (t.isMoved = !1),
            (t.startMoving = !1))
        return
    }
    l.grabCursor &&
        t.isMoved &&
        t.isTouched &&
        (e.allowSlideNext === !0 || e.allowSlidePrev === !0) &&
        e.setGrabCursor(!1)
    const p = K(),
        h = p - t.touchStartTime
    if (e.allowClick) {
        const S = s.path || (s.composedPath && s.composedPath())
        ;(e.updateClickedSlide((S && S[0]) || s.target, S),
            e.emit('tap click', s),
            h < 300 &&
                p - t.lastClickTime < 300 &&
                e.emit('doubleTap doubleClick', s))
    }
    if (
        ((t.lastClickTime = K()),
        we(() => {
            e.destroyed || (e.allowClick = !0)
        }),
        !t.isTouched ||
            !t.isMoved ||
            !e.swipeDirection ||
            (a.diff === 0 && !t.loopSwapReset) ||
            (t.currentTranslate === t.startTranslate && !t.loopSwapReset))
    ) {
        ;((t.isTouched = !1), (t.isMoved = !1), (t.startMoving = !1))
        return
    }
    ;((t.isTouched = !1), (t.isMoved = !1), (t.startMoving = !1))
    let g
    if (
        (l.followFinger
            ? (g = d ? e.translate : -e.translate)
            : (g = -t.currentTranslate),
        l.cssMode)
    )
        return
    if (l.freeMode && l.freeMode.enabled) {
        e.freeMode.onTouchEnd({ currentPos: g })
        return
    }
    const m = g >= -e.maxTranslate() && !e.params.loop
    let v = 0,
        b = e.slidesSizesGrid[0]
    for (
        let S = 0;
        S < o.length;
        S += S < l.slidesPerGroupSkip ? 1 : l.slidesPerGroup
    ) {
        const E = S < l.slidesPerGroupSkip - 1 ? 1 : l.slidesPerGroup
        typeof o[S + E] < 'u'
            ? (m || (g >= o[S] && g < o[S + E])) &&
              ((v = S), (b = o[S + E] - o[S]))
            : (m || g >= o[S]) &&
              ((v = S), (b = o[o.length - 1] - o[o.length - 2]))
    }
    let y = null,
        c = null
    l.rewind &&
        (e.isBeginning
            ? (c =
                  l.virtual && l.virtual.enabled && e.virtual
                      ? e.virtual.slides.length - 1
                      : e.slides.length - 1)
            : e.isEnd && (y = 0))
    const f = (g - o[v]) / b,
        w = v < l.slidesPerGroupSkip - 1 ? 1 : l.slidesPerGroup
    if (h > l.longSwipesMs) {
        if (!l.longSwipes) {
            e.slideTo(e.activeIndex)
            return
        }
        ;(e.swipeDirection === 'next' &&
            (f >= l.longSwipesRatio
                ? e.slideTo(l.rewind && e.isEnd ? y : v + w)
                : e.slideTo(v)),
            e.swipeDirection === 'prev' &&
                (f > 1 - l.longSwipesRatio
                    ? e.slideTo(v + w)
                    : c !== null && f < 0 && Math.abs(f) > l.longSwipesRatio
                      ? e.slideTo(c)
                      : e.slideTo(v)))
    } else {
        if (!l.shortSwipes) {
            e.slideTo(e.activeIndex)
            return
        }
        e.navigation &&
        (s.target === e.navigation.nextEl || s.target === e.navigation.prevEl)
            ? s.target === e.navigation.nextEl
                ? e.slideTo(v + w)
                : e.slideTo(v)
            : (e.swipeDirection === 'next' && e.slideTo(y !== null ? y : v + w),
              e.swipeDirection === 'prev' && e.slideTo(c !== null ? c : v))
    }
}
function me() {
    const i = this,
        { params: e, el: t } = i
    if (t && t.offsetWidth === 0) return
    e.breakpoints && i.setBreakpoint()
    const { allowSlideNext: s, allowSlidePrev: n, snapGrid: r } = i,
        l = i.virtual && i.params.virtual.enabled
    ;((i.allowSlideNext = !0),
        (i.allowSlidePrev = !0),
        i.updateSize(),
        i.updateSlides(),
        i.updateSlidesClasses())
    const a = l && e.loop
    ;((e.slidesPerView === 'auto' || e.slidesPerView > 1) &&
    i.isEnd &&
    !i.isBeginning &&
    !i.params.centeredSlides &&
    !a
        ? i.slideTo(i.slides.length - 1, 0, !1, !0)
        : i.params.loop && !l
          ? i.slideToLoop(i.realIndex, 0, !1, !0)
          : i.slideTo(i.activeIndex, 0, !1, !0),
        i.autoplay &&
            i.autoplay.running &&
            i.autoplay.paused &&
            (clearTimeout(i.autoplay.resizeTimeout),
            (i.autoplay.resizeTimeout = setTimeout(() => {
                i.autoplay &&
                    i.autoplay.running &&
                    i.autoplay.paused &&
                    i.autoplay.resume()
            }, 500))),
        (i.allowSlidePrev = n),
        (i.allowSlideNext = s),
        i.params.watchOverflow && r !== i.snapGrid && i.checkOverflow())
}
function zt(i) {
    const e = this
    e.enabled &&
        (e.allowClick ||
            (e.params.preventClicks && i.preventDefault(),
            e.params.preventClicksPropagation &&
                e.animating &&
                (i.stopPropagation(), i.stopImmediatePropagation())))
}
function kt() {
    const i = this,
        { wrapperEl: e, rtlTranslate: t, enabled: s } = i
    if (!s) return
    ;((i.previousTranslate = i.translate),
        i.isHorizontal()
            ? (i.translate = -e.scrollLeft)
            : (i.translate = -e.scrollTop),
        i.translate === 0 && (i.translate = 0),
        i.updateActiveIndex(),
        i.updateSlidesClasses())
    let n
    const r = i.maxTranslate() - i.minTranslate()
    ;(r === 0 ? (n = 0) : (n = (i.translate - i.minTranslate()) / r),
        n !== i.progress && i.updateProgress(t ? -i.translate : i.translate),
        i.emit('setTranslate', i.translate, !1))
}
function Dt(i) {
    const e = this
    ;(U(e, i.target),
        !(
            e.params.cssMode ||
            (e.params.slidesPerView !== 'auto' && !e.params.autoHeight)
        ) && e.update())
}
function Gt() {
    const i = this
    i.documentTouchHandlerProceeded ||
        ((i.documentTouchHandlerProceeded = !0),
        i.params.touchReleaseOnEdges && (i.el.style.touchAction = 'auto'))
}
const Me = (i, e) => {
    const t = R(),
        { params: s, el: n, wrapperEl: r, device: l } = i,
        a = !!s.nested,
        d = e === 'on' ? 'addEventListener' : 'removeEventListener',
        o = e
    !n ||
        typeof n == 'string' ||
        (t[d]('touchstart', i.onDocumentTouchStart, {
            passive: !1,
            capture: a,
        }),
        n[d]('touchstart', i.onTouchStart, { passive: !1 }),
        n[d]('pointerdown', i.onTouchStart, { passive: !1 }),
        t[d]('touchmove', i.onTouchMove, { passive: !1, capture: a }),
        t[d]('pointermove', i.onTouchMove, { passive: !1, capture: a }),
        t[d]('touchend', i.onTouchEnd, { passive: !0 }),
        t[d]('pointerup', i.onTouchEnd, { passive: !0 }),
        t[d]('pointercancel', i.onTouchEnd, { passive: !0 }),
        t[d]('touchcancel', i.onTouchEnd, { passive: !0 }),
        t[d]('pointerout', i.onTouchEnd, { passive: !0 }),
        t[d]('pointerleave', i.onTouchEnd, { passive: !0 }),
        t[d]('contextmenu', i.onTouchEnd, { passive: !0 }),
        (s.preventClicks || s.preventClicksPropagation) &&
            n[d]('click', i.onClick, !0),
        s.cssMode && r[d]('scroll', i.onScroll),
        s.updateOnWindowResize
            ? i[o](
                  l.ios || l.android
                      ? 'resize orientationchange observerUpdate'
                      : 'resize observerUpdate',
                  me,
                  !0,
              )
            : i[o]('observerUpdate', me, !0),
        n[d]('load', i.onLoad, { capture: !0 }))
}
function Bt() {
    const i = this,
        { params: e } = i
    ;((i.onTouchStart = It.bind(i)),
        (i.onTouchMove = Ot.bind(i)),
        (i.onTouchEnd = At.bind(i)),
        (i.onDocumentTouchStart = Gt.bind(i)),
        e.cssMode && (i.onScroll = kt.bind(i)),
        (i.onClick = zt.bind(i)),
        (i.onLoad = Dt.bind(i)),
        Me(i, 'on'))
}
function Vt() {
    Me(this, 'off')
}
var $t = { attachEvents: Bt, detachEvents: Vt }
const he = (i, e) => i.grid && e.grid && e.grid.rows > 1
function Ft() {
    const i = this,
        { realIndex: e, initialized: t, params: s, el: n } = i,
        r = s.breakpoints
    if (!r || (r && Object.keys(r).length === 0)) return
    const l = R(),
        a =
            s.breakpointsBase === 'window' || !s.breakpointsBase
                ? s.breakpointsBase
                : 'container',
        d =
            ['window', 'container'].includes(s.breakpointsBase) ||
            !s.breakpointsBase
                ? i.el
                : l.querySelector(s.breakpointsBase),
        o = i.getBreakpoint(r, a, d)
    if (!o || i.currentBreakpoint === o) return
    const p = (o in r ? r[o] : void 0) || i.originalParams,
        h = he(i, s),
        g = he(i, p),
        m = i.params.grabCursor,
        v = p.grabCursor,
        b = s.enabled
    ;(h && !g
        ? (n.classList.remove(
              `${s.containerModifierClass}grid`,
              `${s.containerModifierClass}grid-column`,
          ),
          i.emitContainerClasses())
        : !h &&
          g &&
          (n.classList.add(`${s.containerModifierClass}grid`),
          ((p.grid.fill && p.grid.fill === 'column') ||
              (!p.grid.fill && s.grid.fill === 'column')) &&
              n.classList.add(`${s.containerModifierClass}grid-column`),
          i.emitContainerClasses()),
        m && !v ? i.unsetGrabCursor() : !m && v && i.setGrabCursor(),
        ['navigation', 'pagination', 'scrollbar'].forEach((E) => {
            if (typeof p[E] > 'u') return
            const M = s[E] && s[E].enabled,
                C = p[E] && p[E].enabled
            ;(M && !C && i[E].disable(), !M && C && i[E].enable())
        }))
    const y = p.direction && p.direction !== s.direction,
        c = s.loop && (p.slidesPerView !== s.slidesPerView || y),
        f = s.loop
    ;(y && t && i.changeDirection(), F(i.params, p))
    const w = i.params.enabled,
        S = i.params.loop
    ;(Object.assign(i, {
        allowTouchMove: i.params.allowTouchMove,
        allowSlideNext: i.params.allowSlideNext,
        allowSlidePrev: i.params.allowSlidePrev,
    }),
        b && !w ? i.disable() : !b && w && i.enable(),
        (i.currentBreakpoint = o),
        i.emit('_beforeBreakpoint', p),
        t &&
            (c
                ? (i.loopDestroy(), i.loopCreate(e), i.updateSlides())
                : !f && S
                  ? (i.loopCreate(e), i.updateSlides())
                  : f && !S && i.loopDestroy()),
        i.emit('breakpoint', p))
}
function _t(i, e, t) {
    if ((e === void 0 && (e = 'window'), !i || (e === 'container' && !t)))
        return
    let s = !1
    const n = $(),
        r = e === 'window' ? n.innerHeight : t.clientHeight,
        l = Object.keys(i).map((a) => {
            if (typeof a == 'string' && a.indexOf('@') === 0) {
                const d = parseFloat(a.substr(1))
                return { value: r * d, point: a }
            }
            return { value: a, point: a }
        })
    l.sort((a, d) => parseInt(a.value, 10) - parseInt(d.value, 10))
    for (let a = 0; a < l.length; a += 1) {
        const { point: d, value: o } = l[a]
        e === 'window'
            ? n.matchMedia(`(min-width: ${o}px)`).matches && (s = d)
            : o <= t.clientWidth && (s = d)
    }
    return s || 'max'
}
var Ht = { setBreakpoint: Ft, getBreakpoint: _t }
function Rt(i, e) {
    const t = []
    return (
        i.forEach((s) => {
            typeof s == 'object'
                ? Object.keys(s).forEach((n) => {
                      s[n] && t.push(e + n)
                  })
                : typeof s == 'string' && t.push(e + s)
        }),
        t
    )
}
function Nt() {
    const i = this,
        { classNames: e, params: t, rtl: s, el: n, device: r } = i,
        l = Rt(
            [
                'initialized',
                t.direction,
                { 'free-mode': i.params.freeMode && t.freeMode.enabled },
                { autoheight: t.autoHeight },
                { rtl: s },
                { grid: t.grid && t.grid.rows > 1 },
                {
                    'grid-column':
                        t.grid && t.grid.rows > 1 && t.grid.fill === 'column',
                },
                { android: r.android },
                { ios: r.ios },
                { 'css-mode': t.cssMode },
                { centered: t.cssMode && t.centeredSlides },
                { 'watch-progress': t.watchSlidesProgress },
            ],
            t.containerModifierClass,
        )
    ;(e.push(...l), n.classList.add(...e), i.emitContainerClasses())
}
function qt() {
    const i = this,
        { el: e, classNames: t } = i
    !e ||
        typeof e == 'string' ||
        (e.classList.remove(...t), i.emitContainerClasses())
}
var Wt = { addClasses: Nt, removeClasses: qt }
function jt() {
    const i = this,
        { isLocked: e, params: t } = i,
        { slidesOffsetBefore: s } = t
    if (s) {
        const n = i.slides.length - 1,
            r = i.slidesGrid[n] + i.slidesSizesGrid[n] + s * 2
        i.isLocked = i.size > r
    } else i.isLocked = i.snapGrid.length === 1
    ;(t.allowSlideNext === !0 && (i.allowSlideNext = !i.isLocked),
        t.allowSlidePrev === !0 && (i.allowSlidePrev = !i.isLocked),
        e && e !== i.isLocked && (i.isEnd = !1),
        e !== i.isLocked && i.emit(i.isLocked ? 'lock' : 'unlock'))
}
var Yt = { checkOverflow: jt },
    ge = {
        init: !0,
        direction: 'horizontal',
        oneWayMovement: !1,
        swiperElementNodeName: 'SWIPER-CONTAINER',
        touchEventsTarget: 'wrapper',
        initialSlide: 0,
        speed: 300,
        cssMode: !1,
        updateOnWindowResize: !0,
        resizeObserver: !0,
        nested: !1,
        createElements: !1,
        eventsPrefix: 'swiper',
        enabled: !0,
        focusableElements:
            'input, select, option, textarea, button, video, label',
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
        effect: 'slide',
        breakpoints: void 0,
        breakpointsBase: 'window',
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
        noSwipingClass: 'swiper-no-swiping',
        noSwipingSelector: null,
        passiveListeners: !0,
        maxBackfaceHiddenSlides: 10,
        containerModifierClass: 'swiper-',
        slideClass: 'swiper-slide',
        slideBlankClass: 'swiper-slide-blank',
        slideActiveClass: 'swiper-slide-active',
        slideVisibleClass: 'swiper-slide-visible',
        slideFullyVisibleClass: 'swiper-slide-fully-visible',
        slideNextClass: 'swiper-slide-next',
        slidePrevClass: 'swiper-slide-prev',
        wrapperClass: 'swiper-wrapper',
        lazyPreloaderClass: 'swiper-lazy-preloader',
        lazyPreloadPrevNext: 0,
        runCallbacksOnInit: !0,
        _emitClasses: !1,
    }
function Xt(i, e) {
    return function (s) {
        s === void 0 && (s = {})
        const n = Object.keys(s)[0],
            r = s[n]
        if (typeof r != 'object' || r === null) {
            F(e, s)
            return
        }
        if (
            (i[n] === !0 && (i[n] = { enabled: !0 }),
            n === 'navigation' &&
                i[n] &&
                i[n].enabled &&
                !i[n].prevEl &&
                !i[n].nextEl &&
                (i[n].auto = !0),
            ['pagination', 'scrollbar'].indexOf(n) >= 0 &&
                i[n] &&
                i[n].enabled &&
                !i[n].el &&
                (i[n].auto = !0),
            !(n in i && 'enabled' in r))
        ) {
            F(e, s)
            return
        }
        ;(typeof i[n] == 'object' &&
            !('enabled' in i[n]) &&
            (i[n].enabled = !0),
            i[n] || (i[n] = { enabled: !1 }),
            F(e, s))
    }
}
const ne = {
        eventsEmitter: We,
        update: it,
        translate: ot,
        transition: ut,
        slide: St,
        loop: Et,
        grabCursor: Pt,
        events: $t,
        breakpoints: Ht,
        checkOverflow: Yt,
        classes: Wt,
    },
    ae = {}
class _ {
    constructor() {
        let e, t
        for (var s = arguments.length, n = new Array(s), r = 0; r < s; r++)
            n[r] = arguments[r]
        ;(n.length === 1 &&
        n[0].constructor &&
        Object.prototype.toString.call(n[0]).slice(8, -1) === 'Object'
            ? (t = n[0])
            : ([e, t] = n),
            t || (t = {}),
            (t = F({}, t)),
            e && !t.el && (t.el = e))
        const l = R()
        if (
            t.el &&
            typeof t.el == 'string' &&
            l.querySelectorAll(t.el).length > 1
        ) {
            const u = []
            return (
                l.querySelectorAll(t.el).forEach((p) => {
                    const h = F({}, t, { el: p })
                    u.push(new _(h))
                }),
                u
            )
        }
        const a = this
        ;((a.__swiper__ = !0),
            (a.support = be()),
            (a.device = xe({ userAgent: t.userAgent })),
            (a.browser = Ee()),
            (a.eventsListeners = {}),
            (a.eventsAnyListeners = []),
            (a.modules = [...a.__modules__]),
            t.modules &&
                Array.isArray(t.modules) &&
                a.modules.push(...t.modules))
        const d = {}
        a.modules.forEach((u) => {
            u({
                params: t,
                swiper: a,
                extendParams: Xt(t, d),
                on: a.on.bind(a),
                once: a.once.bind(a),
                off: a.off.bind(a),
                emit: a.emit.bind(a),
            })
        })
        const o = F({}, ge, d)
        return (
            (a.params = F({}, o, ae, t)),
            (a.originalParams = F({}, a.params)),
            (a.passedParams = F({}, t)),
            a.params &&
                a.params.on &&
                Object.keys(a.params.on).forEach((u) => {
                    a.on(u, a.params.on[u])
                }),
            a.params && a.params.onAny && a.onAny(a.params.onAny),
            Object.assign(a, {
                enabled: a.params.enabled,
                el: e,
                classNames: [],
                slides: [],
                slidesGrid: [],
                snapGrid: [],
                slidesSizesGrid: [],
                isHorizontal() {
                    return a.params.direction === 'horizontal'
                },
                isVertical() {
                    return a.params.direction === 'vertical'
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
                allowSlideNext: a.params.allowSlideNext,
                allowSlidePrev: a.params.allowSlidePrev,
                touchEventsData: {
                    isTouched: void 0,
                    isMoved: void 0,
                    allowTouchCallbacks: void 0,
                    touchStartTime: void 0,
                    isScrolling: void 0,
                    currentTranslate: void 0,
                    startTranslate: void 0,
                    allowThresholdMove: void 0,
                    focusableElements: a.params.focusableElements,
                    lastClickTime: 0,
                    clickTimeout: void 0,
                    velocities: [],
                    allowMomentumBounce: void 0,
                    startMoving: void 0,
                    pointerId: null,
                    touchId: null,
                },
                allowClick: !0,
                allowTouchMove: a.params.allowTouchMove,
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
            a.emit('_swiper'),
            a.params.init && a.init(),
            a
        )
    }
    getDirectionLabel(e) {
        return this.isHorizontal()
            ? e
            : {
                  width: 'height',
                  'margin-top': 'margin-left',
                  'margin-bottom ': 'margin-right',
                  'margin-left': 'margin-top',
                  'margin-right': 'margin-bottom',
                  'padding-left': 'padding-top',
                  'padding-right': 'padding-bottom',
                  marginRight: 'marginBottom',
              }[e]
    }
    getSlideIndex(e) {
        const { slidesEl: t, params: s } = this,
            n = H(t, `.${s.slideClass}, swiper-slide`),
            r = Z(n[0])
        return Z(e) - r
    }
    getSlideIndexByData(e) {
        return this.getSlideIndex(
            this.slides.find(
                (t) => t.getAttribute('data-swiper-slide-index') * 1 === e,
            ),
        )
    }
    getSlideIndexWhenGrid(e) {
        return (
            this.grid &&
                this.params.grid &&
                this.params.grid.rows > 1 &&
                (this.params.grid.fill === 'column'
                    ? (e = Math.floor(e / this.params.grid.rows))
                    : this.params.grid.fill === 'row' &&
                      (e =
                          e %
                          Math.ceil(
                              this.slides.length / this.params.grid.rows,
                          ))),
            e
        )
    }
    recalcSlides() {
        const e = this,
            { slidesEl: t, params: s } = e
        e.slides = H(t, `.${s.slideClass}, swiper-slide`)
    }
    enable() {
        const e = this
        e.enabled ||
            ((e.enabled = !0),
            e.params.grabCursor && e.setGrabCursor(),
            e.emit('enable'))
    }
    disable() {
        const e = this
        e.enabled &&
            ((e.enabled = !1),
            e.params.grabCursor && e.unsetGrabCursor(),
            e.emit('disable'))
    }
    setProgress(e, t) {
        const s = this
        e = Math.min(Math.max(e, 0), 1)
        const n = s.minTranslate(),
            l = (s.maxTranslate() - n) * e + n
        ;(s.translateTo(l, typeof t > 'u' ? 0 : t),
            s.updateActiveIndex(),
            s.updateSlidesClasses())
    }
    emitContainerClasses() {
        const e = this
        if (!e.params._emitClasses || !e.el) return
        const t = e.el.className
            .split(' ')
            .filter(
                (s) =>
                    s.indexOf('swiper') === 0 ||
                    s.indexOf(e.params.containerModifierClass) === 0,
            )
        e.emit('_containerClasses', t.join(' '))
    }
    getSlideClasses(e) {
        const t = this
        return t.destroyed
            ? ''
            : e.className
                  .split(' ')
                  .filter(
                      (s) =>
                          s.indexOf('swiper-slide') === 0 ||
                          s.indexOf(t.params.slideClass) === 0,
                  )
                  .join(' ')
    }
    emitSlidesClasses() {
        const e = this
        if (!e.params._emitClasses || !e.el) return
        const t = []
        ;(e.slides.forEach((s) => {
            const n = e.getSlideClasses(s)
            ;(t.push({ slideEl: s, classNames: n }),
                e.emit('_slideClass', s, n))
        }),
            e.emit('_slideClasses', t))
    }
    slidesPerViewDynamic(e, t) {
        ;(e === void 0 && (e = 'current'), t === void 0 && (t = !1))
        const s = this,
            {
                params: n,
                slides: r,
                slidesGrid: l,
                slidesSizesGrid: a,
                size: d,
                activeIndex: o,
            } = s
        let u = 1
        if (typeof n.slidesPerView == 'number') return n.slidesPerView
        if (n.centeredSlides) {
            let p = r[o] ? Math.ceil(r[o].swiperSlideSize) : 0,
                h
            for (let g = o + 1; g < r.length; g += 1)
                r[g] &&
                    !h &&
                    ((p += Math.ceil(r[g].swiperSlideSize)),
                    (u += 1),
                    p > d && (h = !0))
            for (let g = o - 1; g >= 0; g -= 1)
                r[g] &&
                    !h &&
                    ((p += r[g].swiperSlideSize), (u += 1), p > d && (h = !0))
        } else if (e === 'current')
            for (let p = o + 1; p < r.length; p += 1)
                (t ? l[p] + a[p] - l[o] < d : l[p] - l[o] < d) && (u += 1)
        else for (let p = o - 1; p >= 0; p -= 1) l[o] - l[p] < d && (u += 1)
        return u
    }
    update() {
        const e = this
        if (!e || e.destroyed) return
        const { snapGrid: t, params: s } = e
        ;(s.breakpoints && e.setBreakpoint(),
            [...e.el.querySelectorAll('[loading="lazy"]')].forEach((l) => {
                l.complete && U(e, l)
            }),
            e.updateSize(),
            e.updateSlides(),
            e.updateProgress(),
            e.updateSlidesClasses())
        function n() {
            const l = e.rtlTranslate ? e.translate * -1 : e.translate,
                a = Math.min(Math.max(l, e.maxTranslate()), e.minTranslate())
            ;(e.setTranslate(a), e.updateActiveIndex(), e.updateSlidesClasses())
        }
        let r
        if (s.freeMode && s.freeMode.enabled && !s.cssMode)
            (n(), s.autoHeight && e.updateAutoHeight())
        else {
            if (
                (s.slidesPerView === 'auto' || s.slidesPerView > 1) &&
                e.isEnd &&
                !s.centeredSlides
            ) {
                const l =
                    e.virtual && s.virtual.enabled ? e.virtual.slides : e.slides
                r = e.slideTo(l.length - 1, 0, !1, !0)
            } else r = e.slideTo(e.activeIndex, 0, !1, !0)
            r || n()
        }
        ;(s.watchOverflow && t !== e.snapGrid && e.checkOverflow(),
            e.emit('update'))
    }
    changeDirection(e, t) {
        t === void 0 && (t = !0)
        const s = this,
            n = s.params.direction
        return (
            e || (e = n === 'horizontal' ? 'vertical' : 'horizontal'),
            e === n ||
                (e !== 'horizontal' && e !== 'vertical') ||
                (s.el.classList.remove(
                    `${s.params.containerModifierClass}${n}`,
                ),
                s.el.classList.add(`${s.params.containerModifierClass}${e}`),
                s.emitContainerClasses(),
                (s.params.direction = e),
                s.slides.forEach((r) => {
                    e === 'vertical'
                        ? (r.style.width = '')
                        : (r.style.height = '')
                }),
                s.emit('changeDirection'),
                t && s.update()),
            s
        )
    }
    changeLanguageDirection(e) {
        const t = this
        ;(t.rtl && e === 'rtl') ||
            (!t.rtl && e === 'ltr') ||
            ((t.rtl = e === 'rtl'),
            (t.rtlTranslate = t.params.direction === 'horizontal' && t.rtl),
            t.rtl
                ? (t.el.classList.add(`${t.params.containerModifierClass}rtl`),
                  (t.el.dir = 'rtl'))
                : (t.el.classList.remove(
                      `${t.params.containerModifierClass}rtl`,
                  ),
                  (t.el.dir = 'ltr')),
            t.update())
    }
    mount(e) {
        const t = this
        if (t.mounted) return !0
        let s = e || t.params.el
        if ((typeof s == 'string' && (s = document.querySelector(s)), !s))
            return !1
        ;((s.swiper = t),
            s.parentNode &&
                s.parentNode.host &&
                s.parentNode.host.nodeName ===
                    t.params.swiperElementNodeName.toUpperCase() &&
                (t.isElement = !0))
        const n = () =>
            `.${(t.params.wrapperClass || '').trim().split(' ').join('.')}`
        let l =
            s && s.shadowRoot && s.shadowRoot.querySelector
                ? s.shadowRoot.querySelector(n())
                : H(s, n())[0]
        return (
            !l &&
                t.params.createElements &&
                ((l = Q('div', t.params.wrapperClass)),
                s.append(l),
                H(s, `.${t.params.slideClass}`).forEach((a) => {
                    l.append(a)
                })),
            Object.assign(t, {
                el: s,
                wrapperEl: l,
                slidesEl:
                    t.isElement && !s.parentNode.host.slideSlots
                        ? s.parentNode.host
                        : l,
                hostEl: t.isElement ? s.parentNode.host : s,
                mounted: !0,
                rtl:
                    s.dir.toLowerCase() === 'rtl' ||
                    N(s, 'direction') === 'rtl',
                rtlTranslate:
                    t.params.direction === 'horizontal' &&
                    (s.dir.toLowerCase() === 'rtl' ||
                        N(s, 'direction') === 'rtl'),
                wrongRTL: N(l, 'display') === '-webkit-box',
            }),
            !0
        )
    }
    init(e) {
        const t = this
        if (t.initialized || t.mount(e) === !1) return t
        ;(t.emit('beforeInit'),
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
        const n = [...t.el.querySelectorAll('[loading="lazy"]')]
        return (
            t.isElement &&
                n.push(...t.hostEl.querySelectorAll('[loading="lazy"]')),
            n.forEach((r) => {
                r.complete
                    ? U(t, r)
                    : r.addEventListener('load', (l) => {
                          U(t, l.target)
                      })
            }),
            oe(t),
            (t.initialized = !0),
            oe(t),
            t.emit('init'),
            t.emit('afterInit'),
            t
        )
    }
    destroy(e, t) {
        ;(e === void 0 && (e = !0), t === void 0 && (t = !0))
        const s = this,
            { params: n, el: r, wrapperEl: l, slides: a } = s
        return (
            typeof s.params > 'u' ||
                s.destroyed ||
                (s.emit('beforeDestroy'),
                (s.initialized = !1),
                s.detachEvents(),
                n.loop && s.loopDestroy(),
                t &&
                    (s.removeClasses(),
                    r && typeof r != 'string' && r.removeAttribute('style'),
                    l && l.removeAttribute('style'),
                    a &&
                        a.length &&
                        a.forEach((d) => {
                            ;(d.classList.remove(
                                n.slideVisibleClass,
                                n.slideFullyVisibleClass,
                                n.slideActiveClass,
                                n.slideNextClass,
                                n.slidePrevClass,
                            ),
                                d.removeAttribute('style'),
                                d.removeAttribute('data-swiper-slide-index'))
                        })),
                s.emit('destroy'),
                Object.keys(s.eventsListeners).forEach((d) => {
                    s.off(d)
                }),
                e !== !1 &&
                    (s.el && typeof s.el != 'string' && (s.el.swiper = null),
                    Ae(s)),
                (s.destroyed = !0)),
            null
        )
    }
    static extendDefaults(e) {
        F(ae, e)
    }
    static get extendedDefaults() {
        return ae
    }
    static get defaults() {
        return ge
    }
    static installModule(e) {
        _.prototype.__modules__ || (_.prototype.__modules__ = [])
        const t = _.prototype.__modules__
        typeof e == 'function' && t.indexOf(e) < 0 && t.push(e)
    }
    static use(e) {
        return Array.isArray(e)
            ? (e.forEach((t) => _.installModule(t)), _)
            : (_.installModule(e), _)
    }
}
Object.keys(ne).forEach((i) => {
    Object.keys(ne[i]).forEach((e) => {
        _.prototype[e] = ne[i][e]
    })
})
_.use([Ne, qe])
function Pe(i, e, t, s) {
    return (
        i.params.createElements &&
            Object.keys(s).forEach((n) => {
                if (!t[n] && t.auto === !0) {
                    let r = H(i.el, `.${s[n]}`)[0]
                    ;(r ||
                        ((r = Q('div', s[n])),
                        (r.className = s[n]),
                        i.el.append(r)),
                        (t[n] = r),
                        (e[n] = r))
                }
            }),
        t
    )
}
function Ut(i) {
    let { swiper: e, extendParams: t, on: s, emit: n } = i
    ;(t({
        navigation: {
            nextEl: null,
            prevEl: null,
            hideOnClick: !1,
            disabledClass: 'swiper-button-disabled',
            hiddenClass: 'swiper-button-hidden',
            lockClass: 'swiper-button-lock',
            navigationDisabledClass: 'swiper-navigation-disabled',
        },
    }),
        (e.navigation = { nextEl: null, prevEl: null }))
    function r(m) {
        let v
        return m &&
            typeof m == 'string' &&
            e.isElement &&
            ((v = e.el.querySelector(m) || e.hostEl.querySelector(m)), v)
            ? v
            : (m &&
                  (typeof m == 'string' &&
                      (v = [...document.querySelectorAll(m)]),
                  e.params.uniqueNavElements &&
                  typeof m == 'string' &&
                  v &&
                  v.length > 1 &&
                  e.el.querySelectorAll(m).length === 1
                      ? (v = e.el.querySelector(m))
                      : v && v.length === 1 && (v = v[0])),
              m && !v ? m : v)
    }
    function l(m, v) {
        const b = e.params.navigation
        ;((m = B(m)),
            m.forEach((y) => {
                y &&
                    (y.classList[v ? 'add' : 'remove'](
                        ...b.disabledClass.split(' '),
                    ),
                    y.tagName === 'BUTTON' && (y.disabled = v),
                    e.params.watchOverflow &&
                        e.enabled &&
                        y.classList[e.isLocked ? 'add' : 'remove'](b.lockClass))
            }))
    }
    function a() {
        const { nextEl: m, prevEl: v } = e.navigation
        if (e.params.loop) {
            ;(l(v, !1), l(m, !1))
            return
        }
        ;(l(v, e.isBeginning && !e.params.rewind),
            l(m, e.isEnd && !e.params.rewind))
    }
    function d(m) {
        ;(m.preventDefault(),
            !(e.isBeginning && !e.params.loop && !e.params.rewind) &&
                (e.slidePrev(), n('navigationPrev')))
    }
    function o(m) {
        ;(m.preventDefault(),
            !(e.isEnd && !e.params.loop && !e.params.rewind) &&
                (e.slideNext(), n('navigationNext')))
    }
    function u() {
        const m = e.params.navigation
        if (
            ((e.params.navigation = Pe(
                e,
                e.originalParams.navigation,
                e.params.navigation,
                { nextEl: 'swiper-button-next', prevEl: 'swiper-button-prev' },
            )),
            !(m.nextEl || m.prevEl))
        )
            return
        let v = r(m.nextEl),
            b = r(m.prevEl)
        ;(Object.assign(e.navigation, { nextEl: v, prevEl: b }),
            (v = B(v)),
            (b = B(b)))
        const y = (c, f) => {
            ;(c && c.addEventListener('click', f === 'next' ? o : d),
                !e.enabled && c && c.classList.add(...m.lockClass.split(' ')))
        }
        ;(v.forEach((c) => y(c, 'next')), b.forEach((c) => y(c, 'prev')))
    }
    function p() {
        let { nextEl: m, prevEl: v } = e.navigation
        ;((m = B(m)), (v = B(v)))
        const b = (y, c) => {
            ;(y.removeEventListener('click', c === 'next' ? o : d),
                y.classList.remove(
                    ...e.params.navigation.disabledClass.split(' '),
                ))
        }
        ;(m.forEach((y) => b(y, 'next')), v.forEach((y) => b(y, 'prev')))
    }
    ;(s('init', () => {
        e.params.navigation.enabled === !1 ? g() : (u(), a())
    }),
        s('toEdge fromEdge lock unlock', () => {
            a()
        }),
        s('destroy', () => {
            p()
        }),
        s('enable disable', () => {
            let { nextEl: m, prevEl: v } = e.navigation
            if (((m = B(m)), (v = B(v)), e.enabled)) {
                a()
                return
            }
            ;[...m, ...v]
                .filter((b) => !!b)
                .forEach((b) => b.classList.add(e.params.navigation.lockClass))
        }),
        s('click', (m, v) => {
            let { nextEl: b, prevEl: y } = e.navigation
            ;((b = B(b)), (y = B(y)))
            const c = v.target
            let f = y.includes(c) || b.includes(c)
            if (e.isElement && !f) {
                const w = v.path || (v.composedPath && v.composedPath())
                w && (f = w.find((S) => b.includes(S) || y.includes(S)))
            }
            if (e.params.navigation.hideOnClick && !f) {
                if (
                    e.pagination &&
                    e.params.pagination &&
                    e.params.pagination.clickable &&
                    (e.pagination.el === c || e.pagination.el.contains(c))
                )
                    return
                let w
                ;(b.length
                    ? (w = b[0].classList.contains(
                          e.params.navigation.hiddenClass,
                      ))
                    : y.length &&
                      (w = y[0].classList.contains(
                          e.params.navigation.hiddenClass,
                      )),
                    n(w === !0 ? 'navigationShow' : 'navigationHide'),
                    [...b, ...y]
                        .filter((S) => !!S)
                        .forEach((S) =>
                            S.classList.toggle(e.params.navigation.hiddenClass),
                        ))
            }
        }))
    const h = () => {
            ;(e.el.classList.remove(
                ...e.params.navigation.navigationDisabledClass.split(' '),
            ),
                u(),
                a())
        },
        g = () => {
            ;(e.el.classList.add(
                ...e.params.navigation.navigationDisabledClass.split(' '),
            ),
                p())
        }
    Object.assign(e.navigation, {
        enable: h,
        disable: g,
        update: a,
        init: u,
        destroy: p,
    })
}
function W(i) {
    return (
        i === void 0 && (i = ''),
        `.${i
            .trim()
            .replace(/([\.:!+\/()[\]])/g, '\\$1')
            .replace(/ /g, '.')}`
    )
}
function Kt(i) {
    let { swiper: e, extendParams: t, on: s, emit: n } = i
    const r = 'swiper-pagination'
    ;(t({
        pagination: {
            el: null,
            bulletElement: 'span',
            clickable: !1,
            hideOnClick: !1,
            renderBullet: null,
            renderProgressbar: null,
            renderFraction: null,
            renderCustom: null,
            progressbarOpposite: !1,
            type: 'bullets',
            dynamicBullets: !1,
            dynamicMainBullets: 1,
            formatFractionCurrent: (c) => c,
            formatFractionTotal: (c) => c,
            bulletClass: `${r}-bullet`,
            bulletActiveClass: `${r}-bullet-active`,
            modifierClass: `${r}-`,
            currentClass: `${r}-current`,
            totalClass: `${r}-total`,
            hiddenClass: `${r}-hidden`,
            progressbarFillClass: `${r}-progressbar-fill`,
            progressbarOppositeClass: `${r}-progressbar-opposite`,
            clickableClass: `${r}-clickable`,
            lockClass: `${r}-lock`,
            horizontalClass: `${r}-horizontal`,
            verticalClass: `${r}-vertical`,
            paginationDisabledClass: `${r}-disabled`,
        },
    }),
        (e.pagination = { el: null, bullets: [] }))
    let l,
        a = 0
    function d() {
        return (
            !e.params.pagination.el ||
            !e.pagination.el ||
            (Array.isArray(e.pagination.el) && e.pagination.el.length === 0)
        )
    }
    function o(c, f) {
        const { bulletActiveClass: w } = e.params.pagination
        c &&
            ((c = c[`${f === 'prev' ? 'previous' : 'next'}ElementSibling`]),
            c &&
                (c.classList.add(`${w}-${f}`),
                (c = c[`${f === 'prev' ? 'previous' : 'next'}ElementSibling`]),
                c && c.classList.add(`${w}-${f}-${f}`)))
    }
    function u(c, f, w) {
        if (((c = c % w), (f = f % w), f === c + 1)) return 'next'
        if (f === c - 1) return 'previous'
    }
    function p(c) {
        const f = c.target.closest(W(e.params.pagination.bulletClass))
        if (!f) return
        c.preventDefault()
        const w = Z(f) * e.params.slidesPerGroup
        if (e.params.loop) {
            if (e.realIndex === w) return
            const S = u(e.realIndex, w, e.slides.length)
            S === 'next'
                ? e.slideNext()
                : S === 'previous'
                  ? e.slidePrev()
                  : e.slideToLoop(w)
        } else e.slideTo(w)
    }
    function h() {
        const c = e.rtl,
            f = e.params.pagination
        if (d()) return
        let w = e.pagination.el
        w = B(w)
        let S, E
        const M =
                e.virtual && e.params.virtual.enabled
                    ? e.virtual.slides.length
                    : e.slides.length,
            C = e.params.loop
                ? Math.ceil(M / e.params.slidesPerGroup)
                : e.snapGrid.length
        if (
            (e.params.loop
                ? ((E = e.previousRealIndex || 0),
                  (S =
                      e.params.slidesPerGroup > 1
                          ? Math.floor(e.realIndex / e.params.slidesPerGroup)
                          : e.realIndex))
                : typeof e.snapIndex < 'u'
                  ? ((S = e.snapIndex), (E = e.previousSnapIndex))
                  : ((E = e.previousIndex || 0), (S = e.activeIndex || 0)),
            f.type === 'bullets' &&
                e.pagination.bullets &&
                e.pagination.bullets.length > 0)
        ) {
            const P = e.pagination.bullets
            let T, x, O
            if (
                (f.dynamicBullets &&
                    ((l = le(P[0], e.isHorizontal() ? 'width' : 'height')),
                    w.forEach((A) => {
                        A.style[e.isHorizontal() ? 'width' : 'height'] =
                            `${l * (f.dynamicMainBullets + 4)}px`
                    }),
                    f.dynamicMainBullets > 1 &&
                        E !== void 0 &&
                        ((a += S - (E || 0)),
                        a > f.dynamicMainBullets - 1
                            ? (a = f.dynamicMainBullets - 1)
                            : a < 0 && (a = 0)),
                    (T = Math.max(S - a, 0)),
                    (x = T + (Math.min(P.length, f.dynamicMainBullets) - 1)),
                    (O = (x + T) / 2)),
                P.forEach((A) => {
                    const z = [
                        ...[
                            '',
                            '-next',
                            '-next-next',
                            '-prev',
                            '-prev-prev',
                            '-main',
                        ].map((k) => `${f.bulletActiveClass}${k}`),
                    ]
                        .map((k) =>
                            typeof k == 'string' && k.includes(' ')
                                ? k.split(' ')
                                : k,
                        )
                        .flat()
                    A.classList.remove(...z)
                }),
                w.length > 1)
            )
                P.forEach((A) => {
                    const z = Z(A)
                    ;(z === S
                        ? A.classList.add(...f.bulletActiveClass.split(' '))
                        : e.isElement && A.setAttribute('part', 'bullet'),
                        f.dynamicBullets &&
                            (z >= T &&
                                z <= x &&
                                A.classList.add(
                                    ...`${f.bulletActiveClass}-main`.split(' '),
                                ),
                            z === T && o(A, 'prev'),
                            z === x && o(A, 'next')))
                })
            else {
                const A = P[S]
                if (
                    (A && A.classList.add(...f.bulletActiveClass.split(' ')),
                    e.isElement &&
                        P.forEach((z, k) => {
                            z.setAttribute(
                                'part',
                                k === S ? 'bullet-active' : 'bullet',
                            )
                        }),
                    f.dynamicBullets)
                ) {
                    const z = P[T],
                        k = P[x]
                    for (let L = T; L <= x; L += 1)
                        P[L] &&
                            P[L].classList.add(
                                ...`${f.bulletActiveClass}-main`.split(' '),
                            )
                    ;(o(z, 'prev'), o(k, 'next'))
                }
            }
            if (f.dynamicBullets) {
                const A = Math.min(P.length, f.dynamicMainBullets + 4),
                    z = (l * A - l) / 2 - O * l,
                    k = c ? 'right' : 'left'
                P.forEach((L) => {
                    L.style[e.isHorizontal() ? k : 'top'] = `${z}px`
                })
            }
        }
        w.forEach((P, T) => {
            if (
                (f.type === 'fraction' &&
                    (P.querySelectorAll(W(f.currentClass)).forEach((x) => {
                        x.textContent = f.formatFractionCurrent(S + 1)
                    }),
                    P.querySelectorAll(W(f.totalClass)).forEach((x) => {
                        x.textContent = f.formatFractionTotal(C)
                    })),
                f.type === 'progressbar')
            ) {
                let x
                f.progressbarOpposite
                    ? (x = e.isHorizontal() ? 'vertical' : 'horizontal')
                    : (x = e.isHorizontal() ? 'horizontal' : 'vertical')
                const O = (S + 1) / C
                let A = 1,
                    z = 1
                ;(x === 'horizontal' ? (A = O) : (z = O),
                    P.querySelectorAll(W(f.progressbarFillClass)).forEach(
                        (k) => {
                            ;((k.style.transform = `translate3d(0,0,0) scaleX(${A}) scaleY(${z})`),
                                (k.style.transitionDuration = `${e.params.speed}ms`))
                        },
                    ))
            }
            ;(f.type === 'custom' && f.renderCustom
                ? (fe(P, f.renderCustom(e, S + 1, C)),
                  T === 0 && n('paginationRender', P))
                : (T === 0 && n('paginationRender', P),
                  n('paginationUpdate', P)),
                e.params.watchOverflow &&
                    e.enabled &&
                    P.classList[e.isLocked ? 'add' : 'remove'](f.lockClass))
        })
    }
    function g() {
        const c = e.params.pagination
        if (d()) return
        const f =
            e.virtual && e.params.virtual.enabled
                ? e.virtual.slides.length
                : e.grid && e.params.grid.rows > 1
                  ? e.slides.length / Math.ceil(e.params.grid.rows)
                  : e.slides.length
        let w = e.pagination.el
        w = B(w)
        let S = ''
        if (c.type === 'bullets') {
            let E = e.params.loop
                ? Math.ceil(f / e.params.slidesPerGroup)
                : e.snapGrid.length
            e.params.freeMode && e.params.freeMode.enabled && E > f && (E = f)
            for (let M = 0; M < E; M += 1)
                c.renderBullet
                    ? (S += c.renderBullet.call(e, M, c.bulletClass))
                    : (S += `<${c.bulletElement} ${e.isElement ? 'part="bullet"' : ''} class="${c.bulletClass}"></${c.bulletElement}>`)
        }
        ;(c.type === 'fraction' &&
            (c.renderFraction
                ? (S = c.renderFraction.call(e, c.currentClass, c.totalClass))
                : (S = `<span class="${c.currentClass}"></span> / <span class="${c.totalClass}"></span>`)),
            c.type === 'progressbar' &&
                (c.renderProgressbar
                    ? (S = c.renderProgressbar.call(e, c.progressbarFillClass))
                    : (S = `<span class="${c.progressbarFillClass}"></span>`)),
            (e.pagination.bullets = []),
            w.forEach((E) => {
                ;(c.type !== 'custom' && fe(E, S || ''),
                    c.type === 'bullets' &&
                        e.pagination.bullets.push(
                            ...E.querySelectorAll(W(c.bulletClass)),
                        ))
            }),
            c.type !== 'custom' && n('paginationRender', w[0]))
    }
    function m() {
        e.params.pagination = Pe(
            e,
            e.originalParams.pagination,
            e.params.pagination,
            { el: 'swiper-pagination' },
        )
        const c = e.params.pagination
        if (!c.el) return
        let f
        ;(typeof c.el == 'string' &&
            e.isElement &&
            (f = e.el.querySelector(c.el)),
            !f &&
                typeof c.el == 'string' &&
                (f = [...document.querySelectorAll(c.el)]),
            f || (f = c.el),
            !(!f || f.length === 0) &&
                (e.params.uniqueNavElements &&
                    typeof c.el == 'string' &&
                    Array.isArray(f) &&
                    f.length > 1 &&
                    ((f = [...e.el.querySelectorAll(c.el)]),
                    f.length > 1 &&
                        (f = f.find((w) => Te(w, '.swiper')[0] === e.el))),
                Array.isArray(f) && f.length === 1 && (f = f[0]),
                Object.assign(e.pagination, { el: f }),
                (f = B(f)),
                f.forEach((w) => {
                    ;(c.type === 'bullets' &&
                        c.clickable &&
                        w.classList.add(...(c.clickableClass || '').split(' ')),
                        w.classList.add(c.modifierClass + c.type),
                        w.classList.add(
                            e.isHorizontal()
                                ? c.horizontalClass
                                : c.verticalClass,
                        ),
                        c.type === 'bullets' &&
                            c.dynamicBullets &&
                            (w.classList.add(
                                `${c.modifierClass}${c.type}-dynamic`,
                            ),
                            (a = 0),
                            c.dynamicMainBullets < 1 &&
                                (c.dynamicMainBullets = 1)),
                        c.type === 'progressbar' &&
                            c.progressbarOpposite &&
                            w.classList.add(c.progressbarOppositeClass),
                        c.clickable && w.addEventListener('click', p),
                        e.enabled || w.classList.add(c.lockClass))
                })))
    }
    function v() {
        const c = e.params.pagination
        if (d()) return
        let f = e.pagination.el
        ;(f &&
            ((f = B(f)),
            f.forEach((w) => {
                ;(w.classList.remove(c.hiddenClass),
                    w.classList.remove(c.modifierClass + c.type),
                    w.classList.remove(
                        e.isHorizontal() ? c.horizontalClass : c.verticalClass,
                    ),
                    c.clickable &&
                        (w.classList.remove(
                            ...(c.clickableClass || '').split(' '),
                        ),
                        w.removeEventListener('click', p)))
            })),
            e.pagination.bullets &&
                e.pagination.bullets.forEach((w) =>
                    w.classList.remove(...c.bulletActiveClass.split(' ')),
                ))
    }
    ;(s('changeDirection', () => {
        if (!e.pagination || !e.pagination.el) return
        const c = e.params.pagination
        let { el: f } = e.pagination
        ;((f = B(f)),
            f.forEach((w) => {
                ;(w.classList.remove(c.horizontalClass, c.verticalClass),
                    w.classList.add(
                        e.isHorizontal() ? c.horizontalClass : c.verticalClass,
                    ))
            }))
    }),
        s('init', () => {
            e.params.pagination.enabled === !1 ? y() : (m(), g(), h())
        }),
        s('activeIndexChange', () => {
            typeof e.snapIndex > 'u' && h()
        }),
        s('snapIndexChange', () => {
            h()
        }),
        s('snapGridLengthChange', () => {
            ;(g(), h())
        }),
        s('destroy', () => {
            v()
        }),
        s('enable disable', () => {
            let { el: c } = e.pagination
            c &&
                ((c = B(c)),
                c.forEach((f) =>
                    f.classList[e.enabled ? 'remove' : 'add'](
                        e.params.pagination.lockClass,
                    ),
                ))
        }),
        s('lock unlock', () => {
            h()
        }),
        s('click', (c, f) => {
            const w = f.target,
                S = B(e.pagination.el)
            if (
                e.params.pagination.el &&
                e.params.pagination.hideOnClick &&
                S &&
                S.length > 0 &&
                !w.classList.contains(e.params.pagination.bulletClass)
            ) {
                if (
                    e.navigation &&
                    ((e.navigation.nextEl && w === e.navigation.nextEl) ||
                        (e.navigation.prevEl && w === e.navigation.prevEl))
                )
                    return
                const E = S[0].classList.contains(
                    e.params.pagination.hiddenClass,
                )
                ;(n(E === !0 ? 'paginationShow' : 'paginationHide'),
                    S.forEach((M) =>
                        M.classList.toggle(e.params.pagination.hiddenClass),
                    ))
            }
        }))
    const b = () => {
            e.el.classList.remove(e.params.pagination.paginationDisabledClass)
            let { el: c } = e.pagination
            ;(c &&
                ((c = B(c)),
                c.forEach((f) =>
                    f.classList.remove(
                        e.params.pagination.paginationDisabledClass,
                    ),
                )),
                m(),
                g(),
                h())
        },
        y = () => {
            e.el.classList.add(e.params.pagination.paginationDisabledClass)
            let { el: c } = e.pagination
            ;(c &&
                ((c = B(c)),
                c.forEach((f) =>
                    f.classList.add(
                        e.params.pagination.paginationDisabledClass,
                    ),
                )),
                v())
        }
    Object.assign(e.pagination, {
        enable: b,
        disable: y,
        render: g,
        update: h,
        init: m,
        destroy: v,
    })
}
function Jt(i) {
    let { swiper: e, extendParams: t, on: s, emit: n, params: r } = i
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
    let l,
        a,
        d = r && r.autoplay ? r.autoplay.delay : 3e3,
        o = r && r.autoplay ? r.autoplay.delay : 3e3,
        u,
        p = new Date().getTime(),
        h,
        g,
        m,
        v,
        b,
        y,
        c
    function f(I) {
        !e ||
            e.destroyed ||
            !e.wrapperEl ||
            (I.target === e.wrapperEl &&
                (e.wrapperEl.removeEventListener('transitionend', f),
                !(c || (I.detail && I.detail.bySwiperTouchMove)) && T()))
    }
    const w = () => {
            if (e.destroyed || !e.autoplay.running) return
            e.autoplay.paused ? (h = !0) : h && ((o = u), (h = !1))
            const I = e.autoplay.paused ? u : p + o - new Date().getTime()
            ;((e.autoplay.timeLeft = I),
                n('autoplayTimeLeft', I, I / d),
                (a = requestAnimationFrame(() => {
                    w()
                })))
        },
        S = () => {
            let I
            return (
                e.virtual && e.params.virtual.enabled
                    ? (I = e.slides.find((V) =>
                          V.classList.contains('swiper-slide-active'),
                      ))
                    : (I = e.slides[e.activeIndex]),
                I
                    ? parseInt(I.getAttribute('data-swiper-autoplay'), 10)
                    : void 0
            )
        },
        E = (I) => {
            if (e.destroyed || !e.autoplay.running) return
            ;(cancelAnimationFrame(a), w())
            let D = typeof I > 'u' ? e.params.autoplay.delay : I
            ;((d = e.params.autoplay.delay), (o = e.params.autoplay.delay))
            const V = S()
            ;(!Number.isNaN(V) &&
                V > 0 &&
                typeof I > 'u' &&
                ((D = V), (d = V), (o = V)),
                (u = D))
            const q = e.params.speed,
                j = () => {
                    !e ||
                        e.destroyed ||
                        (e.params.autoplay.reverseDirection
                            ? !e.isBeginning || e.params.loop || e.params.rewind
                                ? (e.slidePrev(q, !0, !0), n('autoplay'))
                                : e.params.autoplay.stopOnLastSlide ||
                                  (e.slideTo(e.slides.length - 1, q, !0, !0),
                                  n('autoplay'))
                            : !e.isEnd || e.params.loop || e.params.rewind
                              ? (e.slideNext(q, !0, !0), n('autoplay'))
                              : e.params.autoplay.stopOnLastSlide ||
                                (e.slideTo(0, q, !0, !0), n('autoplay')),
                        e.params.cssMode &&
                            ((p = new Date().getTime()),
                            requestAnimationFrame(() => {
                                E()
                            })))
                }
            return (
                D > 0
                    ? (clearTimeout(l),
                      (l = setTimeout(() => {
                          j()
                      }, D)))
                    : requestAnimationFrame(() => {
                          j()
                      }),
                D
            )
        },
        M = () => {
            ;((p = new Date().getTime()),
                (e.autoplay.running = !0),
                E(),
                n('autoplayStart'))
        },
        C = () => {
            ;((e.autoplay.running = !1),
                clearTimeout(l),
                cancelAnimationFrame(a),
                n('autoplayStop'))
        },
        P = (I, D) => {
            if (e.destroyed || !e.autoplay.running) return
            ;(clearTimeout(l), I || (y = !0))
            const V = () => {
                ;(n('autoplayPause'),
                    e.params.autoplay.waitForTransition
                        ? e.wrapperEl.addEventListener('transitionend', f)
                        : T())
            }
            if (((e.autoplay.paused = !0), D)) {
                ;(b && (u = e.params.autoplay.delay), (b = !1), V())
                return
            }
            ;((u = (u || e.params.autoplay.delay) - (new Date().getTime() - p)),
                !(e.isEnd && u < 0 && !e.params.loop) &&
                    (u < 0 && (u = 0), V()))
        },
        T = () => {
            ;(e.isEnd && u < 0 && !e.params.loop) ||
                e.destroyed ||
                !e.autoplay.running ||
                ((p = new Date().getTime()),
                y ? ((y = !1), E(u)) : E(),
                (e.autoplay.paused = !1),
                n('autoplayResume'))
        },
        x = () => {
            if (e.destroyed || !e.autoplay.running) return
            const I = R()
            ;(I.visibilityState === 'hidden' && ((y = !0), P(!0)),
                I.visibilityState === 'visible' && T())
        },
        O = (I) => {
            I.pointerType === 'mouse' &&
                ((y = !0),
                (c = !0),
                !(e.animating || e.autoplay.paused) && P(!0))
        },
        A = (I) => {
            I.pointerType === 'mouse' && ((c = !1), e.autoplay.paused && T())
        },
        z = () => {
            e.params.autoplay.pauseOnMouseEnter &&
                (e.el.addEventListener('pointerenter', O),
                e.el.addEventListener('pointerleave', A))
        },
        k = () => {
            e.el &&
                typeof e.el != 'string' &&
                (e.el.removeEventListener('pointerenter', O),
                e.el.removeEventListener('pointerleave', A))
        },
        L = () => {
            R().addEventListener('visibilitychange', x)
        },
        G = () => {
            R().removeEventListener('visibilitychange', x)
        }
    ;(s('init', () => {
        e.params.autoplay.enabled && (z(), L(), M())
    }),
        s('destroy', () => {
            ;(k(), G(), e.autoplay.running && C())
        }),
        s('_freeModeStaticRelease', () => {
            ;(m || y) && T()
        }),
        s('_freeModeNoMomentumRelease', () => {
            e.params.autoplay.disableOnInteraction ? C() : P(!0, !0)
        }),
        s('beforeTransitionStart', (I, D, V) => {
            e.destroyed ||
                !e.autoplay.running ||
                (V || !e.params.autoplay.disableOnInteraction ? P(!0, !0) : C())
        }),
        s('sliderFirstMove', () => {
            if (!(e.destroyed || !e.autoplay.running)) {
                if (e.params.autoplay.disableOnInteraction) {
                    C()
                    return
                }
                ;((g = !0),
                    (m = !1),
                    (y = !1),
                    (v = setTimeout(() => {
                        ;((y = !0), (m = !0), P(!0))
                    }, 200)))
            }
        }),
        s('touchEnd', () => {
            if (!(e.destroyed || !e.autoplay.running || !g)) {
                if (
                    (clearTimeout(v),
                    clearTimeout(l),
                    e.params.autoplay.disableOnInteraction)
                ) {
                    ;((m = !1), (g = !1))
                    return
                }
                ;(m && e.params.cssMode && T(), (m = !1), (g = !1))
            }
        }),
        s('slideChange', () => {
            e.destroyed || !e.autoplay.running || (b = !0)
        }),
        Object.assign(e.autoplay, { start: M, stop: C, pause: P, resume: T }))
}
function Qt(i) {
    const {
        effect: e,
        swiper: t,
        on: s,
        setTranslate: n,
        setTransition: r,
        overwriteParams: l,
        perspective: a,
        recreateShadows: d,
        getEffectParams: o,
    } = i
    ;(s('beforeInit', () => {
        if (t.params.effect !== e) return
        ;(t.classNames.push(`${t.params.containerModifierClass}${e}`),
            a &&
                a() &&
                t.classNames.push(`${t.params.containerModifierClass}3d`))
        const p = l ? l() : {}
        ;(Object.assign(t.params, p), Object.assign(t.originalParams, p))
    }),
        s('setTranslate _virtualUpdated', () => {
            t.params.effect === e && n()
        }),
        s('setTransition', (p, h) => {
            t.params.effect === e && r(h)
        }),
        s('transitionEnd', () => {
            if (t.params.effect === e && d) {
                if (!o || !o().slideShadows) return
                ;(t.slides.forEach((p) => {
                    p.querySelectorAll(
                        '.swiper-slide-shadow-top, .swiper-slide-shadow-right, .swiper-slide-shadow-bottom, .swiper-slide-shadow-left',
                    ).forEach((h) => h.remove())
                }),
                    d())
            }
        }))
    let u
    s('virtualUpdate', () => {
        t.params.effect === e &&
            (t.slides.length || (u = !0),
            requestAnimationFrame(() => {
                u && t.slides && t.slides.length && (n(), (u = !1))
            }))
    })
}
function Zt(i, e) {
    const t = Se(e)
    return (
        t !== e &&
            ((t.style.backfaceVisibility = 'hidden'),
            (t.style['-webkit-backface-visibility'] = 'hidden')),
        t
    )
}
function ei(i) {
    let { swiper: e, duration: t, transformElements: s } = i
    const { activeIndex: n } = e
    if (e.params.virtualTranslate && t !== 0) {
        let r = !1,
            l
        ;((l = s),
            l.forEach((a) => {
                Fe(a, () => {
                    if (r || !e || e.destroyed) return
                    ;((r = !0), (e.animating = !1))
                    const d = new window.CustomEvent('transitionend', {
                        bubbles: !0,
                        cancelable: !0,
                    })
                    e.wrapperEl.dispatchEvent(d)
                })
            }))
    }
}
function ti(i) {
    let { swiper: e, extendParams: t, on: s } = i
    ;(t({ fadeEffect: { crossFade: !1 } }),
        Qt({
            effect: 'fade',
            swiper: e,
            on: s,
            setTranslate: () => {
                const { slides: l } = e,
                    a = e.params.fadeEffect
                for (let d = 0; d < l.length; d += 1) {
                    const o = e.slides[d]
                    let p = -o.swiperSlideOffset
                    e.params.virtualTranslate || (p -= e.translate)
                    let h = 0
                    e.isHorizontal() || ((h = p), (p = 0))
                    const g = e.params.fadeEffect.crossFade
                            ? Math.max(1 - Math.abs(o.progress), 0)
                            : 1 + Math.min(Math.max(o.progress, -1), 0),
                        m = Zt(a, o)
                    ;((m.style.opacity = g),
                        (m.style.transform = `translate3d(${p}px, ${h}px, 0px)`))
                }
            },
            setTransition: (l) => {
                const a = e.slides.map((d) => Se(d))
                ;(a.forEach((d) => {
                    d.style.transitionDuration = `${l}ms`
                }),
                    ei({ swiper: e, duration: l, transformElements: a }))
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
function Le() {
    document.querySelectorAll('.swiper').forEach((e) => {
        e.dataset.initialized || ((e.dataset.initialized = !0), ii(e))
    })
}
Le()
document.addEventListener('livewire:navigated', () => {
    Le()
})
function ii(i) {
    let e = i.querySelector('.swiper-controls')
    e || (e = i.parentNode.querySelector('.swiper-controls'))
    const t = e == null ? void 0 : e.querySelector('.swiper-pagination'),
        s = e == null ? void 0 : e.querySelector('.swiper-button-prev'),
        n = e == null ? void 0 : e.querySelector('.swiper-button-next'),
        r = i.dataset.fade === '1',
        l = i.dataset.loop === '1',
        a = i.dataset.auto === '1',
        d = parseInt(i.dataset.delay, 10) || 5e3,
        o = i.dataset.drag !== void 0 ? i.dataset.drag === '1' : !0,
        u = i.dataset.align || 'center',
        p = parseInt(i.dataset.perview, 10) || 1
    let h = [],
        g = {
            loop: l,
            centeredSlides: u === 'center',
            slidesPerView: p,
            spaceBetween: 0,
            speed: 300,
            allowTouchMove: o,
            grabCursor: o,
            watchSlidesProgress: !0,
            on: {
                init: function () {
                    ;(i.classList.add('swiper-ready'), v(this))
                },
                slideChange: function () {
                    v(this)
                },
                resize: function () {
                    this.update()
                },
            },
        }
    if (i.dataset.breakpoint)
        try {
            g.breakpoints = JSON.parse(i.dataset.breakpoint)
        } catch (f) {
            console.error('Invalid JSON in data-breakpoint:', f)
        }
    ;((s || n) &&
        (h.push(Ut),
        (g.navigation = {
            nextEl: n,
            prevEl: s,
            disabledClass: 'swiper-button-disabled',
        })),
        t &&
            (h.push(Kt),
            (g.pagination = {
                el: t,
                clickable: !0,
                bulletClass: 'swiper-pagination-bullet',
                bulletActiveClass: 'swiper-pagination-bullet-active',
            })),
        a &&
            (h.push(Jt),
            (g.autoplay = {
                delay: d,
                stopOnLastSlide: !1,
                disableOnInteraction: !0,
                pauseOnMouseEnter: !0,
            })),
        r &&
            (h.push(ti),
            (g.effect = 'fade'),
            (g.fadeEffect = { crossFade: !0 }),
            (g.slidesPerView = 1),
            (g.centeredSlides = !0)),
        (g.modules = h))
    let m = new _(i, g)
    function v(f) {
        f.slides.forEach((S, E) => {
            ;(S.classList.remove('swiper-slide-selected'),
                E === f.activeIndex && S.classList.add('swiper-slide-selected'))
        })
    }
    function b(f) {
        ;(i.classList.toggle('swiper-disabled', !f),
            m.autoplay && (f ? m.autoplay.start() : m.autoplay.stop()))
    }
    ;(new IntersectionObserver((f) => {
        f.forEach((w) => {
            w.isIntersecting ? b(!0) : b(!1)
        })
    }).observe(i),
        i.querySelectorAll('img').forEach((f) => {
            f.addEventListener('load', () => {
                ;(m.update(), b(!0))
            })
        }),
        i.addEventListener('enable-carousel', () => b(!0), !1),
        i.addEventListener('disable-carousel', () => b(!1), !1),
        (i.swiper = m))
}
