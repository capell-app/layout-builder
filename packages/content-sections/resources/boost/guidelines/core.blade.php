# Capell Content Sections Reusable content sections for Capell admin and
frontend surfaces. `capell-app/content-sections` owns the `Section` model,
section definitions, section configurators, and the frontend section component
keys. Read `vendor/capell-app/content-sections/README.md` before changing
section storage, admin forms, or rendering. Use `SectionDefinitionProvider` to
let other packages register new section types without editing this package
directly. Keep public rendering routed through neutral frontend component keys
where possible.
