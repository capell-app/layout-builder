# Package Boost Skills Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace generic package Boost skills with very short, package-specific AI guidance only for packages where a skill adds real signal.

**Architecture:** Every package keeps `resources/boost/guidelines/core.blade.php`. Only allowlisted packages keep `resources/boost/skills/*/SKILL.md`; thin packages rely on guidelines only. The resource test enforces guidelines for every package and skills only for the allowlist.

**Tech Stack:** Laravel Boost package resources, Markdown skill files, Pest resource coverage test.

---

## File Structure

- Modify: selected `packages/*/resources/boost/skills/*/SKILL.md`
- Delete: pointless `packages/*/resources/boost/skills/*/SKILL.md`
- Leave unchanged: `packages/*/resources/boost/guidelines/core.blade.php`
- Test: `tests/Packages/BoostResourcesTest.php`

## Task 1: Skill Policy Test

**Files:**

- Modify: `tests/Packages/BoostResourcesTest.php`

- [ ] **Step 1: Require guidelines for all packages and skills for the allowlist**

Use this policy:

```php
$packagesWithSkills = [
    'address',
    'insights',
    'ai-orchestrator',
    'login-audit',
    'migration-assistant',
    'blog',
    'campaign-studio',
    'block-library',
    'foundation-theme',
    'deployments',
    'diagnostics',
    'form-builder',
    'agent-bridge',
    'media-library',
    'layout-builder',
    'navigation',
    'redirects',
    'seo-suite',
    'search',
    'tags',
    'theme-studio-admin',
    'theme-studio-core',
    'publishing-studio',
];
```

- [ ] **Step 2: Run the policy test before cleanup**

Run:

```bash
vendor/bin/pest tests/Packages/BoostResourcesTest.php
```

Expected: FAIL while pointless skills still exist.

## Task 2: Rewrite Allowlisted Skills

**Files:**

- Modify: `packages/address/resources/boost/skills/capell-address-development/SKILL.md`
- Modify: `packages/insights/resources/boost/skills/capell-insights-development/SKILL.md`
- Modify: `packages/ai-orchestrator/resources/boost/skills/capell-ai-orchestrator-development/SKILL.md`
- Modify: `packages/login-audit/resources/boost/skills/capell-login-audit-development/SKILL.md`
- Modify: `packages/migration-assistant/resources/boost/skills/capell-migration-assistant-development/SKILL.md`
- Modify: `packages/blog/resources/boost/skills/blog/SKILL.md`
- Modify: `packages/campaign-studio/resources/boost/skills/capell-campaign-studio-development/SKILL.md`
- Modify: `packages/block-library/resources/boost/skills/capell-block-library-development/SKILL.md`
- Modify: `packages/foundation-theme/resources/boost/skills/capell-foundation-theme-development/SKILL.md`
- Modify: `packages/deployments/resources/boost/skills/capell-deployments-development/SKILL.md`
- Modify: `packages/diagnostics/resources/boost/skills/capell-diagnostics-development/SKILL.md`
- Modify: `packages/form-builder/resources/boost/skills/capell-form-builder-development/SKILL.md`
- Modify: `packages/agent-bridge/resources/boost/skills/capell-agent-bridge-development/SKILL.md`
- Modify: `packages/media-library/resources/boost/skills/capell-media-library-development/SKILL.md`
- Modify: `packages/layout-builder/resources/boost/skills/layout-builder/SKILL.md`
- Modify: `packages/navigation/resources/boost/skills/capell-navigation-development/SKILL.md`
- Modify: `packages/redirects/resources/boost/skills/capell-redirects-development/SKILL.md`
- Modify: `packages/seo-suite/resources/boost/skills/capell-seo-suite-development/SKILL.md`
- Modify: `packages/search/resources/boost/skills/capell-search-development/SKILL.md`
- Modify: `packages/tags/resources/boost/skills/capell-tags-development/SKILL.md`
- Modify: `packages/theme-studio-admin/resources/boost/skills/capell-theme-studio-admin-development/SKILL.md`
- Modify: `packages/theme-studio-core/resources/boost/skills/capell-theme-studio-core-development/SKILL.md`
- Modify: `packages/publishing-studio/resources/boost/skills/publishing-studio/SKILL.md`

- [ ] **Step 1: Rewrite the skills**

Use compact package-specific content with front matter, one purpose sentence, `Look`, and 2-4 `Rules`.

- [ ] **Step 2: Review token size**

Run:

```bash
find packages -path '*/resources/boost/skills/*/SKILL.md' -type f -print0 | xargs -0 wc -w
```

Expected: each shipped skill is short enough to scan quickly.

## Task 3: Remove Pointless Skills

**Files:**

- Delete: `packages/admin-preview/resources/boost/skills/capell-admin-preview-development/SKILL.md`
- Delete: `packages/html-optimizer/resources/boost/skills/capell-html-optimizer-development/SKILL.md`
- Delete: `packages/theme-agency/resources/boost/skills/capell-theme-agency-development/SKILL.md`
- Delete: `packages/theme-corporate/resources/boost/skills/capell-theme-corporate-development/SKILL.md`
- Delete: `packages/theme-saas/resources/boost/skills/capell-theme-saas-development/SKILL.md`
- Delete: `packages/theme-studio/resources/boost/skills/capell-theme-studio-development/SKILL.md`
- Delete: `packages/toolbar/resources/boost/skills/capell-frontend-toolbar-development/SKILL.md`

- [ ] **Step 1: Delete the skill files**

Remove the listed files so Boost has no pointless trigger for thin packages.

- [ ] **Step 2: Keep guidelines**

Run:

```bash
test -f packages/html-optimizer/resources/boost/guidelines/core.blade.php
```

Expected: exit code 0.

## Task 4: Verify Boost Resources

**Files:**

- Test: `tests/Packages/BoostResourcesTest.php`

- [ ] **Step 1: Run the Boost resource test**

Run:

```bash
vendor/bin/pest tests/Packages/BoostResourcesTest.php
```

Expected: PASS.

- [ ] **Step 2: Check the diff**

Run:

```bash
git diff -- packages/*/resources/boost/skills
```

Expected: only package skill Markdown files changed; guidelines remain untouched.
