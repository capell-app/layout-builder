# Media Attachment in Blog Package

Articles support media attachment through Laravel Media Library. This guide explains how media is handled and linked in the admin panel.

## How Media Works with Articles

The Blog package uses Spatie's Media Library to attach files to articles:

```php
$article->addMedia($filePath)
    ->toMediaCollection('gallery');
```

## Admin Media Library Integration

When media is attached to an article, the admin **Media Library** page shows a link to the article's edit page. This works through the Capell resource registration system.

### Resource Registration

Articles are registered as a **variant of the Page type**:

```php
// In BlogServiceProvider
CapellAdmin::registerResource(
    AdminResourceEnum::Page,
    class: ResourceEnum::Article->value,
    name: 'article',
);
```

This means:

- **Type**: `'Page'` (articles are a type of page)
- **Name**: `'article'` (the specific variant)

### Media Table Resolution

When the Media Library displays attached media, the `MediaTable` component resolves the resource:

1. Checks if a resource is registered for the model type (`'Article'`)
2. If not found, checks if the model is a Page instance
3. Looks for a Page resource with the lowercase model name (`'article'`)
4. Returns `null` if no resource found (owner displays as plain text)

This design allows article media to link back to the article editor without needing a separate `'Article'` resource registration.

## Testing Media Attachment

When testing media attachment to articles, ensure:

1. The article is created via factory
2. Media is added to the article
3. The media's `model_type` and `model_id` are correctly stored
4. The `model` relationship returns the article instance

Example test:

```php
it('media attached to article stores correct model type', function (): void {
    $article = Article::factory()->create();

    $article->addMedia($filePath)
        ->toMediaCollection();

    $media = $article->getMedia()->first();

    expect($media->model)
        ->toBeInstanceOf(Article::class);
});
```

## Troubleshooting

### Media Shows No Owner Link

If media attached to an article shows the owner as plain text instead of a clickable link:

1. Verify the article was created before attaching media
2. Check that the article's resource is properly registered in `BlogServiceProvider`
3. Confirm the article is a Page subtype (extends or morphs to Page)

### ResourceNotFoundException

If you see `ResourceNotFoundException: No resources registered for type: Page and name: article`:

1. Ensure `BlogServiceProvider` is registered and booted
2. Verify the blog package is installed and enabled
3. Check that `registerResources()` is being called in `registeringPackage()`
