---
name: expressive-development
description: "Use this skill when changing this Laravel package expressive repository itself: placeholders, configure flow, starter defaults, root guidance, local skills, temporary phase tests, dist hygiene, or scaffold-wide conventions. Do not use for ordinary package feature work after a package has been configured."
license: MIT
metadata:
  author: laravel
---

# Expressive Development

## Primary Goal

Evolve the starter kit without making it less useful for future package authors.

## Workflow

1. Preserve generic placeholders such as `Wendell Adriel`, `Expressive`, `wendelladriel`, and `expressive` until the configure flow replaces them.
2. Keep starter guidance minimal and split expressive-maintenance rules from package-author rules.
3. Use temporary phase scaffold tests when proving repository shape, file parity, or generated scaffolding; delete those tests before final validation.
4. Keep `.claude/skills` and `.agents/skills` mirrored when adding or editing local skills.
5. Keep `configure.php` feature and tool pruning maps aligned with the service provider, Composer metadata, README sections, docs, AI guidance, skills, and publishable files.
6. Keep development-time authoring files out of Composer dist archives with `.gitattributes` when they are not runtime package files.

## References

- `AGENTS.md`
- `CLAUDE.md`
- `.agents/skills/`
- `.claude/skills/`
- `configure.php`
- `.gitattributes`
- `PLAN.md`, `REQS.md`, and `phases/` when present

## Examples

- Add or rename a local skill by updating both agent ecosystems, front matter names, root guidance, and any scaffold consistency checks.
- Add temporary Phase tests to lock down generated file shape, run them to prove the change, then delete them before the final `composer test`.
- Update placeholder-sensitive docs by preserving delete-fence content that should disappear after configuration.
- When adding a selectable package feature, add both the starter files and the matching configure pruning behavior so package authors can opt out cleanly.

## Anti-Patterns

- Leaving temporary phase tests in the shipped starter suite.
- Putting expressive-maintenance rules into package-facing skills.
- Replacing placeholders with one real package name in starter files.
- Adding runtime dependencies for repository-maintenance convenience.
