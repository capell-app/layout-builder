# LayoutBuilder Layout Builder

This focused guide extends [Overview](overview.md) for the LayoutBuilder package.

## Purpose

The layout builder connects Capell pages, layouts, sections, widgets, and assets.

Document it as a workflow because developers and editors need to understand placement, reuse, and cache impact.

## Workflow

1. Prepare page and layout records.
2. Add sections or widgets through the admin schema extenders.
3. Use layout plans when assisted placement is needed.
4. Save the container structure.
5. Let LayoutBuilder queue a generated admin preview image for the saved structure.
6. Clear or refresh layout cache when rendering changes need to appear immediately.

## Generated Preview Images

Generated previews are covered in [Generated Layout Previews](generated-layout-previews.md). The short version: manual preview images win, generated PNGs are admin-only fallbacks, and stale generated files are deleted before the refresh job runs.

## Extension Points

- Register widget types through LayoutBuilder service provider registration.
- Add widget configurators for package-owned widget data.
- Use `ApplyLayoutPlanAction` and `AddWidgetToLayoutContainerAction` for domain work.
- Use page and layout schema extenders for admin integration.

## Pitfalls

- Keep widget type registration and configurator registration together.
- Verify layout cache after changing widget placement.
- Check frontend output for empty widgets before publishing.
- Do not rely on generated previews for frontend output; they are admin-only selection aids.
