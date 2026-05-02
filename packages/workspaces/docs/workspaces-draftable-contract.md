# Workspace draftable contract

> **New to workspaces?** Read [Workspaces & Versions](workspaces.md) first.
> A _workspace_ is an isolated sandbox for editorial changes; _live_ is the
> currently-published state. A _draftable_ model is one whose rows can exist
> in both — one copy on live, and one or more workspace-scoped copies that
> flip over when the workspace is published.

Any Eloquent model that participates in the workspace/version system must
honour a small, mechanical contract:

- It lives on **live** when `workspace_id = 0`.
- Edits inside a workspace create a **copy-on-write** clone stamped with the
  workspace's id.
- **Publish** flips that clone back onto live by setting its `workspace_id`
  to `0`.

Downstream packages adding a new draftable model can assert they meet this
contract with a single Pest helper.

## Using the helper

The helper is a Pest method exposed via the `Capell\Tests\Fixtures\Concerns\HasAssertWorkspaceDraftable` trait — apply it to your test case (`uses(...)` in Pest).

```php
use Capell\Workspaces\Models\Workspace;

it('honours the workspace draftable contract', function (): void {
    $factory = function (?Workspace $workspace): MyPackageModel {
        $row = new MyPackageModel;
        $row->forceFill([
            'workspace_id' => $workspace?->id ?? 0,
            'uuid' => (string) Str::uuid(),
            'name' => 'fixture',
        ])->save();

        return $row;
    };

    test()->assertWorkspaceDraftableContract(MyPackageModel::class, $factory);
});
```

The factory closure is called twice: once with `null` (the helper
expects a live row stamped with `workspace_id = 0`) and once more with
a `Workspace` instance after the initial edit path has been exercised.

## What the helper checks

Running the helper drives the following assertions:

1. **Registration.** The model is registered with the
   `WorkspaceRegistry` (idempotently — calling the helper repeatedly is
   safe).
2. **Live stamping.** A factory call with no workspace produces a row
   whose `workspace_id` is `0`.
3. **Copy-on-write on edit.** Editing the live row inside an open
   workspace creates a workspace-scoped clone and leaves the live row
   attribute values untouched.
4. **Publish flip.** After approving and publishing the workspace, the
   clone becomes live (reachable by its `uuid` with `workspace_id = 0`).
5. **Deletion tombstone.** When the model uses
   `Illuminate\Database\Eloquent\SoftDeletes`, deleting the live row
   inside a workspace creates a soft-deleted tombstone in the
   workspace and leaves the live row intact until publish.

Models that hard-delete (no `SoftDeletes` trait) are allowed — the
helper stops short of the deletion step in that case. Hard-deleting a
live row inside a workspace context throws `LogicException` at runtime
and should be tested separately.

## Prerequisites

- The model uses the `BelongsToWorkspace` trait.
- The model's table has `workspace_id` (indexed) and
  `shadowed_by_workspace_id` columns.
- The model table exposes a `uuid` column that the publisher uses to
  pair live rows with their workspace shadows.
- A `Workspace` factory is available (the helper creates two
  throwaway workspaces).

If any of those preconditions is missing the underlying assertion
failures point directly at the gap — usually a missing column or a
factory that doesn't stamp `workspace_id` correctly.

## Recommended layout

Keep the contract test alongside the model's other integration tests.
A single test function per draftable is enough; if your model has
extra invariants (custom `cloneUsing`, `finalizeOnPublish`, non-UUID
keying) write dedicated assertions in the same file — the shared
helper stays focused on the universal contract.
