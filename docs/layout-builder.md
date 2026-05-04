# Mosaic Layout Builder

This focused guide extends [Overview](overview.md) for the Mosaic package.

## Purpose

The layout builder connects Capell pages, layouts, sections, widgets, and assets.

Document it as a workflow because developers and editors need to understand placement, reuse, and cache impact.

## Workflow

1. Prepare page and layout records.
2. Add sections or widgets through the admin schema extenders.
3. Use layout plans when assisted placement is needed.
4. Save the container structure.
5. Clear or refresh layout cache when rendering changes need to appear immediately.

## Extension Points

- Register widget types through Mosaic service provider registration.
- Add widget configurators for package-owned widget data.
- Use `ApplyLayoutPlanAction` and `AddWidgetToLayoutContainerAction` for domain work.
- Use page and layout schema extenders for admin integration.

## Pitfalls

- Keep widget type registration and configurator registration together.
- Verify layout cache after changing widget placement.
- Check frontend output for empty widgets before publishing.
