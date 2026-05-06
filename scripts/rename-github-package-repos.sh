#!/usr/bin/env bash

set -euo pipefail

# Requires GitHub CLI auth with repository administration permission.
# Usage: bash scripts/rename-github-package-repos.sh

RENAMES=$(cat <<'RENAMES'
mosaic layout-builder
workspaces publishing-studio
migrator migration-assistant
analytics insights
google-analytics ga4-reports
developer-tools diagnostics
content-blocks block-library
campaigns campaign-studio
forms form-builder
site-search search
seo-tools seo-suite
authentication-log login-audit
password-security password-policy
html-minify html-optimizer
default-theme foundation-theme
example-sites starter-sites
mcp agent-bridge
assistant ai-orchestrator
media-curator media-library
media-assistant media-ai
filament-peek admin-preview
reports dashboard-reports
RENAMES
)

echo "${RENAMES}" | while read -r old_name new_name; do
    if [ -z "${old_name}" ] || [ -z "${new_name}" ]; then
        continue
    fi

    if gh repo view "capell-app/${new_name}" >/dev/null 2>&1; then
        echo "Skipping capell-app/${old_name}; capell-app/${new_name} already exists"
        continue
    fi

    if ! gh repo view "capell-app/${old_name}" >/dev/null 2>&1; then
        echo "Skipping capell-app/${old_name}; repository not found"
        continue
    fi

    echo "Renaming capell-app/${old_name} -> capell-app/${new_name}"
    gh repo rename "${new_name}" --repo "capell-app/${old_name}" --yes
done
