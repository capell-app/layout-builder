# WordPress Importer

WordPress Importer registers a WXR XML reader with the Capell Migration Assistant.

The reader extracts WordPress posts and pages into a neutral import row shape that Migrator can map, preview, validate, and execute.

## Extracted Fields

- `post_id`, `post_type`, `post_title`, `post_name`, `link`, `post_content`, `post_excerpt`, `post_status`, `post_date`, and `parent_id`.
- Author login from the WXR `dc:creator` field.
- Category and tag metadata.
- Attachment URL references when present.

## Boundary

This package only owns WordPress WXR parsing and source registration. Migrator owns field mapping, previews, validation, execution, import sessions, notifications, and rollback reports.
