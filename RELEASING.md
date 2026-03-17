# Releasing

This document is only for cutting published releases. Normal branch, commit, pull request, and local validation flow lives in `CONTRIBUTING.md`.

## Preconditions

Before running a release:

- the release changes are already merged to `main`
- `CHANGELOG.md` already contains the exact version and date
- the merged `main` commit already has a successful `quality` workflow run

## Release Flow

1. Prepare the release changes on a topic branch such as `fix/...` or `feat/...`.
2. Run `composer validate --strict`.
3. Run `composer qa:check`.
4. Update `CHANGELOG.md` with the exact release version and date.
5. Merge the pull request into `main`.
6. Wait for the post-merge `quality` workflow on `main` to finish green.
7. In GitHub Actions, run the `release` workflow on `main`.
8. Provide the version as `1.2.3` or `v1.2.3`.
9. Let the workflow create the annotated tag and publish the GitHub release.

## Workflow Guardrails

The `release` workflow rejects the run unless:

- it is running from the latest `main` commit
- the requested tag does not already exist
- `CHANGELOG.md` contains the matching version heading
- the merged `main` commit already has a successful `quality` workflow run

The `quality` workflow also lint-checks GitHub Actions files with pinned `actionlint` before the PHP QA steps run.

## Release Rules

- Do not create release tags manually on topic branches.
- Workflow files such as `.github/workflows/quality.yml` and `.github/workflows/release.yml` become part of a release only when they are merged before that release tag is created.
- If a workflow or release-process fix lands after a version is already published, cut the next patch release instead of moving or recreating the published tag.

## Human Step

- The manual `release` workflow trigger is always a human step.
- If you want a second explicit approval gate, configure required reviewers for the `github-release` environment in the repository settings.
