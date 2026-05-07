import fs from 'node:fs/promises'
import { chromium } from 'playwright'

const [payloadPath, outputPath] = process.argv.slice(2)

if (!payloadPath || !outputPath) {
    console.error(
        'Usage: node generate-critical-css.mjs <payload-json> <output-css>',
    )

    throw new Error('Missing required critical CSS generator arguments.')
}

const payload = JSON.parse(await fs.readFile(payloadPath, 'utf8'))
const viewports =
    Array.isArray(payload.viewports) && payload.viewports.length > 0
        ? payload.viewports
        : [{ width: 1440, height: 900 }]
const eligibleStylesheetPaths = Array.isArray(payload.eligible_stylesheet_paths)
    ? payload.eligible_stylesheet_paths.filter(
          (path) => typeof path === 'string' && path.length > 0,
      )
    : []

const browser = await chromium.launch()
const criticalRules = new Set()

try {
    for (const viewport of viewports) {
        const page = await browser.newPage({ viewport })
        await page.goto(payload.url, { waitUntil: 'networkidle' })

        const rules = await page.evaluate((stylesheetPaths) => {
            const collectedRules = []
            const viewportHeight =
                window.innerHeight || document.documentElement.clientHeight
            const viewportWidth =
                window.innerWidth || document.documentElement.clientWidth

            const normalizePath = (path) => {
                try {
                    return new URL(path, window.location.href).pathname
                } catch {
                    return path
                }
            }

            const eligiblePaths = stylesheetPaths.map(normalizePath)

            const stylesheetIsEligible = (stylesheet) => {
                if (eligiblePaths.length === 0) {
                    return true
                }

                if (!stylesheet.href) {
                    return true
                }

                const stylesheetPath = normalizePath(stylesheet.href)

                return eligiblePaths.some(
                    (path) =>
                        stylesheetPath === path ||
                        stylesheetPath.endsWith(path),
                )
            }

            const elementIsAboveFold = (element) => {
                const rectangle = element.getBoundingClientRect()

                return (
                    rectangle.width > 0 &&
                    rectangle.height > 0 &&
                    rectangle.top < viewportHeight &&
                    rectangle.bottom > 0 &&
                    rectangle.left < viewportWidth &&
                    rectangle.right > 0
                )
            }

            const selectorIsAboveFold = (selectorText) => {
                for (const selector of selectorText.split(',')) {
                    const normalizedSelector = selector.trim()

                    if (
                        normalizedSelector === '' ||
                        normalizedSelector.includes('::')
                    ) {
                        continue
                    }

                    try {
                        if (
                            [':root', 'html', 'body'].includes(
                                normalizedSelector,
                            )
                        ) {
                            return true
                        }

                        for (const element of Array.from(
                            document.querySelectorAll(normalizedSelector),
                        )) {
                            if (elementIsAboveFold(element)) {
                                return true
                            }
                        }
                    } catch {
                        continue
                    }
                }

                return false
            }

            const collectRule = (rule) => {
                if (rule instanceof CSSStyleRule) {
                    if (selectorIsAboveFold(rule.selectorText)) {
                        collectedRules.push(rule.cssText)
                    }

                    return
                }

                if (
                    rule instanceof CSSFontFaceRule ||
                    rule instanceof CSSKeyframesRule
                ) {
                    collectedRules.push(rule.cssText)

                    return
                }

                if (rule instanceof CSSMediaRule) {
                    if (!window.matchMedia(rule.conditionText).matches) {
                        return
                    }

                    const nestedRules = []

                    for (const nestedRule of Array.from(rule.cssRules ?? [])) {
                        if (
                            nestedRule instanceof CSSStyleRule &&
                            selectorIsAboveFold(nestedRule.selectorText)
                        ) {
                            nestedRules.push(nestedRule.cssText)
                        }
                    }

                    if (nestedRules.length > 0) {
                        collectedRules.push(
                            `@media ${rule.conditionText} { ${nestedRules.join(' ')} }`,
                        )
                    }

                    return
                }

                if (rule instanceof CSSSupportsRule) {
                    const nestedRules = []

                    for (const nestedRule of Array.from(rule.cssRules ?? [])) {
                        if (
                            nestedRule instanceof CSSStyleRule &&
                            selectorIsAboveFold(nestedRule.selectorText)
                        ) {
                            nestedRules.push(nestedRule.cssText)
                        }
                    }

                    if (nestedRules.length > 0) {
                        collectedRules.push(
                            `@supports ${rule.conditionText} { ${nestedRules.join(' ')} }`,
                        )
                    }
                }
            }

            for (const stylesheet of Array.from(document.styleSheets)) {
                if (!stylesheetIsEligible(stylesheet)) {
                    continue
                }

                try {
                    for (const rule of Array.from(stylesheet.cssRules ?? [])) {
                        collectRule(rule)
                    }
                } catch {
                    // Cross-origin stylesheets cannot be inspected by the browser.
                }
            }

            return collectedRules
        }, eligibleStylesheetPaths)

        for (const rule of rules) {
            criticalRules.add(rule)
        }

        await page.close()
    }
} finally {
    await browser.close()
}

await fs.mkdir(new URL('.', `file://${outputPath}`).pathname, {
    recursive: true,
})
await fs.writeFile(outputPath, Array.from(criticalRules).join('\n') + '\n')
