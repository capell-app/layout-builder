# Page API

Capell API adds a public, read-only page resolver endpoint:

```http
GET /api/capell/pages/resolve?url=/terms
```

The route name is `capell-api.pages.resolve`.

## Default Response

By default the endpoint returns only the URL, title, and content from the published page translation.

```json
{
    "data": {
        "url": "/terms",
        "title": "Terms and Conditions",
        "content": "<p>Terms content...</p>"
    }
}
```

Missing site, language, or page resolution returns:

```json
{
    "message": "Page not found"
}
```

with HTTP `404`.

## Query Parameters

| Parameter    | Example              | Notes                                                                                                      |
| ------------ | -------------------- | ---------------------------------------------------------------------------------------------------------- |
| `url`        | `/terms`             | Page URL to resolve. Blank or omitted resolves `/`.                                                        |
| `site`       | `1`                  | Optional site id for signed integration URLs only. Unsigned requests resolve from the current host/domain. |
| `language`   | `en`                 | Optional language id, code, or locale for signed integration URLs only.                                    |
| `fields`     | `title,content,meta` | Optional projection. Defaults to `url,title,content`. Unknown fields are ignored.                          |
| `include`    | `layout`             | Optional includes. Supports `layout` and `layout.html`.                                                    |
| `containers` | `main,sidebar`       | Optional layout container filter. Empty, `all`, or `*` returns all containers.                             |

Supported fields are:

- `url`
- `title`
- `content`
- `meta`

## Site Resolution

Unsigned requests resolve the site from the request host/domain using Capell site domain records. This keeps the public API aligned with frontend multi-site boundaries.

Explicit `site` or `language` selection is only accepted on a signed route URL. The signature fixes the selected `site` and `language`; consumers may still vary `url`, `fields`, `include`, and `containers`.

## Layout Builder Content

Layout output is off by default. Request it explicitly:

```http
GET /api/capell/pages/resolve?url=/terms&include=layout
```

This adds `data.layout` with layout key, containers, and public widget data. Layout and container `meta` are present as empty objects by default; package resolvers should expose only fields that are intentionally public.

```json
{
    "data": {
        "url": "/terms",
        "title": "Terms and Conditions",
        "content": "<p>Terms content...</p>",
        "layout": {
            "key": "default",
            "meta": {},
            "containers": [
                {
                    "key": "main",
                    "meta": {},
                    "widgets": [
                        {
                            "key": "page-content",
                            "occurrence": 1,
                            "type": "content",
                            "data": {
                                "title": "Page content",
                                "content": "<p>...</p>"
                            }
                        }
                    ]
                }
            ]
        }
    }
}
```

Limit output to selected containers:

```http
GET /api/capell/pages/resolve?url=/terms&include=layout&containers=main
```

Use `include=layout.html` when a registered public widget payload resolver supports rendered HTML:

```http
GET /api/capell/pages/resolve?url=/terms&include=layout.html
```

HTML remains off unless `layout.html` is requested.

## Sanitization

The API sanitizes returned HTML strings with Symfony's HTML sanitizer using safe elements, relative links, and relative media. This removes unsafe executable markup such as scripts, inline event handlers, `javascript:` URL attributes, and `srcdoc` payloads while keeping normal author HTML.

Sanitization applies recursively to selected page fields, page meta, layout meta, container meta, widget data, and widget HTML.

This is a response safety boundary, not an editor validation layer. Do not use the API as a sanitizer for saving content.

## Core Extension Points

The endpoint delegates page resolution to `Capell\Core\Actions\ResolvePublicPageByUrlAction`.

Layout output is built by `Capell\Core\LayoutBuilder\Actions\BuildPublicLayoutGraphAction`.

To customize widget API payloads, bind `Capell\Core\LayoutBuilder\Contracts\PublicWidgetPayloadResolver` to your own implementation. The default resolver returns widget title and content only, and returns `null` for HTML.
