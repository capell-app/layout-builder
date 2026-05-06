# LayoutBuilder Widget Reference

This focused guide extends [Overview](overview.md) for the LayoutBuilder package.

## Purpose

LayoutBuilder owns reusable widgets, sections, widget assets, and layout container placement.

Use this guide for widget families and editor-facing behaviour that would crowd the package README.

## Widget Families

- Hero and hero banner widgets.
- Card grid, feature list, FAQ, image gallery, pricing table, process steps, stats, team members, and testimonial widgets.
- Page content, navigation, result, system, and asset-backed widgets.
- Campaign and Blog packages can register LayoutBuilder-aware widget configurators.

## Admin Workflow

1. Open the Widgets resource.
2. Create or edit a widget with a registered widget type.
3. Attach assets where the configurator supports them.
4. Place the widget in a layout container or section.
5. Verify frontend rendering and layout cache behaviour.

## Extension Points

- Register widget types in LayoutBuilder service provider registration.
- Add widget configurators for package-owned widget data.
- Keep domain work in actions such as `MakeWidgetAction`, `ApplyLayoutPlanAction`, and `AddWidgetToLayoutContainerAction`.
- Keep rendering in Blade or Livewire components rather than Filament resources.

## Screenshot Requirements

- Widget index.
- Widget create/edit form.
- Asset relation manager.
- Layout builder placement.
- Frontend output for each modern widget family.
