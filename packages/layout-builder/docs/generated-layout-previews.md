# Generated Layout Previews

LayoutBuilder generates admin-only PNG preview images for saved layout container structures. These previews help editors recognize layouts in the layout table and page layout select without relying on hand-uploaded thumbnails.

## Behaviour

- Preview generation is queued after saved layout container/widget changes.
- The old generated preview file is deleted immediately when the saved structure changes.
- While the job is pending, the layout has no generated preview image, so admin surfaces cannot show stale generated output.
- Manual layout preview images always take precedence and are never deleted by generated preview refreshes.
- Generated previews are admin selection aids only. They are not rendered into public frontend HTML, cached HTML, static exports, or theme output.

## Rendered Image

- Output format: PNG.
- Canvas: `1200x1200`.
- Structure: 12-column grid based on saved container `meta.colspan` values.
- Content: container keys, widget keys, and a compact icon fallback derived from widget admin icon metadata.
- Colours: stable per layout, generated from layout/block identity, spaced to avoid near-identical neighbouring hues, with adaptive text contrast.
- Overflow: tall layouts are clipped and marked with a `+N more` footer.

## Invalidation

Preview signatures are based on normalized layout containers plus referenced widget display data. A preview is invalidated when:

- saved layout containers change;
- widgets are added, removed, reordered, or moved between containers;
- container width metadata changes;
- a referenced widget key, name, admin icon, or type changes;
- a referenced widget type name or admin icon changes.

Invalidation updates the layout before dispatching the queue job:

1. Delete the old generated preview PNG from the public disk.
2. Clear `generated_preview_image`.
3. Store the new `generated_preview_signature`.
4. Set `generated_preview_status` to `pending`.
5. Clear `generated_preview_error`.
6. Dispatch preview generation for the layout/signature pair.

The queued job checks the stored signature before writing output. Stale jobs exit without writing if a newer signature has already replaced their signature.

## Metadata

Generated preview state is stored in `layouts.admin` to avoid another migration:

| Key                           | Meaning                                           |
| ----------------------------- | ------------------------------------------------- |
| `generated_preview_image`     | Public disk path for the generated PNG.           |
| `generated_preview_signature` | Hash of normalized container/widget display data. |
| `generated_preview_status`    | `pending`, `ready`, or `failed`.                  |
| `generated_preview_error`     | Short failure message when generation fails.      |

Status values are represented by `LayoutPreviewStatusEnum`.

## Display Precedence

Admin preview consumers should resolve layout images in this order:

1. manual media preview image;
2. manual `admin.image` path;
3. generated preview PNG;
4. no image.

LayoutBuilder's layout table and Capell Admin's page layout select both follow this order.

## Failure Handling

If rendering fails, the job leaves `generated_preview_image` empty, sets `generated_preview_status` to `failed`, stores a short error message, and logs the exception context. It must not restore or keep an outdated generated image.

## Verification

Use focused tests when changing this feature:

- `vendor/bin/pest packages/layout-builder/tests/Integration/Actions/LayoutPreviews/LayoutPreviewImageActionsTest.php --configuration=phpunit.xml`
- `vendor/bin/pest packages/layout-builder/tests/Feature/Livewire/LayoutBuilder/LayoutBuilderTest.php --configuration=phpunit.xml`
- `vendor/bin/pest packages/admin/tests/Feature/Filament/Components/LayoutSelectTest.php --configuration=phpunit.xml`
