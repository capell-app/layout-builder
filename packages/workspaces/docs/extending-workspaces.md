# Extending Workspaces

The Workspaces package provides several extension points for customizing behavior during workspace lifecycle events and workspace-aware model management.

## Event Subscribers

Implement `WorkspaceEventSubscriber` to hook into workspace lifecycle events:

### Example: Custom Validation Before Publish

```php
<?php

namespace App\Workspaces;

use Capell\Workspaces\Events\Contracts\WorkspaceEventSubscriber;
use Capell\Workspaces\Models\Workspace;

class MyPublishValidator implements WorkspaceEventSubscriber
{
    public function beforeClone(Workspace $source, Workspace $target): bool
    {
        return true; // Allow clone
    }

    public function afterClone(Workspace $source, Workspace $target): void
    {
        // Custom logging or cleanup after clone
    }

    public function beforePublish(Workspace $workspace): bool
    {
        // Custom validation — return false to block publish
        if (! $this->isValid($workspace)) {
            return false;
        }

        return true;
    }

    public function afterPublish(Workspace $workspace): void
    {
        // Custom logic after successful publish
    }

    public function beforeDelete(Workspace $workspace): bool
    {
        return true; // Allow delete
    }

    public function afterDelete(Workspace $workspace): void
    {
        // Cleanup after workspace deleted
    }

    private function isValid(Workspace $workspace): bool
    {
        // Your validation logic
        return true;
    }
}
```

### Register Your Subscriber

In your app's service provider:

```php
use Capell\Workspaces\Facades\CapellWorkspaces;
use App\Workspaces\MyPublishValidator;

public function boot(): void
{
    CapellWorkspaces::subscribe(MyPublishValidator::class);
}
```

## Model Registration

Register additional draftable models via `WorkspaceRegistry`:

```php
use Capell\Workspaces\WorkspaceRegistry;
use Capell\Workspaces\Models\Workspace;

WorkspaceRegistry::register(MyModel::class);

// With custom clone logic:
WorkspaceRegistry::register(MyModel::class, cloneUsing: function (MyModel $original, Workspace $workspace): MyModel {
    $clone = $original->replicate();
    $clone->workspace_id = $workspace->id;
    // Custom clone behavior
    return $clone;
});

// With finalization on publish:
WorkspaceRegistry::register(MyModel::class, finalizeOnPublish: function (MyModel $draftRow): MyModel {
    // Custom logic when moving from draft to live
    return $draftRow;
});
```

## Publish Checks

Add custom publish validations:

```php
<?php

namespace App\Workspaces;

use Capell\Workspaces\Models\Workspace;

class MyCustomPublishCheck
{
    public function validate(Workspace $workspace): array
    {
        $errors = [];

        // Your validation logic
        if (! $this->hasRequiredContent($workspace)) {
            $errors[] = 'Workspace must have at least one published page.';
        }

        return $errors;
    }

    private function hasRequiredContent(Workspace $workspace): bool
    {
        return $workspace->pages()->count() > 0;
    }
}
```

Register in your app's service provider:

```php
use Capell\Workspaces\Facades\CapellWorkspaces;
use App\Workspaces\MyCustomPublishCheck;

public function boot(): void
{
    CapellWorkspaces::registerPublishCheck(MyCustomPublishCheck::class);
}
```

## Workspace Context Hooks

Use middleware or service providers to customize workspace resolution:

```php
<?php

namespace App\Workspaces;

use Capell\Workspaces\Concerns\ResolveWorkspaceContext;
use Illuminate\Http\Request;

class CustomWorkspaceResolver
{
    public function resolve(Request $request): ?string
    {
        // Custom logic to determine active workspace
        // Return workspace ID or null for live content

        if ($request->has('workspace_id')) {
            return $request->query('workspace_id');
        }

        return null;
    }
}
```

## Draftable Model Extension

When implementing the `Draftable` contract, you can customize behavior:

```php
<?php

namespace App\Models;

use Capell\Workspaces\Contracts\Draftable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model implements Draftable
{
    public function getDraftableAttributes(): array
    {
        // Define which attributes should be cloned to drafts
        return ['title', 'slug', 'content', 'published_at'];
    }

    public function beforePublish(): void
    {
        // Runs before this model is published from draft
        // Validate, cleanup, or prepare for publication
    }

    public function afterPublish(): void
    {
        // Runs after successful publication
        // Clear caches, trigger events, etc.
    }
}
```

## Best Practices

1. **Keep subscribers lightweight** — long-running operations should be queued
2. **Return early from validation** — fail fast in `beforePublish` checks
3. **Use transactions** — wrap multi-step publishing logic in database transactions
4. **Test with real workspaces** — don't mock; create actual draft/live environments
5. **Document custom checks** — make validation errors user-friendly and discoverable
6. **Avoid circular dependencies** — don't subscribe to events that trigger more events
