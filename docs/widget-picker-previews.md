# Widget picker previews

The Add widget picker uses a widget's uploaded or generated preview first, then a matching preview asset from an installed, enabled package. It shows a labelled icon when no image is available. Layout and asset usage counts remain on the widget management screen rather than the picker cards.

Packages declare previews in `resources/previews/previews.json`, written by the generator described below. Each entry maps a widget identifier to a file inside the same directory:

```json
{
    "previews": [
        {
            "widget": "example.gallery",
            "path": "resources/previews/gallery.webp"
        }
    ]
}
```

`widget` must exactly match the saved widget key, resolved component identifier, or view identifier. Components inherited from the widget's loaded blueprint also match. Use the canonical component identifier to cover independently created widget records. The preview is resolved when the picker renders, so existing records do not need a migration or a setup rerun. The index is read from disk rather than registered as a manifest contribution, so it creates no runtime registration receipt.

Keep the image inside the owning package's `resources/previews/` directory (it is not published to the public web root). Use a real widget rendering with representative demo content, 480 × 300 WebP. The resolver accepts WebP, PNG and JPEG up to 512 KiB; missing files, non-images, SVG and paths escaping the preview directory fall back to the icon. It embeds validated image bytes in the admin card, so no asset publication or public file-serving route is needed. Hosts must permit `data:` images in the admin content security policy.

The card's image alternative text identifies the widget using the package translation. Fallback icons have a translated accessible label; decorative icon markup is hidden from assistive technology. Disabled widgets retain their disabled state and cannot be selected. The picker hydrates image and blueprint relationships before rendering.

Preview assets are admin metadata. They do not belong in public render data, stored widget content, public fragments or exports. This resolver does not change the public rendering pipeline.

Previews are produced from existing committed captures, never new ones. Each preview is a 480 × 300 WebP cropped from a reviewed capture and stored in the owning package, alongside the matching `capell.json` declaration.

Today the ten `widget-*` packages and the Foundation theme sections (navigation, hero, features, search, content listing, proof, form, call to action, footer) ship previews cropped from their public-output captures. The remaining registered widgets (Layout Builder built-ins, modern and kitchen-sink sets, the cookbook widget, Foundation pagination and contact split) are listed in `tests/Fixtures/widget-preview-pending.json` until a capture exists. The coverage contract test compares that list with the registered widgets, so a new widget without a preview, or a preview that lands without shrinking the list, fails the suite.
