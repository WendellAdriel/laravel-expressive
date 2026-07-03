# Syncing generated classes

- [Introduction](#introduction)
- [Checking for drift](#checking-for-drift)
- [Checking every generated class](#checking-every-generated-class)
- [Understanding reports](#understanding-reports)
- [Writing safe updates](#writing-safe-updates)
- [When to update manually](#when-to-update-manually)

## Introduction

The `expressive:sync` command compares an existing Expressive class against the class that would be generated from the current Eloquent model shape. It is useful in CI or during refactors when generated classes should stay aligned with their models.

The command does not change files unless you pass `--write`.

## Checking for drift

Run `expressive:sync` with the Expressive class name and mapped model:

```shell
php artisan expressive:sync User --model="App\Models\User"
```

The `--model` option is required. You may also pass `--namespace` and `--suffix` when the target class should be resolved differently for a single run:

```shell
php artisan expressive:sync User \
    --model="App\Models\User" \
    --namespace="App\Data" \
    --suffix="Expressive"
```

The command returns a successful exit code when the class is in sync.

## Checking every generated class

Use `--all` to scan the configured Expressive namespace path and check every discovered Expressive class:

```shell
php artisan expressive:sync --all
```

This is useful in CI after model changes:

```shell
php artisan expressive:sync --all --no-interaction
```

The command discovers classes under `expressive.namespace`, skips non-Expressive classes, resolves each mapped model, and prints a final summary:

```text
Checked: 12
In sync: 11
Drifted: 1
Missing: 0
Updated: 0
```

Pass `--namespace` and `--suffix` when the check should use different lookup rules for one run:

```shell
php artisan expressive:sync --all --namespace="App\Data" --suffix="Data"
```

Do not pass a class name or `--model` with `--all`. Those options are for single-class checks.

## Understanding reports

When drift is detected, the command prints one line per difference. Reports include the difference kind, model class, Expressive class, property, mapped key, expected type, actual type, and a suggestion.

Common difference kinds include:

| Difference | Meaning |
| --- | --- |
| `missing_attribute` | The generated shape expects an attribute property that is not present. |
| `stale_attribute` | The class contains an attribute property that no longer appears in the generated shape. |
| `missing_relationship` | The generated shape expects a relationship property that is not present. |
| `stale_relationship` | The class contains a relationship property that no longer appears in the generated shape. |
| `invalid_type` | The property exists, but its declared type differs from the generated type. |

Use these reports as review input. A difference may be intentional if the Expressive class has been customized beyond the generated model shape.

## Writing safe updates

Pass `--write` when you want Expressive to update a safely generated class:

```shell
php artisan expressive:sync User --model="App\Models\User" --write
```

Write mode replaces the class only when Expressive determines that the existing file is still safe to regenerate. This is intended for generated classes that have not been meaningfully edited by hand.

You may combine `--write` with `--all` when you want CI or a local refactor to rewrite every safe generated class:

```shell
php artisan expressive:sync --all --write
```

The command still refuses unsafe rewrites and returns a failing exit code when drift remains, a mapped model cannot be resolved, or a customized class needs manual review.

## When to update manually

If the command detects user edits, it refuses the rewrite and prints an error. Update the class manually when it contains custom methods, custom property decisions, or other changes that the generator should not overwrite.

You may also regenerate with `make:expressive --force` after reviewing the current class and deciding that overwriting it is correct.
