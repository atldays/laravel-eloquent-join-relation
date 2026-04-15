# Contributing

Thanks for considering a contribution to this package.

The goal of this project is to make join-heavy Eloquent queries feel natural to work with after execution. If a query already joins related tables for filtering, sorting, or validation, this package should make it possible to hydrate those related models directly from the same SQL result without follow-up relation queries.

## Project vision

This package focuses on a narrow, predictable problem:

- hydrate relations from already joined SQL data
- preserve familiar Eloquent developer experience
- avoid extra queries caused by `with()` or lazy loading in join-driven queries
- stay explicit and predictable instead of supporting every possible relation shape

At the moment, the package is intentionally focused on:

- `BelongsTo`
- `HasOne`
- nested relation paths, when joined in order
- manual `hydrate` mode for custom join scenarios

## What we want to improve

Contributions are especially welcome around:

- documentation and examples
- test coverage for real-world query scenarios
- API clarity and developer experience
- reliability around edge cases for supported relation types
- performance and predictability of hydration

Before adding support for new relation types, please keep the package direction in mind: we prefer correctness and explicitness over broad but fragile magic.

## Development workflow

Install dependencies:

```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD:/app" -w /app composer:2 sh -lc 'composer install'
```

Run tests:

```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD:/app" -w /app composer:2 sh -lc 'composer test'
```

Check formatting:

```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD:/app" -w /app composer:2 sh -lc 'composer format:test'
```

Fix formatting:

```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD:/app" -w /app composer:2 sh -lc 'composer format'
```

If you want to enable the repository git hooks:

```bash
git config core.hooksPath .githooks
```

## Commit message format

This repository uses **Conventional Commits**.

Please write commit messages in this format:

```text
<type>(optional-scope): <description>
```

Examples:

```text
feat(relation): add nested path hydration support
fix(hasone): return null for missing left join match
docs(readme): add SQL examples for advanced usage
test(ci): cover lazy-loading fallback protection
```

Common commit types:

- `feat`
- `fix`
- `docs`
- `refactor`
- `test`
- `chore`
- `ci`
- `perf`
- `style`

## Pull request expectations

When opening a pull request, please try to:

- describe the problem being solved
- explain the intended behavior change
- include or update tests
- update documentation when the public API or expected behavior changes
- keep changes focused and scoped

## Notes on API changes

This package is still evolving, but API changes should stay aligned with the core idea:

- hydrate relations from joined data
- avoid hidden fallback queries
- keep nested behavior explicit
- fail loudly when a query shape is unsupported or invalid

If you plan to propose a larger API or architectural change, opening an issue or draft PR first is appreciated.
