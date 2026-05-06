const fs = require('fs')
const path = require('path')

const root = process.cwd()
const manifestPath = path.join(root, 'docs/package-screenshot-manifest.json')
const packageDirs = fs
    .readdirSync(path.join(root, 'packages'), { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => entry.name)

const failures = []

const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'))
const manifestPackages = new Set(
    (manifest.entries ?? []).map((entry) => entry.package),
)

for (const packageName of packageDirs) {
    const screenshotsPath = path.join(
        root,
        'packages',
        packageName,
        'docs/screenshots.json',
    )

    if (!fs.existsSync(screenshotsPath)) {
        continue
    }

    try {
        const packageManifest = JSON.parse(
            fs.readFileSync(screenshotsPath, 'utf8'),
        )

        if (packageManifest.package !== packageName) {
            failures.push(
                `${screenshotsPath}: package key "${packageManifest.package}" does not match directory "${packageName}"`,
            )
        }

        if (!manifestPackages.has(packageName)) {
            failures.push(
                `${screenshotsPath}: package is missing from docs/package-screenshot-manifest.json`,
            )
        }

        if (
            packageManifest.composerRequires !== undefined &&
            !Array.isArray(packageManifest.composerRequires)
        ) {
            failures.push(
                `${screenshotsPath}: composerRequires must be an array when present`,
            )
        }

        for (const requirement of packageManifest.composerRequires ?? []) {
            if (typeof requirement !== 'string' || !requirement.includes('/')) {
                failures.push(
                    `${screenshotsPath}: composerRequires entries must be full Composer package names`,
                )
            }
        }

        if (
            packageManifest.composerRequires !== undefined &&
            !packageManifest.composerRequires.includes(
                packageManifest.composerName,
            )
        ) {
            failures.push(
                `${screenshotsPath}: composerRequires must include composerName`,
            )
        }

        if (
            packageManifest.browserTests !== undefined &&
            !Array.isArray(packageManifest.browserTests)
        ) {
            failures.push(
                `${screenshotsPath}: browserTests must be an array when present`,
            )
        }

        for (const browserTest of packageManifest.browserTests ?? []) {
            if (typeof browserTest.id !== 'string' || browserTest.id === '') {
                failures.push(
                    `${screenshotsPath}: browserTests entries must have an id`,
                )
            }

            if (
                !Array.isArray(browserTest.assertions) ||
                browserTest.assertions.length === 0
            ) {
                failures.push(
                    `${screenshotsPath}: browserTests entries must declare assertions`,
                )
            }
        }
    } catch (error) {
        failures.push(`${screenshotsPath}: ${error.message}`)
    }
}

for (const packageName of manifestPackages) {
    const screenshotsPath = path.join(
        root,
        'packages',
        packageName,
        'docs/screenshots.json',
    )

    if (!fs.existsSync(screenshotsPath)) {
        failures.push(
            `docs/package-screenshot-manifest.json references "${packageName}" but ${screenshotsPath} does not exist`,
        )
    }
}

if (failures.length > 0) {
    throw new Error(
        `Screenshot manifest validation failed:\n${failures.map((failure) => `- ${failure}`).join('\n')}`,
    )
}

console.log('Screenshot manifests are in sync.')
