# 🤖 Copilot & AI Assistance Guidelines

These guidelines define how AI tools (like GitHub Copilot or other code ai-orchestrators) should be used within this repository to keep the codebase consistent, secure, and maintainable.

---

## 1. Core Principles

- AI suggestions are drafts, not final code—always review before committing.
- Never include secrets, tokens, credentials, or proprietary licensed code.
- Prefer original implementations or vetted OSS; do not copy random snippets without understanding.
- All new PHP source files must start with `declare(strict_types=1);`.
- Only add comments when absolutely necessary—prefer clear, self-explanatory code and naming.
- Prefer pure, testable domain logic; isolate side effects (DB, FS, HTTP) in dedicated Actions/Services.
- Favor composition (small Actions / Services / value objects) over deep inheritance hierarchies.
- Inject time, locale, and randomness (Clock / Locale / RNG abstractions); avoid direct global calls in domain logic.
- Fail fast on invalid state—avoid silent coercion or concealed errors.

---

## 1A. Variable Naming & Closure Typing

- Never use one-letter or terse variable names like `$q`, `$r`, `$x` in production code. Prefer descriptive names: `$query`, `$request`, `$payload`, `$collection`.
- All new closures MUST declare parameter types and (where supported) return types explicitly. Example:
    ```php
    $users->filter(function (User $user): bool {
        return $user->isActive();
    });
    $builder->whereHas('sites', function (Builder $query): void {
        $query->whereKey($siteId);
    });
    ```
- Arrow functions MAY be used only when parameter typing remains clear and a return type would be redundant. Prefer full closures if adding a return type improves clarity.
- Do not abbreviate `$query` to `$q` or `$builder` to `$b`—optimize for readability over keystroke savings.
- When a closure mutates a passed-in object (e.g., an Eloquent query), declare a `: void` return type.
- Ensure every method and closure either has a native return type or, if impossible, a precise docblock `@return` annotation.

---

## 1B. Static Analysis & PHPStan Compliance

All contributed code MUST be fully analyzable by PHPStan at the configured max level.

- Treat ALL PHPStan (and Larastan) warnings as failures. Either fix or add a narrowly scoped baseline entry with a justification comment.
- Prefer small, single-purpose methods over large inline closures to aid static analysis and readability.
- Always declare explicit return types (including `: void`) for methods and closures unless implementing legacy interfaces.
- Use generics and array-shape annotations where needed (e.g. `/** @var Collection<int, User> */`).
- Avoid `mixed`; replace with value objects or precise unions.
- Guard nullable values early (`if ($item === null) { return; }`).
- Replace dynamic property access with explicit getters/setters if inference fails.
- Annotate fixed array structures with shapes: `@param array{value:string,label:string} $option`.
- Never add broad `ignoreErrors` patterns; target exact messages and include rationale.
- Prefer immutable value objects; use readonly properties where possible.
- Keep cyclomatic complexity low—extract helpers when branching or loops grow beyond clarity (≈15 branches is a soft ceiling).

---

## 1C. Enum Guidelines

Follow these rules for all new and existing Enums:

1. Namespace Organization
    - Place all Enums under a dedicated namespace, e.g. `App\Enums` (in packages: `Capell\Core\Enums`).
    - Group related Enums by domain (e.g. `Orders`, `Billing`) to avoid catch-all buckets.

2. Backed vs Pure Enums
    - Use backed (string or int) Enums when persisting values to the database or interacting with external systems.
    - Prefer string-backed for readability unless integer semantics are domain-critical.

3. Case Naming
    - Prefer PascalCase for multi-word cases (e.g. `LanguageLocales`, `TotalSites`) where readability benefits and mirrors existing code.
    - Use UPPER_SNAKE_CASE only for domain state or constant-like flags (e.g. `DRAFT`, `ARCHIVED`, `CANCELLED`) to signal status semantics.
    - Remain consistent within a single Enum: do not mix styles arbitrarily—choose PascalCase OR UPPER_SNAKE_CASE based on semantic category.

4. Helpful Methods
    - Provide concise helpers (`label()`, `isTerminal()`, `options()`) to reduce duplication and keep calling code expressive.

5. Documentation
    - Document each case with a short description via a docblock above the Enum or inline comments when non-obvious.
    - Explain transitional / deprecated cases clearly (`@deprecated` with planned removal version).

6. Single Responsibility
    - If an Enum accumulates many cross-cutting helpers, split into multiple Enums or introduce a dedicated service.

7. Type Safety in Signatures
    - Always type-hint Enum parameters and return types:
        ```php
        public function updateOrderStatus(Order $order, OrderStatus $newStatus): void
        {
            // $newStatus is guaranteed to be an OrderStatus
        }
        ```
    - Avoid passing raw scalar values around once an Enum exists.

8. Performance Considerations
    - Backed Enums add negligible overhead; optimize only if profiling identifies a hotspot.
    - Cache expensive derived mappings (labels/options) using a static local variable. Avoid global state or external caches unless persistence is required.

9. Interoperability
    - For API output, expose both `value` and a human `label`; never force clients to derive labels from case names.

10. Migration Strategy
    - When introducing a new backed Enum for an existing varchar/int column, ship a data migration converting legacy values first.
    - Keep legacy constants during transition marked `@deprecated` until confirmation of full rollout.

11. Testing
    - Add tests asserting helper methods, option builders, and deprecated case handling.
    - Validate that every `from()` or custom factory rejects invalid input (expect exceptions).

12. Deprecation & Evolution
    - Mark removable cases with `@deprecated` and add a clear removal version/date.
    - Avoid repurposing existing case values for different semantics—add new cases instead.

## 1D. Enum Do's & Don'ts Quick List

Do:

- Use backed Enums for persisted values.
- Centralize labels & option arrays inside the Enum.
- Cache derived arrays with a static variable.
- Provide semantic helpers (e.g. `isVisible()`).

Don't:

- Mix naming styles inside a single Enum.
- Store raw scalar values where an Enum exists.
- Add unrelated behaviors—split the Enum or create a service.
- Mutate external/global state in Enum methods.

---

## 1E. Readability & Spacing

- Prefer vertical spacing (blank lines) between logical blocks, method arguments, and inside methods to improve clarity and maintainability.
- Add blank lines before/after control structures, between unrelated statements, and between methods.
- Avoid excessive or inconsistent blank lines; use spacing to group related logic and visually separate distinct steps.
- This applies to all PHP, test, and config files.

## 1K. Package Plugin Independence (`address` / `blog` / `layout-builder`)

- `address`, `blog`, and `layout-builder` plugins MAY depend on each other, but MUST remain decoupled from Core internals except via documented public interfaces.
- Core MUST NOT depend directly on any plugin (`address`, `blog`, `layout-builder`). Avoid imports, facades, or direct calls from Core into these plugins.
- Cross-plugin coordination should use neutral boundaries (configuration, cache/filesystem paths, events/commands) without introducing compile-time dependencies.
- When Core needs to trigger a behavior in a plugin (e.g., clear caches), prefer:
    - Removing/invalidating the shared cache file/path via Filesystem, or
    - Emitting a framework event or calling an Artisan command name (string), not importing plugin classes.
- If shared behavior grows complex, extract a minimal interface in a shared module and implement adapters per plugin; do not point Core to concrete plugin classes.
- Enforce with static analysis: any `use Capell\Address\...`, `use Capell\Blog\...`, or `use Capell\LayoutBuilder\...` from Core is a blocker.

---

## 2. Language & Framework Constraints

- Target: PHP ^8.2 and Laravel 10–12 compatible APIs only.
- Avoid deprecated Laravel helpers or facades; prefer current best practices.
- Use native type hints everywhere possible; supplement with docblocks for generics or complex array shapes.
- For filesystem writes in application code, prefer the `File` facade (spying & testability) over raw functions.
- **Naming Conventions:**
    - **Traits:** Name traits with descriptive, noun-based names ending with "able" (e.g., `HasRoles`, `Notifiable`) or with the `Trait` suffix if clarity is needed (e.g., `LogsActivityTrait`).
    - **Interfaces:** Name interfaces with capability-based or adjective names ending with `Interface` (e.g., `RenderableInterface`, `ShouldQueueInterface`).
    - Follow Laravel's conventions for clarity and discoverability; avoid ambiguous or overly generic names.

---

## 3. Actions Pattern & Best Practices

All reusable business logic SHOULD be implemented as Actions using `lorisleiva/laravel-actions`.

Summary:

- Use `AsAction` (or appropriate traits) with a mandatory `handle()` method.
- Prefer `$this->run()` / `ActionClass::run()` for validation + authorization + execution.
- Implement modes only as needed (`asController`, `asJob`, `asCommand`, `asListener`).
- Keep `handle()` lean (< ~60 lines); extract sub-actions or services when larger.
- Validation via `rules()`, access control via `authorize()`.
- Presentation formatting goes in `jsonResponse()` / `htmlResponse()`, not in `handle()`.

Comprehensive guidelines (structure templates, prompting patterns, anti-patterns, migration strategy, security, performance, checklist) are maintained in:
`docs/actions-guidelines.md`

---

## 4. Testing Policy (Pest Only)

- All tests MUST use Pest—no new PHPUnit classes.
- Minimum per feature: one happy path + one edge/negative case.
- Use `beforeEach` / `afterEach` for setup/teardown.
- Spy on side effects (e.g., `File::spy()`) rather than reading/writing real external state.
- For commands: assert exit codes, side-effect calls, and transformed outputs.
- For reflection/dynamic logic: assert that generated code contains expected namespace/import fragments or aliases.
- Maintain a fast feedback core suite (aim < 60s); move slow integration/benchmark tests behind a separate profile.
- Periodically use mutation testing on critical modules to assess test rigor (optional stretch goal).

### Snapshot / Large Output Testing

Use Pest expectations (`expect(...)->toContain(...)`) rather than snapshot files unless structure is large and stable.

### Pest Usage & Chaining Expectations

Core style rules for Pest tests here:

- Single, deterministic assertion: higher-order form is fine (`it('slugifies')->expect(Str::slug('X'))->toBe('x');`).
- Multiple related assertions: use chaining with one expectation per line for clarity.
- Keep test names behavior-focused, not implementation-focused.
- Limit chains to ~3–4 related expectations; split into additional `it()` blocks if they diverge.
- Vertical spacing: blank line before a new logical assertion chain inside a test.
- Avoid heavy computation inside `expect()`; compute first, then assert.
- For collections, prefer semantic helpers: `expect($users)->each->active->toBeTrue();`.

Readable chaining pattern (preferred):

```php
it('calculates monetary breakdown')
    ->expect($order)->subtotal->toBe(100_00)
    ->and($order)->discount->toBe(10_00)
    ->and($order)->tax->toBe(18_00)
    ->and($order)->total->toBe(108_00);
```

Spacing example with setup and multiple chains:

```php
it('generates invoice payload')
    ->tap(function () {
        // Arrange (keep logic minimal here; heavy setup in beforeEach)
        $this->invoice = InvoiceFactory::make(['subtotal' => 100_00]);
    })
    // First chain: structural keys
    ->expect(InvoiceTransformer::for($this->invoice)->toArray())
        ->toHaveKey('id')
        ->toHaveKey('subtotal')
        ->toHaveKey('line_items')

    // Second chain: derived values
    ->and($this->invoice)->subtotal->toBe(100_00)
    ->and($this->invoice)->tax->toBe(18_00)
    ->and($this->invoice)->total->toBe(118_00);
```

Dataset + higher-order expectation (concise form):

```php
use Illuminate\Support\Str;

dataset('titles', [
    ['Hello World', 'hello-world'],
    ['Multi  Space', 'multi-space'],
]);

it('slugifies provided titles')
    ->with('titles')
    ->expect(fn ($original, $expected) => Str::slug($original))
    ->toBe($expected);
```

Anti-patterns to avoid:

- Chaining 6+ heterogeneous expectations (split tests).
- Embedding database queries directly in an expectation chain.
- Asserting internal private state via reflection—assert public behavior instead.

Refactor guidance:
If a chain gains branching logic (ifs/loops) or exceeds ~4 lines, convert to a closure test and extract helpers.

---

## 5. Commit Conventions

Prefix commits semantically:

- `feat:` new feature
- `fix:` bug fix
- `test:` tests only
- `docs:` documentation updates (including this file)
- `refactor:` internal code restructure (no behavior change)
- `chore:` tooling, build scripts, misc maintenance

Group related changes (feature + tests + docs) together. Avoid mixing refactors with new functionality.

---

## 6. Imports & Namespaces

- Maintain logical grouping: Framework / Vendor → Domain → Relative.
- Do not leave unused imports; remove as part of the change.
- Preserve existing ordering unless a broader cleanup is intentional.

---

## 7. Error Handling & Messaging

- Use guard clauses with `throw_if` / `throw_unless` for clarity.
- Error messages should be actionable: include identifiers, not raw objects.
- Avoid swallowing exceptions silently—log if you must suppress.
- Differentiate domain, validation, and infrastructure exceptions; map consistently to HTTP/CLI responses.

---

## 8. Performance & Safety

- Prevent N+1 queries (eager load or restructure loops).
- Avoid synchronous external HTTP calls in request cycles unless cached.
- Consider memory usage with large collections—use generators, chunking, or streams when appropriate.
- Adhere to performance budgets (e.g., ≤ target queries / render time); benchmark critical paths before optimizing.
- Prefer chunked iteration / generators for large datasets—avoid loading massive result sets entirely into memory.

---

## 9. Security Hygiene

- Validate all incoming request/input data.
- Use parameter binding / Eloquent rather than raw SQL unless parameterized.
- Never concatenate unsanitized input into shell commands or HTML output.
- Use least-privilege credentials and tight scopes for services.
- Encode/escape user-controlled output for its target context (HTML, JS, SQL params, shell, etc.).
- Minimize stored PII; collect only necessary data and purge stale sensitive records proactively.
- Avoid weak/insecure hashing algorithms (`md5`, `sha1`). Prefer `hash('sha256', $data)` (or a domain-specific keyed HMAC when integrity matters). Justification: stronger collision resistance reduces accidental key overlap and future-proofs integrity checks.

---

## 10. Documentation & Discoverability

- Public interfaces, abstract classes, and complex helpers should have concise docblocks.
- Reflect major behavioral additions in the README or docs immediately.
- Place reusable test helpers in `tests/Pest.php`.

---

## 11. Refactoring Guidance

Before large AI-assisted refactors:

- Ensure test coverage is adequate.
- Introduce changes incrementally (e.g., clean one subsystem at a time).
- Keep PRs reviewable; split if diff becomes unwieldy.
- Deprecate public APIs before removal—mark with `@deprecated`, add migration notes, and schedule removal.
- Design for deletion: structure features with low coupling so any module can be removed safely.

---

## 12. Dynamic Import & Reflection Rules

- Reflection-based logic must include tests verifying import insertion (with and without alias conflicts).
- When aliasing due to name collisions, ensure usage lines (extends/implements/traits) are updated consistently.
- Avoid over-aliasing—only alias when collision detected.

---

## 13. Adding New Tooling / Dependencies

- Justify new dependencies (performance, security, or significant reduction of complexity).
- Pin versions reasonably (use caret constraints aligned with project policy).
- Include tests for code paths relying on the dependency.
- Prefer native or existing approved packages before introducing new libraries.

---

## 14. Handling Large AI Suggestions

If Copilot suggests a big block:

- Break it down; validate each section (types, correctness, style).
- Refuse suggestions that mix unrelated concerns.
- Prefer explicit variable naming over cryptic short names.
- Avoid premature abstractions—extract only after two real-world repetitions.

---

## 15. When Unsure

- Leave a `// TODO:` with context instead of guessing complex domain logic.
- Open an issue or start a discussion for architectural questions.

---

## 16. Quick Checklist Before Committing

- [ ] Strict types declared.
- [ ] No secrets / credentials.
- [ ] Imports trimmed & organized.
- [ ] Tests added/updated & passing (run with `./vendor/bin/pest`).
- [ ] Lint (`composer lint`) clean.
- [ ] Static analysis (`composer analyze`) clean or justified.
- [ ] README / docs updated if needed.
- [ ] Commit message semantic.
- [ ] **Never use `php artisan` in this package; use `vendor/bin/*` and orchestral/testbench if needed.**

---

## 17. Future Enhancements (Optional)

- Automated alias conflict detection tests.
- Dry-run modes for publish/generate commands.
- Centralized performance benchmarking harness.
- Mutation testing adoption roadmap.

---

## 18. Reliability & Determinism

- Inject time, locale, and randomness via abstractions (Clock/Locale/RNG); avoid non-deterministic globals in pure logic.
- Eliminate hidden environment variance—make env-based decisions explicit and testable.
- Prefer idempotent Actions where practical (safe to run twice without side effects).

---

## 19. Observability & Diagnostics

- Log only actionable events (auth anomalies, failures, major state transitions); avoid noisy debug logs in production.
- Attach correlation IDs / request IDs to logs and error dashboard-dashboard_reports when available.
- Instrument critical paths (timings, counts) before refactoring for performance.
- Include structured context (user ID, feature flag states) in error logging—avoid dumping entire objects.

---

## 20. Collaboration & Review Culture

- Keep PRs focused and reviewable (< ~400 LOC diff when feasible); split large refactors early.
- Name classes/methods after business intent, not implementation detail (e.g., `GenerateInvoicePdf` > `PdfHelper`).
- Provide migration notes in PR descriptions when changing public APIs.
- Use feature flags for gradual rollouts; document flag purpose and cleanup plan.

---

## 21. Resilience & Exception Strategy

- Categorize exceptions (Domain vs Validation vs Infrastructure) and convert them to consistent HTTP/CLI responses.
- Graceful degradation: use feature flags or env toggles to disable unstable components without hard removal.
- Avoid blanket catch blocks—catch specific exception types and handle purposefully.

---

## 22. Mandatory Static Analysis Verification

To prevent regressions like missing class/enum case references (e.g. wrong helper namespaces or renamed Enum cases), every AI-assisted change MUST:

1. Run `composer analyze` locally (PHPStan) before committing.
2. If new files introduce classes/enums used by observers, ensure namespace matches PSR-4 autoload path.
3. Never guess Enum case names—open the Enum file and reference existing cases (PascalCase vs LEGACY UPPER_SNAKE_CASE).
4. When renaming Enum cases, update ALL usages in one commit and run static analysis immediately.
5. For helpers moved across namespaces, update imports everywhere using grep + replace, then run analysis.
6. Add a minimal test when adding cache-flush logic to guarantee an existing Enum case remains valid (e.g. assert `CacheEnum::HasFoundationTheme` resolves and is a `CacheEnum`).

Quick Command Set:

```
composer analyze
grep -R "CacheEnum::" packages/core/src/Observers
```

Failure Handling:

- If PHPStan dashboard-dashboard_reports `class.notFound` or `classConstant.notFound`, fix imports/case names instead of suppressing.
- Do NOT commit while analysis fails; treat as a hard blocker.

Pre-Commit Hook (Recommended):
Integrate a local git pre-commit hook running `composer analyze` and rejecting failures to enforce consistency.

---

## 23. Comment Policy (Strict Minimalism)

The codebase favors self-explanatory naming and structure over comments. AI suggestions MUST NOT introduce superfluous comments.

Allowed comment types ONLY:

- Public surface docblocks (classes, interfaces, abstract methods) that clarify contracts or complex return types (e.g. generics, array shapes).
- Security, performance, or algorithmic rationale where non-obvious (e.g. explaining a deliberate `O(n)` trade-off, cryptographic decision, or concurrency guard).
- `@deprecated` / migration notes with planned removal version.
- Intentional `// TODO:` or `// FIXME:` markers WITH context (why, by when, reference issue #). These should be short-lived.
- Critical domain invariants or constraints that are not self-evident from code alone.

Explicitly FORBIDDEN:

- Redundant narration (e.g. `// set variable`, `// loop through items`).
- Comments echoing code (`// increment i`).
- Auto-generated filler (`// added import`, `// beginning of class`).
- Large banner blocks or decorative separators.
- Leaving commented‑out code in commits (delete or move to a gist/revision history).

Enforcement Rules:

- PRs containing forbidden comment styles must be revised before merge.
- When replacing commented logic with clearer code, REMOVE the comment instead of updating it.
- Prefer extracting well-named methods/Enums over explaining logic inline.
- AI tooling should default to zero comments unless one of the allowed categories applies.

Review Checklist Addition:

- [ ] No unnecessary comments; all remaining comments fit an allowed category.

Refactoring Guidance:
If a comment explains a block >3 lines, consider extracting a method named after the comment’s intent (the need for the comment should disappear).

Example Transform:
Bad:

```php
// fetch active users
$users = User::query()->where('active', true)->get();
```

Good:

```php
$activeUsers = User::query()->where('active', true)->get();
```

Exceptional (Allowed):

```php
// Using array cache driver intentionally: avoids cross-request persistence & enables atomic test isolation.
$cached = Cache::driver('array')->get($key);
```

---

**Note:** This is a package repository. `php artisan` does not exist in this context. Always use `vendor/bin/*` (e.g., `./vendor/bin/pest`) for running tests and CLI tools. If Laravel integration is required, orchestral/testbench will be used automatically.
