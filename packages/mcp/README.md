# Capell MCP

Capell MCP provides MCP servers for Capell CMS:

- **Capell Site MCP** exposes an authenticated, scoped, auditable MCP surface inside an installed Capell site.
- **Capell Knowledge MCP** can expose read-only Capell documentation, package catalog data, conventions, and package recommendations when a host app explicitly enables it.

The site server is designed around registered capabilities. Packages opt in by registering capability providers; MCP clients can only see and run capabilities that are installed, enabled, scoped to their token, and allowed by the mapped Capell user.

## Installation

```bash
composer require capell-app/mcp
php artisan vendor:publish --tag=capell-mcp-config
php artisan migrate
```

The main Capell app already requires this package and installs it from the
Capell package library at `packages/mcp`.

Register the web endpoints from the package service provider:

- `/mcp/capell`

The default route is the authenticated site server. Discovery and knowledge
routes are disabled by default so a host application does not accidentally
publish an unauthenticated MCP surface.

The site endpoint requires a bearer token created by your application and stored hashed in `capell_mcp_tokens`.

## MCP Client Setup

Authenticated Capell app MCP:

```text
https://capell.app/mcp/capell
```

Claude Code:

```bash
claude mcp add --transport http capell-site https://capell.app/mcp/capell \
  --header "Authorization: Bearer cmcp_your_token"
```

Codex:

```toml
[mcp_servers.capell-site]
url = "https://capell.app/mcp/capell"

[mcp_servers.capell-site.headers]
Authorization = "Bearer cmcp_your_token"
```

Authenticated installed-site MCP:

```bash
claude mcp add --transport http capell-site https://your-site.example/mcp/capell \
  --header "Authorization: Bearer cmcp_your_token"
```

The default installed route is `/mcp/capell`.

To expose a separate knowledge or discovery route, publish the config and set
`capell-mcp.routes.knowledge` or `capell-mcp.routes.home` to an explicit path.
Only do that for deployments that intentionally allow that access.

## Filament Admin Prompt Builder

When Capell Admin and Filament are installed, the package registers a small MCP tool item in the admin toolbar. It opens a prompt builder page that asks for the goal, target area, intended action, safety level, constraints, and success criteria, then prepares a structured prompt for an MCP client.

The prompt builder is intentionally non-mutating. It helps admins prepare safer requests while all real changes still go through scoped MCP capabilities, preview tokens, confirmation, policies, and audit logging.

## Capability Workflow

Mutating tools use a two-step workflow:

1. `capell-site-run-capability` returns a preview and confirmation token.
2. `capell-site-confirm-capability` applies the same payload if the token, user, client, scope, and payload hash still match.

This gives MCP clients full admin workflows without turning MCP into a raw remote-code/admin-action executor.
