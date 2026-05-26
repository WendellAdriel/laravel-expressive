---
name: package-generate-skill
description: "Use this skill when creating or updating the bundled Laravel Boost skill under resources/boost/skills from the package implementation and documentation. Trigger after public APIs, commands, config, routes, views, publish tags, examples, or docs change."
license: MIT
metadata:
  author: laravel
---

# Package Generate Skill

## Primary Goal

Keep the package's bundled Boost skill accurate, concise, and focused on helping Laravel applications adopt the package.

## Workflow

1. Inspect the package implementation before editing the Boost skill: service provider, facades, public classes, commands, config, routes, migrations, events, views, publish tags, and tests.
2. Inspect package documentation: `README.md`, `docs/`, `docs/.vitepress/config.ts`, contributing docs, examples, and changelog entries that describe user-facing behavior.
3. Identify the public integration surface only. Include install, configure, publish, command, route, facade, helper, middleware, event, and testing guidance only when the package actually exposes it.
4. Update `resources/boost/skills/*/SKILL.md` with practical adoption steps, references, examples, and anti-patterns for Laravel app developers using the package.
5. Preserve front matter, package metadata, placeholders in unconfigured packages, and the Boost skill structure: description, primary goal, workflow, references, examples, and anti-patterns.
6. Validate that the Boost skill does not describe internals as public API and does not document features that are not implemented.

## Writing Rules

- Write for consumers installing the package in a Laravel application, not for maintainers changing package internals.
- Prefer short, task-oriented steps over broad explanations.
- Point to concrete files only when they help the consumer understand package adoption.
- Keep examples runnable and aligned with documented package names, config keys, commands, and publish tags.
- Keep the skill small enough for an agent to load and apply quickly.

## References

- `resources/boost/skills/`
- `src/*ServiceProvider.php`
- `src/Facades/`
- `src/Console/Commands/`
- `config/*.php`
- `routes/*.php`
- `database/migrations/`
- `README.md`
- `docs/`
- `tests/Feature/` and `tests/Unit/`

## Examples

- After adding a new Artisan command, update the Boost skill with when to run it, required options, expected output, and any related config.
- After adding config and publish tags, update the Boost skill with publish commands and the minimum config keys users need to set.
- After adding a facade or public class, update the Boost skill with one practical integration example and the test assertion a consuming app should use.

## Anti-Patterns

- Regenerating the Boost skill from docs alone without checking implementation.
- Documenting private classes, test helpers, workbench-only routes, or implementation details as consumer API.
- Adding speculative examples for features the package does not provide.
- Removing placeholder-safe metadata before the package has been configured.
