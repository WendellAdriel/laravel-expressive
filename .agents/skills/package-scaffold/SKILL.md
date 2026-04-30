---
name: package-scaffold
description: "Use this skill when adding Laravel package capabilities or package shape changes after creating a package from this starter: commands, migrations, routes, config, views, assets, middleware, workbench files, or new publishable resources."
license: MIT
metadata:
  author: laravel
---

# Package Scaffold

## Primary Goal

Add package features in the right place while keeping package names, namespaces, config keys, and publish tags consistent.

## Workflow

1. Inspect the existing package structure, sibling examples, README setup notes, and current service provider before creating files; in short, inspect the existing package structure and sibling examples first.
2. Identify whether the request touches commands, migrations, routes, config, views, assets, package shape changes, tests, docs, compatibility, or release flow.
3. Use Laravel-native package conventions and keep namespaces, publish tags, docs, config keys, URLs, and badges consistent with the configured package.
4. Use `package-service-provider` for provider wiring, `package-testing` for coverage, `package-docs` for docs, `package-compatibility` for matrix-sensitive changes, and `package-release` for release tasks.
5. If this starter has not been configured yet, preserve `configure.php` mappings when adding or removing selectable capabilities so opt-out cleanup stays accurate.
6. Add only the files needed for the requested capability and validate with the narrowest relevant command before broader checks.

## References

- `src/*ServiceProvider.php`
- `config/*.php`
- `routes/*.php`
- `resources/views/`
- `database/migrations/`
- `public/`
- `tests/Feature/` and `tests/Unit/`

## Examples

- Add an Artisan command: create the command class under `src/Console/Commands`, register it in the provider, add a feature test for observable console output, and document the command if it is user-facing.
- Add a publishable migration: place the migration in `database/migrations`, wire it through the provider with a `expressive-migrations` tag, and test publish behavior with Testbench.

## Anti-Patterns

- Adding unused scaffolding because a package might need it later.
- Mixing old placeholder names with the configured package name.
- Avoid changing dependencies without approval.
- Replacing explicit Laravel package code with a helper abstraction when one feature-specific change would do.
