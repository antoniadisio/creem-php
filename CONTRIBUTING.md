# Contributing

This document is the source of truth for contributor workflow, deterministic validation, and repository hygiene.

## Doc Boundaries

- Consumer-facing SDK usage lives in `README.md`.
- Live and destructive verification lives in `playground/README.md`.
- Published release flow lives in `RELEASING.md`.

## Package Boundaries

This repository is the unofficial `antoniadisio/creem-php` SDK for PHP 8.4+.

- Keep the supported public surface in `Antoniadisio\Creem\Client`, `Config`, `Webhook`, `Resource\*`, `Dto\*`, `Enum\*`, and `Exception\*`.
- Keep transport, hydration, and request serialization internals under `src/Internal/`.
- Do not turn internal Saloon transport classes into part of the public SDK contract.
- Public signature changes in `src/Client.php`, `src/Config.php`, and `src/Resource/*` require manual review because Rector intentionally skips automatic type-declaration inference there.

## Setup

- Run `composer install`.
- Use PHP 8.4 or newer.
- Read `README.md` before changing the public API surface.

## Usual Git Flow

1. Update local `main` with `git checkout main` and `git pull`.
2. Create a topic branch from `main`.
3. Use `fix/...` for fixes, docs, tooling, and compatibility work.
4. Use `feat/...` for new SDK capabilities.
5. Make changes on that branch only.
6. Run the relevant local validation commands.
7. Commit, push the branch, and open a pull request.
8. Wait for the `quality` workflow to pass, then squash merge.
9. Update local `main` again with `git checkout main` and `git pull`.
10. Delete the merged topic branch when you no longer need it.

Clarifications:

- A branch is an isolated line of work.
- A commit is a saved snapshot on that branch.
- Whoever makes the change opens the pull request. For your own work, that is you. For outside contributions, that is the contributor. Merge authority stays with the repository owner.
- `git fetch` updates remote-tracking refs such as `origin/main` without changing your current files.
- `git pull` fetches and then updates your current branch.
- Squash merge creates a new commit on `main`, so release tags must point at the merged `main` commit, not at the topic-branch tip.

## Code and Test Expectations

- Keep source changes under `src/`.
- Keep `tests/Integration/` resource-focused, usually one resource per file.
- Keep repository guardrails under the Pest `repo` group and out of the default contributor inner loop.
- Put pure logic and branch coverage in `tests/Unit/`.
- Put deterministic mocked transport coverage in `tests/Integration/`.
- Keep opt-in read-only network checks in `tests/Smoke/`.
- Do not add a `Feature` suite.
- Name test files `*Test.php`.
- Keep Pest descriptions as direct behavior statements with `test('...')`.
- Prefer shared helpers in `tests/TestCase.php`, `tests/IntegrationTestCase.php`, `tests/SmokeTestCase.php`, and `tests/Support/`.
- Add or update deterministic tests for every public API change, request mapper change, exception mapping change, and new feature.
- For new features, add Pest tests covering happy paths, invalid input, boundary conditions, and unauthorized behavior when that behavior exists in scope.
- Update response fixtures in `tests/Fixtures/Responses/` when payload shapes change.
- Keep committed response fixtures sanitized with placeholder IDs, reserved-domain URLs, `@example.test` emails, and the canonical fixture timestamps already used by the repo.
- Keep OpenAPI contract work aligned with `tests/Fixtures/OpenApi/creem-openapi.json`.
- When upstream wording or enum values drift from live behavior, normalize the committed OpenAPI fixture deliberately instead of preserving stale aliases in the public SDK.
- Any add, remove, behavior change, or signature change to an outbound SDK endpoint must update the matching playground action definitions, audit coverage, schemas, and `playground/README.md` in the same task.

## Style

- Use `declare(strict_types=1);`, 4-space indentation, typed properties, and clear immutable DTO-style objects.
- Prefer `final` classes unless extension is part of the design.
- Keep public DTOs, resources, and exceptions in their existing namespaces and naming patterns.
- Do not add obvious comments above methods or code blocks.
- Do not add variable docblocks unless a specific type annotation is needed.
- Add comments only when they explain non-obvious reasoning or constraints.

## Validation

- `composer test` or `composer test:unit` runs the fast contributor inner loop.
- `composer test:repo` runs repository guardrails such as contract, fixture, playground audit, and export-policy checks.
- `composer test:integration` runs deterministic mocked transport coverage only.
- `composer test:local` runs the full deterministic suite: all `Unit` coverage including `repo`, then `Integration`.
- `composer test:smoke` runs the opt-in live canary against `Environment::Test`. It requires only `CREEM_TEST_API_KEY`, skips when the key is absent, and intentionally covers only `stats()->summary(...)`.
- `composer cs` checks formatting.
- `composer cs:fix` applies formatting fixes.
- `composer stan` runs static analysis on `src` and `tests`.
- `composer qa` is the local fix-first flow and should pass before a task is considered complete.
- `composer qa:check` is the non-mutating verification flow and should be green before a pull request is opened.

Keep destructive or endpoint-specific live verification out of Pest. Use `playground/README.md` for manual live calls against `Environment::Test`.

## Repository Hygiene

- Do not commit local-only planning files or machine-specific files such as `.env`, `.spec/`, `.codex/`, `vendor/`, `node_modules/`, or IDE settings.
- Keep playground runtime artifacts such as `playground/state.local.json`, `playground/captures/`, and `playground/*.local.json` local via `playground/.gitignore`.
- Keep repo QA files such as `rector.php`, `phpstan.neon.dist`, `phpunit.xml.dist`, and `composer.lock` committed when they support contributor workflow or CI.
- Keep `.gitattributes export-ignore` aligned with the installed package boundary. The minimal consumer archive is `src/`, `composer.json`, `README.md`, and `LICENSE`.

## Commits and Pull Requests

- Write commit subjects in an imperative, outcome-focused style, 72 characters or fewer, with no trailing period.
- Keep pull request descriptions concise and list the validation commands you ran.
- Link the relevant issue when one exists.
- Open pull requests only after `composer qa:check` is green.
- Add a `CHANGELOG.md` entry whenever a versioned release needs release notes, and always for breaking public API changes.
- Release tags are created only by the manual `release` workflow from merged `main`, never from a topic branch. Use `RELEASING.md` for the release steps.

## Security Reporting

- Do not file public GitHub issues for vulnerabilities.
- Follow `SECURITY.md` and use the repository security reporting path for sensitive disclosures.
