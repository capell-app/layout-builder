# Page Drafts & Publishing

This guide covers how draft and publish work on the page edit screen.

## Live vs Draft

Every page edit screen shows a status banner at the top:

- **Live (green)** — you're editing the published version. Anything you save as a normal Save is visible on the public site immediately.
- **Draft in :workspace (blue)** — you're editing a draft copy. Saves are scoped to that workspace and stay invisible to visitors until the workspace is published.

The status banner links straight to the workspace when you're viewing a draft.

## Saving a draft (simple flow)

From a live page:

1. Click **Save as Draft**
2. In the modal, leave "Create a new draft for this page" selected (the default)
3. Click **Save as Draft**

A new workspace named `Draft: :page · :timestamp` is created behind the scenes, your changes are saved into it, and you land on the draft copy.

## Publishing a draft

From a draft page (status banner is blue):

1. Click **Publish**
2. Confirm the scope:
    - If the workspace only contains your page, publishing makes **that one page** live
    - If the workspace contains multiple pages, all of them go live together (the button label says so — "Publish workspace (n pages)")

After publish, you're returned to the live version of the page.

## Using an existing workspace

From a live page:

1. Click **Save as Draft**
2. Choose "Save to :workspace (active)" if a workspace is currently active, or "Choose another workspace…" to pick a different open workspace
3. Click **Save as Draft**

Use this when you want to batch your change with work happening in an existing editorial workspace.

## Approval workflow

If a workspace requires review:

- The **Publish** button is replaced with **Submit for review**
- While in review, **Publish** is disabled and a status panel shows who the draft is waiting on, plus any reviewer feedback
- If changes are requested or the draft is rejected, a **Resubmit for review** button appears after you've made revisions

## Seeing all copies of a page

The header action group has a **Page Copies (n)** entry showing:

- The live version
- Every open workspace that has a draft copy of this page
- The approval status of each draft

Per-row actions:

- **Preview** — opens the published URL (for live) or a workspace preview URL (for drafts)
- **Open Workspace** — go to the workspace detail page
- **Delete** — remove just this draft copy. If it's the last draft of a single-page workspace, the empty workspace is removed too.

## FAQ

**Does "Save as Draft" hide on a draft page?** Yes. When you're already editing a draft, saving means Save. The Save as Draft button is only shown on live pages.

**Can I delete the live version from Page Copies?** No. The live row has no Delete action — it's the published state of the page. Delete works on draft rows only.

**What's a SinglePageDraft workspace?** It's an auto-created workspace used by the simple draft flow. It's marked with a distinct kind so the system knows to clean it up if its last draft is deleted. Multi-user editorial workspaces (kind = Manual) are never auto-deleted.
