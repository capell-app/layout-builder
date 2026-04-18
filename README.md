# Capell Packages

Optional add-on packages for [Capell CMS](https://github.com/capell-app/capell). Each package is independently installable and extends Capell through its published extension points — no core modification required.

[![Documentation](https://img.shields.io/badge/docs-docs.capell.app-blue?style=flat-square)](https://docs.capell.app/packages/)

---

## Packages

| Package                 | What it adds                                                       |
| ----------------------- | ------------------------------------------------------------------ |
| [Mosaic](#mosaic)       | Visual layout builder, widgets, and reusable content items         |
| [Blog](#blog)           | Article page type, tags, archives, sitemap, Livewire listing pages |
| [Address](#address)     | Country and address management, attached to sites                  |
| [Assistant](#assistant) | OpenAI-powered content drafting in the admin panel                 |

Full documentation for each package is at **[docs.capell.app/packages/](https://docs.capell.app/packages/)**.

---

## Requirements

All packages require:

- PHP 8.2+
- Laravel 10+
- [Capell Core, Admin, and Frontend](https://github.com/capell-app/capell) installed

Individual package prerequisites are noted below.

---

## Mosaic

Visual layout builder with widget management. Provides the drag-and-drop page builder, Filament resources for Contents and Widgets, and the frontend rendering pipeline.

→ [Package README](packages/mosaic/README.md) · [docs.capell.app/packages/mosaic](https://docs.capell.app/packages/mosaic/)

**Install:**

```bash
php artisan capell:mosaic-install
```

---

## Blog

Article page type with tags, archives, and Livewire listing pages. Integrates with the sitemap generator and the Mosaic package (optional widgets).

→ [Package README](packages/blog/README.md) · [docs.capell.app/packages/blog](https://docs.capell.app/packages/blog/)

**Install:**

```bash
php artisan capell:blog-install
php artisan capell:blog-create-pages {site-id}
```

---

## Address

Country and address models with Filament resources. Attaches address and country relationships to `Site` at runtime via `Site::resolveRelationUsing(...)` — no schema changes to the core `sites` table.

→ [Package README](packages/address/README.md) · [docs.capell.app/packages/address](https://docs.capell.app/packages/address/)

**Install:**

```bash
php artisan capell:address-install
```

---

## Assistant

OpenAI-powered title, meta description, and long-form content drafting from the admin panel. Includes rate limiting, an audit log, and a usage widget.

→ [Package README](packages/assistant/README.md) · [docs.capell.app/packages/assistant](https://docs.capell.app/packages/assistant/)

**Install:**

```bash
php artisan capell:assistant-install
```

---

## Core Documentation

This repo contains only the add-on packages. For Capell core documentation see:

- **[docs.capell.app](https://docs.capell.app)** — full hosted documentation
- **[capell-app/capell](https://github.com/capell-app/capell)** — core repository (Core, Admin, Frontend packages)
