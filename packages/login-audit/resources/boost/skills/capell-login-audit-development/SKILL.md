---
name: capell-login-audit-development
description: Use when editing Capell Login Audit events, middleware, widgets, or settings.
---

# Capell Login Audit

Login, failed login, logout, and admin/user activity metadata for Capell users.

## Look

- `packages/login-audit/src`
- `packages/login-audit/docs`
- `packages/login-audit/README.md`

## Rules

- Treat auth records as audit data; avoid destructive defaults.
- Middleware must be lightweight and privacy-conscious.
- Widgets should summarize logs without leaking sensitive metadata.
- Run `vendor/bin/pest packages/login-audit/tests`.
