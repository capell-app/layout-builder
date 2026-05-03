# Deployments Overview

The Deployments package stores Git provider connections and publishes Composer requirement changes for package installation flows.

## Responsibilities

- Manage active deployment connections.
- Prepare Composer requirement commits.
- Publish install changes through Git provider pull requests.
- Provide the `PublishesComposerChanges` contract used by marketplace-style install flows.

Deployment connection secrets are stored through the package model casts and should remain encrypted at rest.
