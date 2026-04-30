# Package Expressive

This repository is a Laravel package expressive for building new packages. Preserve idiomatic Laravel package patterns, keep placeholders generic, and prefer the smallest package-specific change that keeps the expressive useful after configuration.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep `Wendell Adriel`, `Expressive`, `wendelladriel`, and `expressive` placeholders intact until the package is configured.
- Treat `configure.php` as a one-time bootstrap script: package authors run `composer install` and then `php ./configure.php` to replace placeholders, prune disabled features/tools, optionally create a GitHub repository, and delete the script after success.
- When changing package capabilities or maintenance tooling in the expressive, keep the configure feature/tool mappings in sync with files, Composer metadata, README copy, AI guidance, and local skills.
- Do not add runtime dependencies, generated files, or extra scaffold unless the package feature needs them.
- Keep temporary phase scaffold tests out of the final shipped test suite.

## Quick Commands

- Full validation: `composer test`
- Formatting check: `composer lint:check`
- Static analysis: `composer analyse`
- Pest tests: `composer test:unit`
- Workbench build: `composer build`
- Workbench server: `composer serve`

## Local Skills

- `package-scaffold`: use when adding package capabilities, files, or package shape changes.
- `package-service-provider`: use when wiring config, routes, views, translations, migrations, assets, commands, or package metadata.
- `package-testing`: use when adding or changing package tests with Pest 4 and Orchestra Testbench.
- `package-release`: use when preparing changelog, release notes, tags, or GitHub release workflow changes.
- `package-compatibility`: use when reviewing code, dependencies, or CI against the PHP and Laravel support matrix.
- `package-docs`: use when writing README, MkDocs, contributing, upgrade, or usage documentation.
- `package-generate-skill`: use when updating the bundled Boost skill from the package implementation and docs.
- `expressive-development`: use when changing this expressive repository itself, including placeholders, configure flow, temporary phase tests, or scaffold-wide conventions.
