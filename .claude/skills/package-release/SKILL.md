---
name: package-release
description: "Use this skill when preparing Laravel package releases: generated release notes, GitHub release workflows, version checks, tags, release validation, or release automation changes. Never publish autonomously."
license: MIT
metadata:
  author: laravel
---

# Package Release

## Primary Goal

Prepare a safe package release checklist and implementation without tagging, pushing, or publishing unless the user explicitly approves that action.

## Workflow

1. Review generated release notes config, GitHub release workflows, open diff, and pending package changes.
2. Validate the release state with `composer test` before recommending a release.
3. Confirm whether version metadata needs to change; many Laravel packages rely on Git tags rather than a hardcoded package version.
4. Review tag naming, release branch, and GitHub release workflow behavior before any release command.
5. Do not tag, push, or publish without explicit user approval.

## References

- `.github/release.yml`
- `.github/workflows/tests.yml`
- `composer.json`

## Examples

- Prepare a release by confirming generated release notes categories, running `composer test`, and drafting the tag command for user approval.
- Update release notes grouping in `.github/release.yml` when a new label convention is added.

## Anti-Patterns

- Creating tags, pushing branches, or publishing releases without explicit approval.
- Skipping `composer test` before a release recommendation.
- Changing release workflows without checking the supported matrix in `.github/workflows/tests.yml`.
