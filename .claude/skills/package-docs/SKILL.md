---
name: package-docs
description: "Use this skill when writing or revising package documentation after creating a package from this starter: README content, VitePress pages, contributing docs, upgrade notes, installation, usage, testing, workbench docs, or examples."
license: MIT
metadata:
  author: laravel
---

# Package Docs

## Primary Goal

Keep package docs concise, accurate, and useful for developers installing, configuring, testing, and contributing to the finished package.

## Workflow

1. Inspect `README.md`, `docs/`, `docs/.vitepress/config.ts`, and contributing docs before writing new documentation.
2. Use the configured package name, vendor slug, namespace, commands, config keys, and publish tags consistently.
3. Remove starter-only setup notes once they no longer apply to the configured package.
4. Cover README, VitePress pages, contributing docs, upgrade notes if added later, and package usage examples using Laravel ecosystem tone.
5. Write direct, Laravel-style documentation: short paragraphs, imperative setup steps, concrete examples, and no marketing filler.
6. Keep README content high level and move longer guides to VitePress pages when the details would distract from first-run package setup.

## Writing Rules

- Prefer practical examples over abstract descriptions.
- Use the configured package identity in commands, URLs, badges, namespaces, and publish tags.
- Make installation, configuration, publishing, usage, testing, and workbench instructions easy to scan.
- Explain only behavior that exists in the implemented package feature.
- Keep headings stable and predictable so downstream authors can remove or expand sections safely.

## References

- `README.md`
- `docs/index.md`
- `docs/installation.md`
- `docs/configuration.md`
- `docs/defining-expressives.md`
- `docs/converting-models.md`
- `docs/relationships.md`
- `docs/serialization.md`
- `docs/persistence.md`
- `docs/generator.md`
- `docs/syncing.md`
- `.github/CONTRIBUTING.md`
- `docs/.vitepress/config.ts`

## Examples

- Add installation docs that show `composer require wendelladriel/laravel-expressive`, publish tags, and migration steps when relevant.
- Add installation and usage docs that explain package setup without over-documenting internals.

## Anti-Patterns

- Documenting unreleased or unimplemented package features as if they exist.
- Breaking placeholders or replacing them with a real vendor/package name too early.
- Duplicating large sections between README and VitePress when a short cross-reference is enough.
- Leaking starter-only instructions into finished package documentation.
