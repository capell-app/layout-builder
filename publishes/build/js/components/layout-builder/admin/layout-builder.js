/* eslint-disable */
function le(e, t) {
    var n = Object.keys(e)
    if (Object.getOwnPropertySymbols) {
        var i = Object.getOwnPropertySymbols(e)
        ;(t &&
            (i = i.filter(function (o) {
                return Object.getOwnPropertyDescriptor(e, o).enumerable
            })),
            n.push.apply(n, i))
    }
    return n
}
function G(e) {
    for (var t = 1; t < arguments.length; t++) {
        var n = arguments[t] != null ? arguments[t] : {}
        t % 2
            ? le(Object(n), !0).forEach(function (i) {
                  Me(e, i, n[i])
              })
            : Object.getOwnPropertyDescriptors
              ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(n))
              : le(Object(n)).forEach(function (i) {
                    Object.defineProperty(
                        e,
                        i,
                        Object.getOwnPropertyDescriptor(n, i),
                    )
                })
    }
    return e
}
function Rt(e) {
    '@babel/helpers - typeof'
    return (
        typeof Symbol == 'function' && typeof Symbol.iterator == 'symbol'
            ? (Rt = function (t) {
                  return typeof t
              })
            : (Rt = function (t) {
                  return t &&
                      typeof Symbol == 'function' &&
                      t.constructor === Symbol &&
                      t !== Symbol.prototype
                      ? 'symbol'
                      : typeof t
              }),
        Rt(e)
    )
}
function Me(e, t, n) {
    return (
        t in e
            ? Object.defineProperty(e, t, {
                  value: n,
                  enumerable: !0,
                  configurable: !0,
                  writable: !0,
              })
            : (e[t] = n),
        e
    )
}
function U() {
    return (
        (U =
            Object.assign ||
            function (e) {
                for (var t = 1; t < arguments.length; t++) {
                    var n = arguments[t]
                    for (var i in n)
                        Object.prototype.hasOwnProperty.call(n, i) &&
                            (e[i] = n[i])
                }
                return e
            }),
        U.apply(this, arguments)
    )
}
function ke(e, t) {
    if (e == null) return {}
    var n = {},
        i = Object.keys(e),
        o,
        r
    for (r = 0; r < i.length; r++)
        ((o = i[r]), !(t.indexOf(o) >= 0) && (n[o] = e[o]))
    return n
}
function Fe(e, t) {
    if (e == null) return {}
    var n = ke(e, t),
        i,
        o
    if (Object.getOwnPropertySymbols) {
        var r = Object.getOwnPropertySymbols(e)
        for (o = 0; o < r.length; o++)
            ((i = r[o]),
                !(t.indexOf(i) >= 0) &&
                    Object.prototype.propertyIsEnumerable.call(e, i) &&
                    (n[i] = e[i]))
    }
    return n
}
var We = '1.15.2'
function q(e) {
    if (typeof window < 'u' && window.navigator)
        return !!navigator.userAgent.match(e)
}
var Q = q(/(?:Trident.*rv[ :]?11\.|msie|iemobile|Windows Phone)/i),
    At = q(/Edge/i),
    ue = q(/firefox/i),
    Et = q(/safari/i) && !q(/chrome/i) && !q(/android/i),
    ve = q(/iP(ad|od|hone)/i),
    be = q(/chrome/i) && q(/android/i),
    ye = { capture: !1, passive: !1 }
function w(e, t, n) {
    e.addEventListener(t, n, !Q && ye)
}
function b(e, t, n) {
    e.removeEventListener(t, n, !Q && ye)
}
function Bt(e, t) {
    if (t) {
        if ((t[0] === '>' && (t = t.substring(1)), e))
            try {
                if (e.matches) return e.matches(t)
                if (e.msMatchesSelector) return e.msMatchesSelector(t)
                if (e.webkitMatchesSelector) return e.webkitMatchesSelector(t)
            } catch {
                return !1
            }
        return !1
    }
}
function Be(e) {
    return e.host && e !== document && e.host.nodeType ? e.host : e.parentNode
}
function Y(e, t, n, i) {
    if (e) {
        n = n || document
        do {
            if (
                (t != null &&
                    (t[0] === '>'
                        ? e.parentNode === n && Bt(e, t)
                        : Bt(e, t))) ||
                (i && e === n)
            )
                return e
            if (e === n) break
        } while ((e = Be(e)))
    }
    return null
}
var ce = /\s+/g
function M(e, t, n) {
    if (e && t)
        if (e.classList) e.classList[n ? 'add' : 'remove'](t)
        else {
            var i = (' ' + e.className + ' ')
                .replace(ce, ' ')
                .replace(' ' + t + ' ', ' ')
            e.className = (i + (n ? ' ' + t : '')).replace(ce, ' ')
        }
}
function h(e, t, n) {
    var i = e && e.style
    if (i) {
        if (n === void 0)
            return (
                document.defaultView && document.defaultView.getComputedStyle
                    ? (n = document.defaultView.getComputedStyle(e, ''))
                    : e.currentStyle && (n = e.currentStyle),
                t === void 0 ? n : n[t]
            )
        ;(!(t in i) && t.indexOf('webkit') === -1 && (t = '-webkit-' + t),
            (i[t] = n + (typeof n == 'string' ? '' : 'px')))
    }
}
function dt(e, t) {
    var n = ''
    if (typeof e == 'string') n = e
    else
        do {
            var i = h(e, 'transform')
            i && i !== 'none' && (n = i + ' ' + n)
        } while (!t && (e = e.parentNode))
    var o =
        window.DOMMatrix ||
        window.WebKitCSSMatrix ||
        window.CSSMatrix ||
        window.MSCSSMatrix
    return o && new o(n)
}
function we(e, t, n) {
    if (e) {
        var i = e.getElementsByTagName(t),
            o = 0,
            r = i.length
        if (n) for (; o < r; o++) n(i[o], o)
        return i
    }
    return []
}
function $() {
    var e = document.scrollingElement
    return e || document.documentElement
}
function A(e, t, n, i, o) {
    if (!(!e.getBoundingClientRect && e !== window)) {
        var r, a, s, l, u, f, d
        if (
            (e !== window && e.parentNode && e !== $()
                ? ((r = e.getBoundingClientRect()),
                  (a = r.top),
                  (s = r.left),
                  (l = r.bottom),
                  (u = r.right),
                  (f = r.height),
                  (d = r.width))
                : ((a = 0),
                  (s = 0),
                  (l = window.innerHeight),
                  (u = window.innerWidth),
                  (f = window.innerHeight),
                  (d = window.innerWidth)),
            (t || n) && e !== window && ((o = o || e.parentNode), !Q))
        )
            do
                if (
                    o &&
                    o.getBoundingClientRect &&
                    (h(o, 'transform') !== 'none' ||
                        (n && h(o, 'position') !== 'static'))
                ) {
                    var m = o.getBoundingClientRect()
                    ;((a -= m.top + parseInt(h(o, 'border-top-width'))),
                        (s -= m.left + parseInt(h(o, 'border-left-width'))),
                        (l = a + r.height),
                        (u = s + r.width))
                    break
                }
            while ((o = o.parentNode))
        if (i && e !== window) {
            var y = dt(o || e),
                v = y && y.a,
                E = y && y.d
            y &&
                ((a /= E),
                (s /= v),
                (d /= v),
                (f /= E),
                (l = a + f),
                (u = s + d))
        }
        return { top: a, left: s, bottom: l, right: u, width: d, height: f }
    }
}
function de(e, t, n) {
    for (var i = tt(e, !0), o = A(e)[t]; i; ) {
        var r = A(i)[n],
            a = void 0
        if ((n === 'top' || n === 'left' ? (a = o >= r) : (a = o <= r), !a))
            return i
        if (i === $()) break
        i = tt(i, !1)
    }
    return !1
}
function ft(e, t, n, i) {
    for (var o = 0, r = 0, a = e.children; r < a.length; ) {
        if (
            a[r].style.display !== 'none' &&
            a[r] !== p.ghost &&
            (i || a[r] !== p.dragged) &&
            Y(a[r], n.draggable, e, !1)
        ) {
            if (o === t) return a[r]
            o++
        }
        r++
    }
    return null
}
function oe(e, t) {
    for (
        var n = e.lastElementChild;
        n && (n === p.ghost || h(n, 'display') === 'none' || (t && !Bt(n, t)));
    )
        n = n.previousElementSibling
    return n || null
}
function W(e, t) {
    var n = 0
    if (!e || !e.parentNode) return -1
    for (; (e = e.previousElementSibling); )
        e.nodeName.toUpperCase() !== 'TEMPLATE' &&
            e !== p.clone &&
            (!t || Bt(e, t)) &&
            n++
    return n
}
function fe(e) {
    var t = 0,
        n = 0,
        i = $()
    if (e)
        do {
            var o = dt(e),
                r = o.a,
                a = o.d
            ;((t += e.scrollLeft * r), (n += e.scrollTop * a))
        } while (e !== i && (e = e.parentNode))
    return [t, n]
}
function Le(e, t) {
    for (var n in e)
        if (e.hasOwnProperty(n)) {
            for (var i in t)
                if (t.hasOwnProperty(i) && t[i] === e[n][i]) return Number(n)
        }
    return -1
}
function tt(e, t) {
    if (!e || !e.getBoundingClientRect) return $()
    var n = e,
        i = !1
    do
        if (n.clientWidth < n.scrollWidth || n.clientHeight < n.scrollHeight) {
            var o = h(n)
            if (
                (n.clientWidth < n.scrollWidth &&
                    (o.overflowX == 'auto' || o.overflowX == 'scroll')) ||
                (n.clientHeight < n.scrollHeight &&
                    (o.overflowY == 'auto' || o.overflowY == 'scroll'))
            ) {
                if (!n.getBoundingClientRect || n === document.body) return $()
                if (i || t) return n
                i = !0
            }
        }
    while ((n = n.parentNode))
    return $()
}
function Xe(e, t) {
    if (e && t) for (var n in t) t.hasOwnProperty(n) && (e[n] = t[n])
    return e
}
function Gt(e, t) {
    return (
        Math.round(e.top) === Math.round(t.top) &&
        Math.round(e.left) === Math.round(t.left) &&
        Math.round(e.height) === Math.round(t.height) &&
        Math.round(e.width) === Math.round(t.width)
    )
}
var St
function Ee(e, t) {
    return function () {
        if (!St) {
            var n = arguments,
                i = this
            ;(n.length === 1 ? e.call(i, n[0]) : e.apply(i, n),
                (St = setTimeout(function () {
                    St = void 0
                }, t)))
        }
    }
}
function Ye() {
    ;(clearTimeout(St), (St = void 0))
}
function Se(e, t, n) {
    ;((e.scrollLeft += t), (e.scrollTop += n))
}
function _e(e) {
    var t = window.Polymer,
        n = window.jQuery || window.Zepto
    return t && t.dom
        ? t.dom(e).cloneNode(!0)
        : n
          ? n(e).clone(!0)[0]
          : e.cloneNode(!0)
}
function Ce(e, t, n) {
    var i = {}
    return (
        Array.from(e.children).forEach(function (o) {
            var r, a, s, l
            if (!(!Y(o, t.draggable, e, !1) || o.animated || o === n)) {
                var u = A(o)
                ;((i.left = Math.min(
                    (r = i.left) !== null && r !== void 0 ? r : 1 / 0,
                    u.left,
                )),
                    (i.top = Math.min(
                        (a = i.top) !== null && a !== void 0 ? a : 1 / 0,
                        u.top,
                    )),
                    (i.right = Math.max(
                        (s = i.right) !== null && s !== void 0 ? s : -1 / 0,
                        u.right,
                    )),
                    (i.bottom = Math.max(
                        (l = i.bottom) !== null && l !== void 0 ? l : -1 / 0,
                        u.bottom,
                    )))
            }
        }),
        (i.width = i.right - i.left),
        (i.height = i.bottom - i.top),
        (i.x = i.left),
        (i.y = i.top),
        i
    )
}
var F = 'Sortable' + new Date().getTime()
function He() {
    var e = [],
        t
    return {
        captureAnimationState: function () {
            if (((e = []), !!this.options.animation)) {
                var i = [].slice.call(this.el.children)
                i.forEach(function (o) {
                    if (!(h(o, 'display') === 'none' || o === p.ghost)) {
                        e.push({ target: o, rect: A(o) })
                        var r = G({}, e[e.length - 1].rect)
                        if (o.thisAnimationDuration) {
                            var a = dt(o, !0)
                            a && ((r.top -= a.f), (r.left -= a.e))
                        }
                        o.fromRect = r
                    }
                })
            }
        },
        addAnimationState: function (i) {
            e.push(i)
        },
        removeAnimationState: function (i) {
            e.splice(Le(e, { target: i }), 1)
        },
        animateAll: function (i) {
            var o = this
            if (!this.options.animation) {
                ;(clearTimeout(t), typeof i == 'function' && i())
                return
            }
            var r = !1,
                a = 0
            ;(e.forEach(function (s) {
                var l = 0,
                    u = s.target,
                    f = u.fromRect,
                    d = A(u),
                    m = u.prevFromRect,
                    y = u.prevToRect,
                    v = s.rect,
                    E = dt(u, !0)
                ;(E && ((d.top -= E.f), (d.left -= E.e)),
                    (u.toRect = d),
                    u.thisAnimationDuration &&
                        Gt(m, d) &&
                        !Gt(f, d) &&
                        (v.top - d.top) / (v.left - d.left) ===
                            (f.top - d.top) / (f.left - d.left) &&
                        (l = Ge(v, m, y, o.options)),
                    Gt(d, f) ||
                        ((u.prevFromRect = f),
                        (u.prevToRect = d),
                        l || (l = o.options.animation),
                        o.animate(u, v, d, l)),
                    l &&
                        ((r = !0),
                        (a = Math.max(a, l)),
                        clearTimeout(u.animationResetTimer),
                        (u.animationResetTimer = setTimeout(function () {
                            ;((u.animationTime = 0),
                                (u.prevFromRect = null),
                                (u.fromRect = null),
                                (u.prevToRect = null),
                                (u.thisAnimationDuration = null))
                        }, l)),
                        (u.thisAnimationDuration = l)))
            }),
                clearTimeout(t),
                r
                    ? (t = setTimeout(function () {
                          typeof i == 'function' && i()
                      }, a))
                    : typeof i == 'function' && i(),
                (e = []))
        },
        animate: function (i, o, r, a) {
            if (a) {
                ;(h(i, 'transition', ''), h(i, 'transform', ''))
                var s = dt(this.el),
                    l = s && s.a,
                    u = s && s.d,
                    f = (o.left - r.left) / (l || 1),
                    d = (o.top - r.top) / (u || 1)
                ;((i.animatingX = !!f),
                    (i.animatingY = !!d),
                    h(i, 'transform', 'translate3d(' + f + 'px,' + d + 'px,0)'),
                    (this.forRepaintDummy = $e(i)),
                    h(
                        i,
                        'transition',
                        'transform ' +
                            a +
                            'ms' +
                            (this.options.easing
                                ? ' ' + this.options.easing
                                : ''),
                    ),
                    h(i, 'transform', 'translate3d(0,0,0)'),
                    typeof i.animated == 'number' && clearTimeout(i.animated),
                    (i.animated = setTimeout(function () {
                        ;(h(i, 'transition', ''),
                            h(i, 'transform', ''),
                            (i.animated = !1),
                            (i.animatingX = !1),
                            (i.animatingY = !1))
                    }, a)))
            }
        },
    }
}
function $e(e) {
    return e.offsetWidth
}
function Ge(e, t, n, i) {
    return (
        (Math.sqrt(Math.pow(t.top - e.top, 2) + Math.pow(t.left - e.left, 2)) /
            Math.sqrt(
                Math.pow(t.top - n.top, 2) + Math.pow(t.left - n.left, 2),
            )) *
        i.animation
    )
}
var st = [],
    zt = { initializeByDefault: !0 },
    Tt = {
        mount: function (t) {
            for (var n in zt)
                zt.hasOwnProperty(n) && !(n in t) && (t[n] = zt[n])
            ;(st.forEach(function (i) {
                if (i.pluginName === t.pluginName)
                    throw 'Sortable: Cannot mount plugin '.concat(
                        t.pluginName,
                        ' more than once',
                    )
            }),
                st.push(t))
        },
        pluginEvent: function (t, n, i) {
            var o = this
            ;((this.eventCanceled = !1),
                (i.cancel = function () {
                    o.eventCanceled = !0
                }))
            var r = t + 'Global'
            st.forEach(function (a) {
                n[a.pluginName] &&
                    (n[a.pluginName][r] &&
                        n[a.pluginName][r](G({ sortable: n }, i)),
                    n.options[a.pluginName] &&
                        n[a.pluginName][t] &&
                        n[a.pluginName][t](G({ sortable: n }, i)))
            })
        },
        initializePlugins: function (t, n, i, o) {
            st.forEach(function (s) {
                var l = s.pluginName
                if (!(!t.options[l] && !s.initializeByDefault)) {
                    var u = new s(t, n, t.options)
                    ;((u.sortable = t),
                        (u.options = t.options),
                        (t[l] = u),
                        U(i, u.defaults))
                }
            })
            for (var r in t.options)
                if (t.options.hasOwnProperty(r)) {
                    var a = this.modifyOption(t, r, t.options[r])
                    typeof a < 'u' && (t.options[r] = a)
                }
        },
        getEventProperties: function (t, n) {
            var i = {}
            return (
                st.forEach(function (o) {
                    typeof o.eventProperties == 'function' &&
                        U(i, o.eventProperties.call(n[o.pluginName], t))
                }),
                i
            )
        },
        modifyOption: function (t, n, i) {
            var o
            return (
                st.forEach(function (r) {
                    t[r.pluginName] &&
                        r.optionListeners &&
                        typeof r.optionListeners[n] == 'function' &&
                        (o = r.optionListeners[n].call(t[r.pluginName], i))
                }),
                o
            )
        },
    }
function ze(e) {
    var t = e.sortable,
        n = e.rootEl,
        i = e.name,
        o = e.targetEl,
        r = e.cloneEl,
        a = e.toEl,
        s = e.fromEl,
        l = e.oldIndex,
        u = e.newIndex,
        f = e.oldDraggableIndex,
        d = e.newDraggableIndex,
        m = e.originalEvent,
        y = e.putSortable,
        v = e.extraEventProperties
    if (((t = t || (n && n[F])), !!t)) {
        var E,
            B = t.options,
            z = 'on' + i.charAt(0).toUpperCase() + i.substr(1)
        ;(window.CustomEvent && !Q && !At
            ? (E = new CustomEvent(i, { bubbles: !0, cancelable: !0 }))
            : ((E = document.createEvent('Event')), E.initEvent(i, !0, !0)),
            (E.to = a || n),
            (E.from = s || n),
            (E.item = o || n),
            (E.clone = r),
            (E.oldIndex = l),
            (E.newIndex = u),
            (E.oldDraggableIndex = f),
            (E.newDraggableIndex = d),
            (E.originalEvent = m),
            (E.pullMode = y ? y.lastPutMode : void 0))
        var I = G(G({}, v), Tt.getEventProperties(i, t))
        for (var L in I) E[L] = I[L]
        ;(n && n.dispatchEvent(E), B[z] && B[z].call(t, E))
    }
}
var je = ['evt'],
    x = function (t, n) {
        var i =
                arguments.length > 2 && arguments[2] !== void 0
                    ? arguments[2]
                    : {},
            o = i.evt,
            r = Fe(i, je)
        Tt.pluginEvent.bind(p)(
            t,
            n,
            G(
                {
                    dragEl: c,
                    parentEl: C,
                    ghostEl: g,
                    rootEl: S,
                    nextEl: at,
                    lastDownEl: Mt,
                    cloneEl: _,
                    cloneHidden: J,
                    dragStarted: bt,
                    putSortable: T,
                    activeSortable: p.active,
                    originalEvent: o,
                    oldIndex: ct,
                    oldDraggableIndex: _t,
                    newIndex: k,
                    newDraggableIndex: K,
                    hideGhostForTarget: Oe,
                    unhideGhostForTarget: Ie,
                    cloneNowHidden: function () {
                        J = !0
                    },
                    cloneNowShown: function () {
                        J = !1
                    },
                    dispatchSortableEvent: function (s) {
                        P({ sortable: n, name: s, originalEvent: o })
                    },
                },
                r,
            ),
        )
    }
function P(e) {
    ze(
        G(
            {
                putSortable: T,
                cloneEl: _,
                targetEl: c,
                rootEl: S,
                oldIndex: ct,
                oldDraggableIndex: _t,
                newIndex: k,
                newDraggableIndex: K,
            },
            e,
        ),
    )
}
var c,
    C,
    g,
    S,
    at,
    Mt,
    _,
    J,
    ct,
    k,
    _t,
    K,
    It,
    T,
    ut = !1,
    Lt = !1,
    Xt = [],
    ot,
    X,
    jt,
    qt,
    he,
    pe,
    bt,
    lt,
    Ct,
    Dt = !1,
    Pt = !1,
    kt,
    O,
    Ut = [],
    Jt = !1,
    Yt = [],
    $t = typeof document < 'u',
    xt = ve,
    ge = At || Q ? 'cssFloat' : 'float',
    qe = $t && !be && !ve && 'draggable' in document.createElement('div'),
    De = (function () {
        if ($t) {
            if (Q) return !1
            var e = document.createElement('x')
            return (
                (e.style.cssText = 'pointer-events:auto'),
                e.style.pointerEvents === 'auto'
            )
        }
    })(),
    Ae = function (t, n) {
        var i = h(t),
            o =
                parseInt(i.width) -
                parseInt(i.paddingLeft) -
                parseInt(i.paddingRight) -
                parseInt(i.borderLeftWidth) -
                parseInt(i.borderRightWidth),
            r = ft(t, 0, n),
            a = ft(t, 1, n),
            s = r && h(r),
            l = a && h(a),
            u =
                s &&
                parseInt(s.marginLeft) + parseInt(s.marginRight) + A(r).width,
            f =
                l &&
                parseInt(l.marginLeft) + parseInt(l.marginRight) + A(a).width
        if (i.display === 'flex')
            return i.flexDirection === 'column' ||
                i.flexDirection === 'column-reverse'
                ? 'vertical'
                : 'horizontal'
        if (i.display === 'grid')
            return i.gridTemplateColumns.split(' ').length <= 1
                ? 'vertical'
                : 'horizontal'
        if (r && s.float && s.float !== 'none') {
            var d = s.float === 'left' ? 'left' : 'right'
            return a && (l.clear === 'both' || l.clear === d)
                ? 'vertical'
                : 'horizontal'
        }
        return r &&
            (s.display === 'widget' ||
                s.display === 'flex' ||
                s.display === 'table' ||
                s.display === 'grid' ||
                (u >= o && i[ge] === 'none') ||
                (a && i[ge] === 'none' && u + f > o))
            ? 'vertical'
            : 'horizontal'
    },
    Ue = function (t, n, i) {
        var o = i ? t.left : t.top,
            r = i ? t.right : t.bottom,
            a = i ? t.width : t.height,
            s = i ? n.left : n.top,
            l = i ? n.right : n.bottom,
            u = i ? n.width : n.height
        return o === s || r === l || o + a / 2 === s + u / 2
    },
    Qe = function (t, n) {
        var i
        return (
            Xt.some(function (o) {
                var r = o[F].options.emptyInsertThreshold
                if (!(!r || oe(o))) {
                    var a = A(o),
                        s = t >= a.left - r && t <= a.right + r,
                        l = n >= a.top - r && n <= a.bottom + r
                    if (s && l) return (i = o)
                }
            }),
            i
        )
    },
    Te = function (t) {
        function n(r, a) {
            return function (s, l, u, f) {
                var d =
                    s.options.group.name &&
                    l.options.group.name &&
                    s.options.group.name === l.options.group.name
                if (r == null && (a || d)) return !0
                if (r == null || r === !1) return !1
                if (a && r === 'clone') return r
                if (typeof r == 'function')
                    return n(r(s, l, u, f), a)(s, l, u, f)
                var m = (a ? s : l).options.group.name
                return (
                    r === !0 ||
                    (typeof r == 'string' && r === m) ||
                    (r.join && r.indexOf(m) > -1)
                )
            }
        }
        var i = {},
            o = t.group
        ;((!o || Rt(o) != 'object') && (o = { name: o }),
            (i.name = o.name),
            (i.checkPull = n(o.pull, !0)),
            (i.checkPut = n(o.put)),
            (i.revertClone = o.revertClone),
            (t.group = i))
    },
    Oe = function () {
        !De && g && h(g, 'display', 'none')
    },
    Ie = function () {
        !De && g && h(g, 'display', '')
    }
$t &&
    !be &&
    document.addEventListener(
        'click',
        function (e) {
            if (Lt)
                return (
                    e.preventDefault(),
                    e.stopPropagation && e.stopPropagation(),
                    e.stopImmediatePropagation && e.stopImmediatePropagation(),
                    (Lt = !1),
                    !1
                )
        },
        !0,
    )
var rt = function (t) {
        if (c) {
            t = t.touches ? t.touches[0] : t
            var n = Qe(t.clientX, t.clientY)
            if (n) {
                var i = {}
                for (var o in t) t.hasOwnProperty(o) && (i[o] = t[o])
                ;((i.target = i.rootEl = n),
                    (i.preventDefault = void 0),
                    (i.stopPropagation = void 0),
                    n[F]._onDragOver(i))
            }
        }
    },
    Ve = function (t) {
        c && c.parentNode[F]._isOutsideThisEl(t.target)
    }
function p(e, t) {
    if (!(e && e.nodeType && e.nodeType === 1))
        throw 'Sortable: `el` must be an HTMLElement, not '.concat(
            {}.toString.call(e),
        )
    ;((this.el = e), (this.options = t = U({}, t)), (e[F] = this))
    var n = {
        group: null,
        sort: !0,
        disabled: !1,
        store: null,
        handle: null,
        draggable: /^[uo]l$/i.test(e.nodeName) ? '>li' : '>*',
        swapThreshold: 1,
        invertSwap: !1,
        invertedSwapThreshold: null,
        removeCloneOnHide: !0,
        direction: function () {
            return Ae(e, this.options)
        },
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        ignore: 'a, img',
        filter: null,
        preventOnFilter: !0,
        animation: 0,
        easing: null,
        setData: function (a, s) {
            a.setData('Text', s.textContent)
        },
        dropBubble: !1,
        dragoverBubble: !1,
        dataIdAttr: 'data-id',
        delay: 0,
        delayOnTouchOnly: !1,
        touchStartThreshold:
            (Number.parseInt ? Number : window).parseInt(
                window.devicePixelRatio,
                10,
            ) || 1,
        forceFallback: !1,
        fallbackClass: 'sortable-fallback',
        fallbackOnBody: !1,
        fallbackTolerance: 0,
        fallbackOffset: { x: 0, y: 0 },
        supportPointer:
            p.supportPointer !== !1 && 'PointerEvent' in window && !Et,
        emptyInsertThreshold: 5,
    }
    Tt.initializePlugins(this, e, n)
    for (var i in n) !(i in t) && (t[i] = n[i])
    Te(t)
    for (var o in this)
        o.charAt(0) === '_' &&
            typeof this[o] == 'function' &&
            (this[o] = this[o].bind(this))
    ;((this.nativeDraggable = t.forceFallback ? !1 : qe),
        this.nativeDraggable && (this.options.touchStartThreshold = 1),
        t.supportPointer
            ? w(e, 'pointerdown', this._onTapStart)
            : (w(e, 'mousedown', this._onTapStart),
              w(e, 'touchstart', this._onTapStart)),
        this.nativeDraggable &&
            (w(e, 'dragover', this), w(e, 'dragenter', this)),
        Xt.push(this.el),
        t.store && t.store.get && this.sort(t.store.get(this) || []),
        U(this, He()))
}
p.prototype = {
    constructor: p,
    _isOutsideThisEl: function (t) {
        !this.el.contains(t) && t !== this.el && (lt = null)
    },
    _getDirection: function (t, n) {
        return typeof this.options.direction == 'function'
            ? this.options.direction.call(this, t, n, c)
            : this.options.direction
    },
    _onTapStart: function (t) {
        if (t.cancelable) {
            var n = this,
                i = this.el,
                o = this.options,
                r = o.preventOnFilter,
                a = t.type,
                s =
                    (t.touches && t.touches[0]) ||
                    (t.pointerType && t.pointerType === 'touch' && t),
                l = (s || t).target,
                u =
                    (t.target.shadowRoot &&
                        ((t.path && t.path[0]) ||
                            (t.composedPath && t.composedPath()[0]))) ||
                    l,
                f = o.filter
            if (
                (rn(i),
                !c &&
                    !(
                        (/mousedown|pointerdown/.test(a) && t.button !== 0) ||
                        o.disabled
                    ) &&
                    !u.isContentEditable &&
                    !(
                        !this.nativeDraggable &&
                        Et &&
                        l &&
                        l.tagName.toUpperCase() === 'SELECT'
                    ) &&
                    ((l = Y(l, o.draggable, i, !1)),
                    !(l && l.animated) && Mt !== l))
            ) {
                if (
                    ((ct = W(l)),
                    (_t = W(l, o.draggable)),
                    typeof f == 'function')
                ) {
                    if (f.call(this, t, l, this)) {
                        ;(P({
                            sortable: n,
                            rootEl: u,
                            name: 'filter',
                            targetEl: l,
                            toEl: i,
                            fromEl: i,
                        }),
                            x('filter', n, { evt: t }),
                            r && t.cancelable && t.preventDefault())
                        return
                    }
                } else if (
                    f &&
                    ((f = f.split(',').some(function (d) {
                        if (((d = Y(u, d.trim(), i, !1)), d))
                            return (
                                P({
                                    sortable: n,
                                    rootEl: d,
                                    name: 'filter',
                                    targetEl: l,
                                    fromEl: i,
                                    toEl: i,
                                }),
                                x('filter', n, { evt: t }),
                                !0
                            )
                    })),
                    f)
                ) {
                    r && t.cancelable && t.preventDefault()
                    return
                }
                ;(o.handle && !Y(u, o.handle, i, !1)) ||
                    this._prepareDragStart(t, s, l)
            }
        }
    },
    _prepareDragStart: function (t, n, i) {
        var o = this,
            r = o.el,
            a = o.options,
            s = r.ownerDocument,
            l
        if (i && !c && i.parentNode === r) {
            var u = A(i)
            if (
                ((S = r),
                (c = i),
                (C = c.parentNode),
                (at = c.nextSibling),
                (Mt = i),
                (It = a.group),
                (p.dragged = c),
                (ot = {
                    target: c,
                    clientX: (n || t).clientX,
                    clientY: (n || t).clientY,
                }),
                (he = ot.clientX - u.left),
                (pe = ot.clientY - u.top),
                (this._lastX = (n || t).clientX),
                (this._lastY = (n || t).clientY),
                (c.style['will-change'] = 'all'),
                (l = function () {
                    if ((x('delayEnded', o, { evt: t }), p.eventCanceled)) {
                        o._onDrop()
                        return
                    }
                    ;(o._disableDelayedDragEvents(),
                        !ue && o.nativeDraggable && (c.draggable = !0),
                        o._triggerDragStart(t, n),
                        P({ sortable: o, name: 'choose', originalEvent: t }),
                        M(c, a.chosenClass, !0))
                }),
                a.ignore.split(',').forEach(function (f) {
                    we(c, f.trim(), Qt)
                }),
                w(s, 'dragover', rt),
                w(s, 'mousemove', rt),
                w(s, 'touchmove', rt),
                w(s, 'mouseup', o._onDrop),
                w(s, 'touchend', o._onDrop),
                w(s, 'touchcancel', o._onDrop),
                ue &&
                    this.nativeDraggable &&
                    ((this.options.touchStartThreshold = 4),
                    (c.draggable = !0)),
                x('delayStart', this, { evt: t }),
                a.delay &&
                    (!a.delayOnTouchOnly || n) &&
                    (!this.nativeDraggable || !(At || Q)))
            ) {
                if (p.eventCanceled) {
                    this._onDrop()
                    return
                }
                ;(w(s, 'mouseup', o._disableDelayedDrag),
                    w(s, 'touchend', o._disableDelayedDrag),
                    w(s, 'touchcancel', o._disableDelayedDrag),
                    w(s, 'mousemove', o._delayedDragTouchMoveHandler),
                    w(s, 'touchmove', o._delayedDragTouchMoveHandler),
                    a.supportPointer &&
                        w(s, 'pointermove', o._delayedDragTouchMoveHandler),
                    (o._dragStartTimer = setTimeout(l, a.delay)))
            } else l()
        }
    },
    _delayedDragTouchMoveHandler: function (t) {
        var n = t.touches ? t.touches[0] : t
        Math.max(
            Math.abs(n.clientX - this._lastX),
            Math.abs(n.clientY - this._lastY),
        ) >=
            Math.floor(
                this.options.touchStartThreshold /
                    ((this.nativeDraggable && window.devicePixelRatio) || 1),
            ) && this._disableDelayedDrag()
    },
    _disableDelayedDrag: function () {
        ;(c && Qt(c),
            clearTimeout(this._dragStartTimer),
            this._disableDelayedDragEvents())
    },
    _disableDelayedDragEvents: function () {
        var t = this.el.ownerDocument
        ;(b(t, 'mouseup', this._disableDelayedDrag),
            b(t, 'touchend', this._disableDelayedDrag),
            b(t, 'touchcancel', this._disableDelayedDrag),
            b(t, 'mousemove', this._delayedDragTouchMoveHandler),
            b(t, 'touchmove', this._delayedDragTouchMoveHandler),
            b(t, 'pointermove', this._delayedDragTouchMoveHandler))
    },
    _triggerDragStart: function (t, n) {
        ;((n = n || (t.pointerType == 'touch' && t)),
            !this.nativeDraggable || n
                ? this.options.supportPointer
                    ? w(document, 'pointermove', this._onTouchMove)
                    : n
                      ? w(document, 'touchmove', this._onTouchMove)
                      : w(document, 'mousemove', this._onTouchMove)
                : (w(c, 'dragend', this), w(S, 'dragstart', this._onDragStart)))
        try {
            document.selection
                ? Ft(function () {
                      document.selection.empty()
                  })
                : window.getSelection().removeAllRanges()
        } catch {}
    },
    _dragStarted: function (t, n) {
        if (((ut = !1), S && c)) {
            ;(x('dragStarted', this, { evt: n }),
                this.nativeDraggable && w(document, 'dragover', Ve))
            var i = this.options
            ;(!t && M(c, i.dragClass, !1),
                M(c, i.ghostClass, !0),
                (p.active = this),
                t && this._appendGhost(),
                P({ sortable: this, name: 'start', originalEvent: n }))
        } else this._nulling()
    },
    _emulateDragOver: function () {
        if (X) {
            ;((this._lastX = X.clientX), (this._lastY = X.clientY), Oe())
            for (
                var t = document.elementFromPoint(X.clientX, X.clientY), n = t;
                t &&
                t.shadowRoot &&
                ((t = t.shadowRoot.elementFromPoint(X.clientX, X.clientY)),
                t !== n);
            )
                n = t
            if ((c.parentNode[F]._isOutsideThisEl(t), n))
                do {
                    if (n[F]) {
                        var i = void 0
                        if (
                            ((i = n[F]._onDragOver({
                                clientX: X.clientX,
                                clientY: X.clientY,
                                target: t,
                                rootEl: n,
                            })),
                            i && !this.options.dragoverBubble)
                        )
                            break
                    }
                    t = n
                } while ((n = n.parentNode))
            Ie()
        }
    },
    _onTouchMove: function (t) {
        if (ot) {
            var n = this.options,
                i = n.fallbackTolerance,
                o = n.fallbackOffset,
                r = t.touches ? t.touches[0] : t,
                a = g && dt(g, !0),
                s = g && a && a.a,
                l = g && a && a.d,
                u = xt && O && fe(O),
                f =
                    (r.clientX - ot.clientX + o.x) / (s || 1) +
                    (u ? u[0] - Ut[0] : 0) / (s || 1),
                d =
                    (r.clientY - ot.clientY + o.y) / (l || 1) +
                    (u ? u[1] - Ut[1] : 0) / (l || 1)
            if (!p.active && !ut) {
                if (
                    i &&
                    Math.max(
                        Math.abs(r.clientX - this._lastX),
                        Math.abs(r.clientY - this._lastY),
                    ) < i
                )
                    return
                this._onDragStart(t, !0)
            }
            if (g) {
                a
                    ? ((a.e += f - (jt || 0)), (a.f += d - (qt || 0)))
                    : (a = { a: 1, b: 0, c: 0, d: 1, e: f, f: d })
                var m = 'matrix('
                    .concat(a.a, ',')
                    .concat(a.b, ',')
                    .concat(a.c, ',')
                    .concat(a.d, ',')
                    .concat(a.e, ',')
                    .concat(a.f, ')')
                ;(h(g, 'webkitTransform', m),
                    h(g, 'mozTransform', m),
                    h(g, 'msTransform', m),
                    h(g, 'transform', m),
                    (jt = f),
                    (qt = d),
                    (X = r))
            }
            t.cancelable && t.preventDefault()
        }
    },
    _appendGhost: function () {
        if (!g) {
            var t = this.options.fallbackOnBody ? document.body : S,
                n = A(c, !0, xt, !0, t),
                i = this.options
            if (xt) {
                for (
                    O = t;
                    h(O, 'position') === 'static' &&
                    h(O, 'transform') === 'none' &&
                    O !== document;
                )
                    O = O.parentNode
                ;(O !== document.body && O !== document.documentElement
                    ? (O === document && (O = $()),
                      (n.top += O.scrollTop),
                      (n.left += O.scrollLeft))
                    : (O = $()),
                    (Ut = fe(O)))
            }
            ;((g = c.cloneNode(!0)),
                M(g, i.ghostClass, !1),
                M(g, i.fallbackClass, !0),
                M(g, i.dragClass, !0),
                h(g, 'transition', ''),
                h(g, 'transform', ''),
                h(g, 'box-sizing', 'border-box'),
                h(g, 'margin', 0),
                h(g, 'top', n.top),
                h(g, 'left', n.left),
                h(g, 'width', n.width),
                h(g, 'height', n.height),
                h(g, 'opacity', '0.8'),
                h(g, 'position', xt ? 'absolute' : 'fixed'),
                h(g, 'zIndex', '100000'),
                h(g, 'pointerEvents', 'none'),
                (p.ghost = g),
                t.appendChild(g),
                h(
                    g,
                    'transform-origin',
                    (he / parseInt(g.style.width)) * 100 +
                        '% ' +
                        (pe / parseInt(g.style.height)) * 100 +
                        '%',
                ))
        }
    },
    _onDragStart: function (t, n) {
        var i = this,
            o = t.dataTransfer,
            r = i.options
        if ((x('dragStart', this, { evt: t }), p.eventCanceled)) {
            this._onDrop()
            return
        }
        ;(x('setupClone', this),
            p.eventCanceled ||
                ((_ = _e(c)),
                _.removeAttribute('id'),
                (_.draggable = !1),
                (_.style['will-change'] = ''),
                this._hideClone(),
                M(_, this.options.chosenClass, !1),
                (p.clone = _)),
            (i.cloneId = Ft(function () {
                ;(x('clone', i),
                    !p.eventCanceled &&
                        (i.options.removeCloneOnHide || S.insertBefore(_, c),
                        i._hideClone(),
                        P({ sortable: i, name: 'clone' })))
            })),
            !n && M(c, r.dragClass, !0),
            n
                ? ((Lt = !0), (i._loopId = setInterval(i._emulateDragOver, 50)))
                : (b(document, 'mouseup', i._onDrop),
                  b(document, 'touchend', i._onDrop),
                  b(document, 'touchcancel', i._onDrop),
                  o &&
                      ((o.effectAllowed = 'move'),
                      r.setData && r.setData.call(i, o, c)),
                  w(document, 'drop', i),
                  h(c, 'transform', 'translateZ(0)')),
            (ut = !0),
            (i._dragStartId = Ft(i._dragStarted.bind(i, n, t))),
            w(document, 'selectstart', i),
            (bt = !0),
            Et && h(document.body, 'user-select', 'none'))
    },
    _onDragOver: function (t) {
        var n = this.el,
            i = t.target,
            o,
            r,
            a,
            s = this.options,
            l = s.group,
            u = p.active,
            f = It === l,
            d = s.sort,
            m = T || u,
            y,
            v = this,
            E = !1
        if (Jt) return
        function B(vt, Ne) {
            x(
                vt,
                v,
                G(
                    {
                        evt: t,
                        isOwner: f,
                        axis: y ? 'vertical' : 'horizontal',
                        revert: a,
                        dragRect: o,
                        targetRect: r,
                        canSort: d,
                        fromSortable: m,
                        target: i,
                        completed: I,
                        onMove: function (se, Re) {
                            return Nt(S, n, c, o, se, A(se), t, Re)
                        },
                        changed: L,
                    },
                    Ne,
                ),
            )
        }
        function z() {
            ;(B('dragOverAnimationCapture'),
                v.captureAnimationState(),
                v !== m && m.captureAnimationState())
        }
        function I(vt) {
            return (
                B('dragOverCompleted', { insertion: vt }),
                vt &&
                    (f ? u._hideClone() : u._showClone(v),
                    v !== m &&
                        (M(
                            c,
                            T ? T.options.ghostClass : u.options.ghostClass,
                            !1,
                        ),
                        M(c, s.ghostClass, !0)),
                    T !== v && v !== p.active
                        ? (T = v)
                        : v === p.active && T && (T = null),
                    m === v && (v._ignoreWhileAnimating = i),
                    v.animateAll(function () {
                        ;(B('dragOverAnimationComplete'),
                            (v._ignoreWhileAnimating = null))
                    }),
                    v !== m &&
                        (m.animateAll(), (m._ignoreWhileAnimating = null))),
                ((i === c && !c.animated) || (i === n && !i.animated)) &&
                    (lt = null),
                !s.dragoverBubble &&
                    !t.rootEl &&
                    i !== document &&
                    (c.parentNode[F]._isOutsideThisEl(t.target), !vt && rt(t)),
                !s.dragoverBubble && t.stopPropagation && t.stopPropagation(),
                (E = !0)
            )
        }
        function L() {
            ;((k = W(c)),
                (K = W(c, s.draggable)),
                P({
                    sortable: v,
                    name: 'change',
                    toEl: n,
                    newIndex: k,
                    newDraggableIndex: K,
                    originalEvent: t,
                }))
        }
        if (
            (t.preventDefault !== void 0 && t.cancelable && t.preventDefault(),
            (i = Y(i, s.draggable, n, !0)),
            B('dragOver'),
            p.eventCanceled)
        )
            return E
        if (
            c.contains(t.target) ||
            (i.animated && i.animatingX && i.animatingY) ||
            v._ignoreWhileAnimating === i
        )
            return I(!1)
        if (
            ((Lt = !1),
            u &&
                !s.disabled &&
                (f
                    ? d || (a = C !== S)
                    : T === this ||
                      ((this.lastPutMode = It.checkPull(this, u, c, t)) &&
                          l.checkPut(this, u, c, t))))
        ) {
            if (
                ((y = this._getDirection(t, i) === 'vertical'),
                (o = A(c)),
                B('dragOverValid'),
                p.eventCanceled)
            )
                return E
            if (a)
                return (
                    (C = S),
                    z(),
                    this._hideClone(),
                    B('revert'),
                    p.eventCanceled ||
                        (at ? S.insertBefore(c, at) : S.appendChild(c)),
                    I(!0)
                )
            var N = oe(n, s.draggable)
            if (!N || (tn(t, y, this) && !N.animated)) {
                if (N === c) return I(!1)
                if (
                    (N && n === t.target && (i = N),
                    i && (r = A(i)),
                    Nt(S, n, c, o, i, r, t, !!i) !== !1)
                )
                    return (
                        z(),
                        N && N.nextSibling
                            ? n.insertBefore(c, N.nextSibling)
                            : n.appendChild(c),
                        (C = n),
                        L(),
                        I(!0)
                    )
            } else if (N && Je(t, y, this)) {
                var et = ft(n, 0, s, !0)
                if (et === c) return I(!1)
                if (((i = et), (r = A(i)), Nt(S, n, c, o, i, r, t, !1) !== !1))
                    return (z(), n.insertBefore(c, et), (C = n), L(), I(!0))
            } else if (i.parentNode === n) {
                r = A(i)
                var H = 0,
                    nt,
                    ht = c.parentNode !== n,
                    R = !Ue(
                        (c.animated && c.toRect) || o,
                        (i.animated && i.toRect) || r,
                        y,
                    ),
                    pt = y ? 'top' : 'left',
                    V = de(i, 'top', 'top') || de(c, 'top', 'top'),
                    gt = V ? V.scrollTop : void 0
                ;(lt !== i &&
                    ((nt = r[pt]),
                    (Dt = !1),
                    (Pt = (!R && s.invertSwap) || ht)),
                    (H = en(
                        t,
                        i,
                        r,
                        y,
                        R ? 1 : s.swapThreshold,
                        s.invertedSwapThreshold == null
                            ? s.swapThreshold
                            : s.invertedSwapThreshold,
                        Pt,
                        lt === i,
                    )))
                var j
                if (H !== 0) {
                    var it = W(c)
                    do ((it -= H), (j = C.children[it]))
                    while (j && (h(j, 'display') === 'none' || j === g))
                }
                if (H === 0 || j === i) return I(!1)
                ;((lt = i), (Ct = H))
                var mt = i.nextElementSibling,
                    Z = !1
                Z = H === 1
                var Ot = Nt(S, n, c, o, i, r, t, Z)
                if (Ot !== !1)
                    return (
                        (Ot === 1 || Ot === -1) && (Z = Ot === 1),
                        (Jt = !0),
                        setTimeout(Ke, 30),
                        z(),
                        Z && !mt
                            ? n.appendChild(c)
                            : i.parentNode.insertBefore(c, Z ? mt : i),
                        V && Se(V, 0, gt - V.scrollTop),
                        (C = c.parentNode),
                        nt !== void 0 && !Pt && (kt = Math.abs(nt - A(i)[pt])),
                        L(),
                        I(!0)
                    )
            }
            if (n.contains(c)) return I(!1)
        }
        return !1
    },
    _ignoreWhileAnimating: null,
    _offMoveEvents: function () {
        ;(b(document, 'mousemove', this._onTouchMove),
            b(document, 'touchmove', this._onTouchMove),
            b(document, 'pointermove', this._onTouchMove),
            b(document, 'dragover', rt),
            b(document, 'mousemove', rt),
            b(document, 'touchmove', rt))
    },
    _offUpEvents: function () {
        var t = this.el.ownerDocument
        ;(b(t, 'mouseup', this._onDrop),
            b(t, 'touchend', this._onDrop),
            b(t, 'pointerup', this._onDrop),
            b(t, 'touchcancel', this._onDrop),
            b(document, 'selectstart', this))
    },
    _onDrop: function (t) {
        var n = this.el,
            i = this.options
        if (
            ((k = W(c)),
            (K = W(c, i.draggable)),
            x('drop', this, { evt: t }),
            (C = c && c.parentNode),
            (k = W(c)),
            (K = W(c, i.draggable)),
            p.eventCanceled)
        ) {
            this._nulling()
            return
        }
        ;((ut = !1),
            (Pt = !1),
            (Dt = !1),
            clearInterval(this._loopId),
            clearTimeout(this._dragStartTimer),
            te(this.cloneId),
            te(this._dragStartId),
            this.nativeDraggable &&
                (b(document, 'drop', this),
                b(n, 'dragstart', this._onDragStart)),
            this._offMoveEvents(),
            this._offUpEvents(),
            Et && h(document.body, 'user-select', ''),
            h(c, 'transform', ''),
            t &&
                (bt &&
                    (t.cancelable && t.preventDefault(),
                    !i.dropBubble && t.stopPropagation()),
                g && g.parentNode && g.parentNode.removeChild(g),
                (S === C || (T && T.lastPutMode !== 'clone')) &&
                    _ &&
                    _.parentNode &&
                    _.parentNode.removeChild(_),
                c &&
                    (this.nativeDraggable && b(c, 'dragend', this),
                    Qt(c),
                    (c.style['will-change'] = ''),
                    bt &&
                        !ut &&
                        M(
                            c,
                            T ? T.options.ghostClass : this.options.ghostClass,
                            !1,
                        ),
                    M(c, this.options.chosenClass, !1),
                    P({
                        sortable: this,
                        name: 'unchoose',
                        toEl: C,
                        newIndex: null,
                        newDraggableIndex: null,
                        originalEvent: t,
                    }),
                    S !== C
                        ? (k >= 0 &&
                              (P({
                                  rootEl: C,
                                  name: 'add',
                                  toEl: C,
                                  fromEl: S,
                                  originalEvent: t,
                              }),
                              P({
                                  sortable: this,
                                  name: 'remove',
                                  toEl: C,
                                  originalEvent: t,
                              }),
                              P({
                                  rootEl: C,
                                  name: 'sort',
                                  toEl: C,
                                  fromEl: S,
                                  originalEvent: t,
                              }),
                              P({
                                  sortable: this,
                                  name: 'sort',
                                  toEl: C,
                                  originalEvent: t,
                              })),
                          T && T.save())
                        : k !== ct &&
                          k >= 0 &&
                          (P({
                              sortable: this,
                              name: 'update',
                              toEl: C,
                              originalEvent: t,
                          }),
                          P({
                              sortable: this,
                              name: 'sort',
                              toEl: C,
                              originalEvent: t,
                          })),
                    p.active &&
                        ((k == null || k === -1) && ((k = ct), (K = _t)),
                        P({
                            sortable: this,
                            name: 'end',
                            toEl: C,
                            originalEvent: t,
                        }),
                        this.save()))),
            this._nulling())
    },
    _nulling: function () {
        ;(x('nulling', this),
            (S =
                c =
                C =
                g =
                at =
                _ =
                Mt =
                J =
                ot =
                X =
                bt =
                k =
                K =
                ct =
                _t =
                lt =
                Ct =
                T =
                It =
                p.dragged =
                p.ghost =
                p.clone =
                p.active =
                    null),
            Yt.forEach(function (t) {
                t.checked = !0
            }),
            (Yt.length = jt = qt = 0))
    },
    handleEvent: function (t) {
        switch (t.type) {
            case 'drop':
            case 'dragend':
                this._onDrop(t)
                break
            case 'dragenter':
            case 'dragover':
                c && (this._onDragOver(t), Ze(t))
                break
            case 'selectstart':
                t.preventDefault()
                break
        }
    },
    toArray: function () {
        for (
            var t = [],
                n,
                i = this.el.children,
                o = 0,
                r = i.length,
                a = this.options;
            o < r;
            o++
        )
            ((n = i[o]),
                Y(n, a.draggable, this.el, !1) &&
                    t.push(n.getAttribute(a.dataIdAttr) || on(n)))
        return t
    },
    sort: function (t, n) {
        var i = {},
            o = this.el
        ;(this.toArray().forEach(function (r, a) {
            var s = o.children[a]
            Y(s, this.options.draggable, o, !1) && (i[r] = s)
        }, this),
            n && this.captureAnimationState(),
            t.forEach(function (r) {
                i[r] && (o.removeChild(i[r]), o.appendChild(i[r]))
            }),
            n && this.animateAll())
    },
    save: function () {
        var t = this.options.store
        t && t.set && t.set(this)
    },
    closest: function (t, n) {
        return Y(t, n || this.options.draggable, this.el, !1)
    },
    option: function (t, n) {
        var i = this.options
        if (n === void 0) return i[t]
        var o = Tt.modifyOption(this, t, n)
        ;(typeof o < 'u' ? (i[t] = o) : (i[t] = n), t === 'group' && Te(i))
    },
    destroy: function () {
        x('destroy', this)
        var t = this.el
        ;((t[F] = null),
            b(t, 'mousedown', this._onTapStart),
            b(t, 'touchstart', this._onTapStart),
            b(t, 'pointerdown', this._onTapStart),
            this.nativeDraggable &&
                (b(t, 'dragover', this), b(t, 'dragenter', this)),
            Array.prototype.forEach.call(
                t.querySelectorAll('[draggable]'),
                function (n) {
                    n.removeAttribute('draggable')
                },
            ),
            this._onDrop(),
            this._disableDelayedDragEvents(),
            Xt.splice(Xt.indexOf(this.el), 1),
            (this.el = t = null))
    },
    _hideClone: function () {
        if (!J) {
            if ((x('hideClone', this), p.eventCanceled)) return
            ;(h(_, 'display', 'none'),
                this.options.removeCloneOnHide &&
                    _.parentNode &&
                    _.parentNode.removeChild(_),
                (J = !0))
        }
    },
    _showClone: function (t) {
        if (t.lastPutMode !== 'clone') {
            this._hideClone()
            return
        }
        if (J) {
            if ((x('showClone', this), p.eventCanceled)) return
            ;(c.parentNode == S && !this.options.group.revertClone
                ? S.insertBefore(_, c)
                : at
                  ? S.insertBefore(_, at)
                  : S.appendChild(_),
                this.options.group.revertClone && this.animate(c, _),
                h(_, 'display', ''),
                (J = !1))
        }
    },
}
function Ze(e) {
    ;(e.dataTransfer && (e.dataTransfer.dropEffect = 'move'),
        e.cancelable && e.preventDefault())
}
function Nt(e, t, n, i, o, r, a, s) {
    var l,
        u = e[F],
        f = u.options.onMove,
        d
    return (
        window.CustomEvent && !Q && !At
            ? (l = new CustomEvent('move', { bubbles: !0, cancelable: !0 }))
            : ((l = document.createEvent('Event')),
              l.initEvent('move', !0, !0)),
        (l.to = t),
        (l.from = e),
        (l.dragged = n),
        (l.draggedRect = i),
        (l.related = o || t),
        (l.relatedRect = r || A(t)),
        (l.willInsertAfter = s),
        (l.originalEvent = a),
        e.dispatchEvent(l),
        f && (d = f.call(u, l, a)),
        d
    )
}
function Qt(e) {
    e.draggable = !1
}
function Ke() {
    Jt = !1
}
function Je(e, t, n) {
    var i = A(ft(n.el, 0, n.options, !0)),
        o = Ce(n.el, n.options, g),
        r = 10
    return t
        ? e.clientX < o.left - r || (e.clientY < i.top && e.clientX < i.right)
        : e.clientY < o.top - r || (e.clientY < i.bottom && e.clientX < i.left)
}
function tn(e, t, n) {
    var i = A(oe(n.el, n.options.draggable)),
        o = Ce(n.el, n.options, g),
        r = 10
    return t
        ? e.clientX > o.right + r ||
              (e.clientY > i.bottom && e.clientX > i.left)
        : e.clientY > o.bottom + r || (e.clientX > i.right && e.clientY > i.top)
}
function en(e, t, n, i, o, r, a, s) {
    var l = i ? e.clientY : e.clientX,
        u = i ? n.height : n.width,
        f = i ? n.top : n.left,
        d = i ? n.bottom : n.right,
        m = !1
    if (!a) {
        if (s && kt < u * o) {
            if (
                (!Dt &&
                    (Ct === 1 ? l > f + (u * r) / 2 : l < d - (u * r) / 2) &&
                    (Dt = !0),
                Dt)
            )
                m = !0
            else if (Ct === 1 ? l < f + kt : l > d - kt) return -Ct
        } else if (l > f + (u * (1 - o)) / 2 && l < d - (u * (1 - o)) / 2)
            return nn(t)
    }
    return (
        (m = m || a),
        m && (l < f + (u * r) / 2 || l > d - (u * r) / 2)
            ? l > f + u / 2
                ? 1
                : -1
            : 0
    )
}
function nn(e) {
    return W(c) < W(e) ? 1 : -1
}
function on(e) {
    for (
        var t = e.tagName + e.className + e.src + e.href + e.textContent,
            n = t.length,
            i = 0;
        n--;
    )
        i += t.charCodeAt(n)
    return i.toString(36)
}
function rn(e) {
    Yt.length = 0
    for (var t = e.getElementsByTagName('input'), n = t.length; n--; ) {
        var i = t[n]
        i.checked && Yt.push(i)
    }
}
function Ft(e) {
    return setTimeout(e, 0)
}
function te(e) {
    return clearTimeout(e)
}
$t &&
    w(document, 'touchmove', function (e) {
        ;(p.active || ut) && e.cancelable && e.preventDefault()
    })
p.utils = {
    on: w,
    off: b,
    css: h,
    find: we,
    is: function (t, n) {
        return !!Y(t, n, t, !1)
    },
    extend: Xe,
    throttle: Ee,
    closest: Y,
    toggleClass: M,
    clone: _e,
    index: W,
    nextTick: Ft,
    cancelNextTick: te,
    detectDirection: Ae,
    getChild: ft,
}
p.get = function (e) {
    return e[F]
}
p.mount = function () {
    for (var e = arguments.length, t = new Array(e), n = 0; n < e; n++)
        t[n] = arguments[n]
    ;(t[0].constructor === Array && (t = t[0]),
        t.forEach(function (i) {
            if (!i.prototype || !i.prototype.constructor)
                throw 'Sortable: Mounted plugin must be a constructor function, not '.concat(
                    {}.toString.call(i),
                )
            ;(i.utils && (p.utils = G(G({}, p.utils), i.utils)), Tt.mount(i))
        }))
}
p.create = function (e, t) {
    return new p(e, t)
}
p.version = We
var D = [],
    yt,
    ee,
    ne = !1,
    Vt,
    Zt,
    Ht,
    wt
function an() {
    function e() {
        this.defaults = {
            scroll: !0,
            forceAutoScrollFallback: !1,
            scrollSensitivity: 30,
            scrollSpeed: 10,
            bubbleScroll: !0,
        }
        for (var t in this)
            t.charAt(0) === '_' &&
                typeof this[t] == 'function' &&
                (this[t] = this[t].bind(this))
    }
    return (
        (e.prototype = {
            dragStarted: function (n) {
                var i = n.originalEvent
                this.sortable.nativeDraggable
                    ? w(document, 'dragover', this._handleAutoScroll)
                    : this.options.supportPointer
                      ? w(
                            document,
                            'pointermove',
                            this._handleFallbackAutoScroll,
                        )
                      : i.touches
                        ? w(
                              document,
                              'touchmove',
                              this._handleFallbackAutoScroll,
                          )
                        : w(
                              document,
                              'mousemove',
                              this._handleFallbackAutoScroll,
                          )
            },
            dragOverCompleted: function (n) {
                var i = n.originalEvent
                !this.options.dragOverBubble &&
                    !i.rootEl &&
                    this._handleAutoScroll(i)
            },
            drop: function () {
                ;(this.sortable.nativeDraggable
                    ? b(document, 'dragover', this._handleAutoScroll)
                    : (b(
                          document,
                          'pointermove',
                          this._handleFallbackAutoScroll,
                      ),
                      b(document, 'touchmove', this._handleFallbackAutoScroll),
                      b(document, 'mousemove', this._handleFallbackAutoScroll)),
                    me(),
                    Wt(),
                    Ye())
            },
            nulling: function () {
                ;((Ht = ee = yt = ne = wt = Vt = Zt = null), (D.length = 0))
            },
            _handleFallbackAutoScroll: function (n) {
                this._handleAutoScroll(n, !0)
            },
            _handleAutoScroll: function (n, i) {
                var o = this,
                    r = (n.touches ? n.touches[0] : n).clientX,
                    a = (n.touches ? n.touches[0] : n).clientY,
                    s = document.elementFromPoint(r, a)
                if (
                    ((Ht = n),
                    i || this.options.forceAutoScrollFallback || At || Q || Et)
                ) {
                    Kt(n, this.options, s, i)
                    var l = tt(s, !0)
                    ne &&
                        (!wt || r !== Vt || a !== Zt) &&
                        (wt && me(),
                        (wt = setInterval(function () {
                            var u = tt(document.elementFromPoint(r, a), !0)
                            ;(u !== l && ((l = u), Wt()),
                                Kt(n, o.options, u, i))
                        }, 10)),
                        (Vt = r),
                        (Zt = a))
                } else {
                    if (!this.options.bubbleScroll || tt(s, !0) === $()) {
                        Wt()
                        return
                    }
                    Kt(n, this.options, tt(s, !1), !1)
                }
            },
        }),
        U(e, { pluginName: 'scroll', initializeByDefault: !0 })
    )
}
function Wt() {
    ;(D.forEach(function (e) {
        clearInterval(e.pid)
    }),
        (D = []))
}
function me() {
    clearInterval(wt)
}
var Kt = Ee(function (e, t, n, i) {
        if (t.scroll) {
            var o = (e.touches ? e.touches[0] : e).clientX,
                r = (e.touches ? e.touches[0] : e).clientY,
                a = t.scrollSensitivity,
                s = t.scrollSpeed,
                l = $(),
                u = !1,
                f
            ee !== n &&
                ((ee = n),
                Wt(),
                (yt = t.scroll),
                (f = t.scrollFn),
                yt === !0 && (yt = tt(n, !0)))
            var d = 0,
                m = yt
            do {
                var y = m,
                    v = A(y),
                    E = v.top,
                    B = v.bottom,
                    z = v.left,
                    I = v.right,
                    L = v.width,
                    N = v.height,
                    et = void 0,
                    H = void 0,
                    nt = y.scrollWidth,
                    ht = y.scrollHeight,
                    R = h(y),
                    pt = y.scrollLeft,
                    V = y.scrollTop
                y === l
                    ? ((et =
                          L < nt &&
                          (R.overflowX === 'auto' ||
                              R.overflowX === 'scroll' ||
                              R.overflowX === 'visible')),
                      (H =
                          N < ht &&
                          (R.overflowY === 'auto' ||
                              R.overflowY === 'scroll' ||
                              R.overflowY === 'visible')))
                    : ((et =
                          L < nt &&
                          (R.overflowX === 'auto' || R.overflowX === 'scroll')),
                      (H =
                          N < ht &&
                          (R.overflowY === 'auto' || R.overflowY === 'scroll')))
                var gt =
                        et &&
                        (Math.abs(I - o) <= a && pt + L < nt) -
                            (Math.abs(z - o) <= a && !!pt),
                    j =
                        H &&
                        (Math.abs(B - r) <= a && V + N < ht) -
                            (Math.abs(E - r) <= a && !!V)
                if (!D[d]) for (var it = 0; it <= d; it++) D[it] || (D[it] = {})
                ;((D[d].vx != gt || D[d].vy != j || D[d].el !== y) &&
                    ((D[d].el = y),
                    (D[d].vx = gt),
                    (D[d].vy = j),
                    clearInterval(D[d].pid),
                    (gt != 0 || j != 0) &&
                        ((u = !0),
                        (D[d].pid = setInterval(
                            function () {
                                i &&
                                    this.layer === 0 &&
                                    p.active._onTouchMove(Ht)
                                var mt = D[this.layer].vy
                                        ? D[this.layer].vy * s
                                        : 0,
                                    Z = D[this.layer].vx
                                        ? D[this.layer].vx * s
                                        : 0
                                ;(typeof f == 'function' &&
                                    f.call(
                                        p.dragged.parentNode[F],
                                        Z,
                                        mt,
                                        e,
                                        Ht,
                                        D[this.layer].el,
                                    ) !== 'continue') ||
                                    Se(D[this.layer].el, Z, mt)
                            }.bind({ layer: d }),
                            24,
                        )))),
                    d++)
            } while (t.bubbleScroll && m !== l && (m = tt(m, !1)))
            ne = u
        }
    }, 30),
    Pe = function (t) {
        var n = t.originalEvent,
            i = t.putSortable,
            o = t.dragEl,
            r = t.activeSortable,
            a = t.dispatchSortableEvent,
            s = t.hideGhostForTarget,
            l = t.unhideGhostForTarget
        if (n) {
            var u = i || r
            s()
            var f =
                    n.changedTouches && n.changedTouches.length
                        ? n.changedTouches[0]
                        : n,
                d = document.elementFromPoint(f.clientX, f.clientY)
            ;(l(),
                u &&
                    !u.el.contains(d) &&
                    (a('spill'), this.onSpill({ dragEl: o, putSortable: i })))
        }
    }
function re() {}
re.prototype = {
    startIndex: null,
    dragStart: function (t) {
        var n = t.oldDraggableIndex
        this.startIndex = n
    },
    onSpill: function (t) {
        var n = t.dragEl,
            i = t.putSortable
        ;(this.sortable.captureAnimationState(), i && i.captureAnimationState())
        var o = ft(this.sortable.el, this.startIndex, this.options)
        ;(o
            ? this.sortable.el.insertBefore(n, o)
            : this.sortable.el.appendChild(n),
            this.sortable.animateAll(),
            i && i.animateAll())
    },
    drop: Pe,
}
U(re, { pluginName: 'revertOnSpill' })
function ae() {}
ae.prototype = {
    onSpill: function (t) {
        var n = t.dragEl,
            i = t.putSortable,
            o = i || this.sortable
        ;(o.captureAnimationState(),
            n.parentNode && n.parentNode.removeChild(n),
            o.animateAll())
    },
    drop: Pe,
}
U(ae, { pluginName: 'removeOnSpill' })
p.mount(new an())
p.mount(ae, re)
var sn = p
function ie(e, t) {
    if (typeof ShadowRoot == 'function' && e instanceof ShadowRoot) {
        Array.from(e.children).forEach((o) => ie(o, t))
        return
    }
    let n = !1
    if ((t(e, () => (n = !0)), n)) return
    let i = e.firstElementChild
    for (; i; ) (ie(i, t, !1), (i = i.nextElementSibling))
}
function ln(e) {
    e.directive(
        'sort',
        (
            t,
            { value: n, modifiers: i, expression: o },
            { effect: r, evaluate: a, cleanup: s },
        ) => {
            if (n === 'config' || n === 'handle' || n === 'group') return
            if (n === 'key' || n === 'item') {
                if ([void 0, null, ''].includes(o)) return
                t._x_sort_key = a(o)
                return
            }
            let l = '[x-sort\\:handle],[wire\\:sort\\:handle]',
                u = {
                    hideGhost: !i.includes('ghost'),
                    useHandles:
                        !!t.querySelector(l) ||
                        Array.from(
                            t.querySelectorAll('template:not(svg template)'),
                        ).some((y) => y.content?.querySelector(l)),
                    group: hn(t, i),
                },
                f = un(o, a),
                d = cn(t, i, a),
                m = dn(t, d, u, (y, v) => {
                    f(y, v)
                })
            s(() => m.destroy())
        },
    )
}
function un(e, t) {
    return [void 0, null, ''].includes(e)
        ? () => {}
        : (n, i) => {
              t(e, {
                  scope: { $key: n, $item: n, $position: i },
                  params: [n, i],
              })
          }
}
function cn(e, t, n) {
    return e.hasAttribute('x-sort:config')
        ? n(e.getAttribute('x-sort:config'))
        : e.hasAttribute('wire:sort:config')
          ? n(e.getAttribute('wire:sort:config'))
          : {}
}
function dn(e, t, n, i) {
    let o,
        r = {
            animation: 150,
            handle: n.useHandles
                ? '[x-sort\\:handle],[wire\\:sort\\:handle]'
                : null,
            group: n.group,
            scroll: !0,
            forceAutoScrollFallback: !0,
            scrollSensitivity: 50,
            preventOnFilter: !1,
            filter(a) {
                return a.target.hasAttribute('x-sort:ignore') ||
                    a.target.hasAttribute('wire:sort:ignore') ||
                    a.target.closest('[x-sort\\:ignore]') ||
                    a.target.closest('[wire\\:sort\\:ignore]')
                    ? !0
                    : e.querySelector('[x-sort\\:item],[wire\\:sort\\:item]')
                      ? !a.target.closest(
                            '[x-sort\\:item],[wire\\:sort\\:item]',
                        )
                      : !1
            },
            onSort(a) {
                if (a.from !== a.to && a.to !== a.target) return
                let s
                ie(a.item, (u, f) => {
                    s === void 0 && u._x_sort_key && ((s = u._x_sort_key), f())
                })
                let l = a.newIndex
                ;(s !== void 0 || s !== null) && i(s, l)
            },
            onStart() {
                ;(document.body.classList.add('sorting'),
                    (o = document.querySelector('.sortable-ghost')),
                    n.hideGhost && o && (o.style.opacity = '0'))
            },
            onEnd() {
                ;(document.body.classList.remove('sorting'),
                    n.hideGhost && o && (o.style.opacity = '1'),
                    (o = void 0),
                    fn(e))
            },
        }
    return new sn(e, { ...r, ...t })
}
function fn(e) {
    let t = e.firstChild
    for (; t.nextSibling; ) {
        if (t.textContent.trim() === '[if ENDBLOCK]><![endif]') {
            e.append(t)
            break
        }
        t = t.nextSibling
    }
}
function hn(e, t) {
    return e.hasAttribute('x-sort:group')
        ? e.getAttribute('x-sort:group')
        : e.hasAttribute('wire:sort:group')
          ? e.getAttribute('wire:sort:group')
          : t.indexOf('group') !== -1
            ? t[t.indexOf('group') + 1]
            : null
}
var xe = ln
Alpine.plugin(xe)
function pn() {
    return {
        mode: 'view',
        selectedItem: null,
        activeBreakpoint: null,
        matchFrontendContainerLayout: !0,
        breakpointCanvasWidths: { tablet: '768px', mobile: '390px' },
        isReorderingResources: [],
        isLoading: !1,
        layoutMutationQueue: Promise.resolve(),
        isWidgetActionSuppressed: !1,
        widgetActionSuppressionTimeout: null,
        isContainersAllCollapsed: null,
        collapsedContainers: new Map(),
        collapsedWidgets: {},
        selectedRecords: this.$wire.$entangle('selectedRecords'),
        init() {
            ;((this.activeBreakpoint = this.normaliseBreakpoint(
                this.$el.dataset.activeBreakpoint ?? null,
            )),
                (this.matchFrontendContainerLayout =
                    this.$el.dataset.matchFrontendContainerLayout !== 'false'),
                this.$wire.on('layout-builder-reset', () => {
                    ;((this.mode = 'view'),
                        (this.selectedItem = null),
                        (this.isReorderingResources = []),
                        this.releaseWidgetActions())
                }),
                window.addEventListener('keydown', (n) => {
                    n.key === 'Escape' &&
                        ((this.mode = 'view'),
                        (this.selectedItem = null),
                        (this.isReorderingResources = []),
                        this.releaseWidgetActions())
                }))
            let e = (n) => {
                ;(this.collapsedContainers.set(
                    n.detail.id,
                    !!n.detail.isCollapsed,
                ),
                    this.updateIsAllContainersCollapsed())
            }
            ;(window.addEventListener('container-collapsed-register', e),
                window.addEventListener('container-collapsed-changed', e))
            let t = (n) => {
                let i = n.detail.containerKey
                ;(this.collapsedWidgets[i] || (this.collapsedWidgets[i] = {}),
                    (this.collapsedWidgets[i][n.detail.id] =
                        !!n.detail.isCollapsed))
            }
            ;(window.addEventListener('widget-collapsed-register', t),
                window.addEventListener('widget-collapsed-changed', t),
                this.$el.addEventListener(
                    'layout-builder-resize-container',
                    (n) => {
                        this.resizeLayoutContainer(
                            n.detail.containerKey,
                            n.detail.colspan,
                            n.detail.breakpoint,
                        )
                    },
                ),
                this.$el.addEventListener(
                    'layout-builder-suppress-widget-actions',
                    () => this.suppressWidgetActions(),
                ))
        },
        normaliseBreakpoint: function (e) {
            return e === '' ? null : e
        },
        setActiveBreakpointPreview: function (e) {
            let t = this.normaliseBreakpoint(e)
            ;((this.activeBreakpoint = t), this.$wire.setActiveBreakpoint(t))
        },
        isActiveBreakpoint: function (e) {
            return this.activeBreakpoint === this.normaliseBreakpoint(e)
        },
        activeBreakpointMaxCanvasWidth: function () {
            return this.breakpointCanvasWidths[this.activeBreakpoint] || '100%'
        },
        activeBreakpointMinCanvasWidth: function () {
            return this.breakpointCanvasWidths[this.activeBreakpoint] || '42rem'
        },
        shouldStackContainersForActiveBreakpoint: function () {
            return (
                this.matchFrontendContainerLayout &&
                ['mobile', 'tablet'].includes(this.activeBreakpoint)
            )
        },
        suppressWidgetActions: function () {
            ;(this.widgetActionSuppressionTimeout &&
                clearTimeout(this.widgetActionSuppressionTimeout),
                (this.isWidgetActionSuppressed = !0))
        },
        releaseWidgetActions: function () {
            this.isWidgetActionSuppressed &&
                (this.widgetActionSuppressionTimeout &&
                    clearTimeout(this.widgetActionSuppressionTimeout),
                (this.widgetActionSuppressionTimeout = setTimeout(() => {
                    ;((this.isWidgetActionSuppressed = !1),
                        (this.widgetActionSuppressionTimeout = null))
                }, 150)))
        },
        shouldSuppressWidgetActions: function () {
            return this.isWidgetActionSuppressed
        },
        queueLayoutMutation: function (e) {
            this.isLoading = !0
            let t = this.layoutMutationQueue.catch(() => {}).then(e)
            return (
                (this.layoutMutationQueue = t),
                t.finally(() => {
                    this.layoutMutationQueue === t && (this.isLoading = !1)
                })
            )
        },
        resizeLayoutContainer: function (e, t, n) {
            return this.queueLayoutMutation(() =>
                this.$wire.resizeContainer(e, t, n),
            )
        },
        reorderContainer: function (e, t) {
            return this.queueLayoutMutation(() =>
                this.$wire.reorderContainers(e, t),
            )
        },
        reorderWidget: function (e, t, n) {
            return this.queueLayoutMutation(() =>
                this.$wire.reorderWidgets(e, t, n),
            )
        },
        selectAllRecords: async function (e, t) {
            this.isLoading = !0
            try {
                await this.$wire.selectAllAssets(e, t)
            } finally {
                this.isLoading = !1
            }
        },
        deselectAllRecords: function (e, t) {
            this.selectedRecords[e][t] = []
        },
        setMode: function (e) {
            ;((this.mode = e),
                e === 'edit' && this.collapseAllWidgets(!0),
                e === 'view' && (this.selectedItem = null))
        },
        selectContainer: function (e) {
            ;((this.selectedItem = { type: 'container', containerKey: e }),
                (this.mode = 'details'))
        },
        selectWidget: function (e, t) {
            ;((this.selectedItem = {
                type: 'widget',
                containerKey: e,
                widgetIndex: t,
            }),
                (this.mode = 'details'))
        },
        isSelectedContainer: function (e) {
            return (
                this.selectedItem &&
                this.selectedItem.type === 'container' &&
                this.selectedItem.containerKey === e
            )
        },
        isSelectedWidget: function (e, t) {
            return (
                this.selectedItem &&
                this.selectedItem.type === 'widget' &&
                this.selectedItem.containerKey === e &&
                this.selectedItem.widgetIndex === t
            )
        },
        shouldShowInsertTargets: function () {
            return this.mode === 'edit'
        },
        collapseAll: function () {
            this.collapseAllComponents(!0)
        },
        expandAll: function () {
            this.collapseAllComponents(!1)
        },
        toggleAllComponents: function () {
            this.collapseAllComponents(this.isContainersAllCollapsed !== !0)
        },
        collapseAllComponents: function (e) {
            ;(this.collapseAllWidgets(e), this.collapseAllContainers(e))
        },
        collapseAllContainerWidgets: function (e, t) {
            ;(t || this.collapseContainer(e, t),
                this.$dispatch('collapse-widget', {
                    containerKey: e,
                    isCollapsed: t,
                }))
        },
        collapseContainer: function (e, t) {
            this.$dispatch('collapse-container', { id: e, isCollapsed: t })
        },
        collapseAllWidgets: function (e) {
            this.$dispatch('collapse-widget', { isCollapsed: e })
        },
        collapseAllContainers: function (e) {
            this.$dispatch('collapse-container', { isCollapsed: e })
        },
        updateIsAllContainersCollapsed: function () {
            let e = Array.from(this.collapsedContainers.values())
            e.length === 0
                ? (this.isContainersAllCollapsed = null)
                : e.every((t) => t === !0)
                  ? (this.isContainersAllCollapsed = !0)
                  : e.every((t) => t === !1)
                    ? (this.isContainersAllCollapsed = !1)
                    : (this.isContainersAllCollapsed = null)
        },
        isAllWidgetsCollapsed: function (e) {
            if (!this.collapsedWidgets[e]) return null
            let t = Object.values(this.collapsedWidgets[e])
            return t.length === 0
                ? null
                : t.every((n) => n === !0)
                  ? !0
                  : t.every((n) => n === !1)
                    ? !1
                    : null
        },
        toggleReorderingResources: function (e, t) {
            return (
                this.deselectAllRecords(e, t),
                this.isReorderingResources[e]
                    ? ((this.isReorderingResources[e][t] =
                          !this.isReorderingResources[e][t]),
                      this.isReorderingResources[e][t])
                    : ((this.isReorderingResources[e] = []),
                      (this.isReorderingResources[e][t] = !0),
                      this.isReorderingResources[e][t])
            )
        },
        isWidgetReorderingResources: function (e, t) {
            return this.isReorderingResources[e]
                ? this.isReorderingResources[e][t]
                : !1
        },
    }
}
export { pn as default }
/*! Bundled license information:

@alpinejs/sort/dist/module.esm.js:
  (*! Bundled license information:

  sortablejs/modular/sortable.esm.js:
    (**!
     * Sortable 1.15.2
     * @author	RubaXa   <trash@rubaxa.org>
     * @author	owenm    <owen23355@gmail.com>
     * @license MIT
     *)
  *)
*/
