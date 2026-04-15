# Laravel Eloquent Join Relation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/atldays/laravel-eloquent-join-relation.svg?logo=packagist&style=for-the-badge)](https://packagist.org/packages/atldays/laravel-eloquent-join-relation)
[![Total Downloads](https://img.shields.io/packagist/dt/atldays/laravel-eloquent-join-relation.svg?style=for-the-badge&color=blue)](https://packagist.org/packages/atldays/laravel-eloquent-join-relation)
[![CI](https://img.shields.io/github/actions/workflow/status/atldays/laravel-eloquent-join-relation/ci.yml?style=for-the-badge&label=CI)](https://github.com/atldays/laravel-eloquent-join-relation/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE.md)

Laravel is great at working with relations, but as soon as a query becomes more complex and starts using `join`, loading relations through `with()` or lazy loading often leads to additional queries. The required data was already returned by the main SQL query.

This package solves that problem by letting you keep a single SQL query with `join` while still getting fully hydrated relations, as if they had been loaded through Eloquent in the usual way.

If you already joined a related table for filtering, sorting, or conditional checks, the package can build the relation directly from that joined data and set it on the model without touching the database again.

It is especially useful in complex queries that involve multiple related tables, where you still want to work with them afterward as normal nested Laravel relations.

## Highlights

- Hydrates `BelongsTo` and `HasOne` relations directly from data returned by `join`.
- Lets you work with joined data as normal Eloquent relations without extra SQL queries.
- Supports nested relation paths such as `author.team.organization`.
- Supports manual hydration for custom join scenarios through `hydrate`.
- Correctly returns `null` for missing records on `left join`.
- Fails explicitly when nested relation paths are joined out of order.
- Especially useful for complex queries that filter across multiple related tables.

## Support

- PHP: `8.2+`
- Laravel: `11.x`, `12.x`, `13.x`

## Current boundaries

- Supported relation types:
  - `BelongsTo`
  - `HasOne`
- Not supported yet:
  - `HasMany`
  - `BelongsToMany`
  - `MorphTo`, `MorphOne`, `MorphMany`
  - `HasOneThrough`
- Nested relation paths must be called in order:
  - first `author`
  - then `author.team`
  - then `author.team.organization`

## Installation

```bash
composer require atldays/laravel-eloquent-join-relation
```

## Basic usage

If you already join a related table, you can hydrate that relation without an extra query.

```php
use Atldays\JoinRelation\HasJoinRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasJoinRelation;

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
```

```php
$posts = Post::query()
    ->select('posts.*')
    ->joinRelation(
        relation: 'author',
        columns: ['id', 'name', 'email'],
    )
    ->where('authors.active', true)
    ->get();

$post = $posts->first();

$post->author; // already hydrated, no extra query
```

The generated SQL will look roughly like this:

```sql
select
    `posts`.*,
    `authors`.`id` as `join_author_id`,
    `authors`.`name` as `join_author_name`,
    `authors`.`email` as `join_author_email`
from `posts`
inner join `authors`
    on `authors`.`id` = `posts`.`author_id`
where `authors`.`active` = 1
```

That means:

- one SQL query
- hydrated `$post->author`
- no follow-up query when you access the relation

## Nested relation paths

For common `BelongsTo` chains, you can hydrate nested relations step by step.

```php
$posts = Post::query()
    ->select('posts.*')
    ->joinRelation(
        relation: 'author',
        columns: ['id', 'team_id', 'name'],
    )
    ->joinRelation(
        relation: 'author.team',
        columns: ['id', 'organization_id', 'name'],
    )
    ->joinRelation(
        relation: 'author.team.organization',
        columns: ['id', 'name'],
    )
    ->where('posts.published', true)
    ->where('authors.active', true)
    ->where('teams.active', true)
    ->where('organizations.active', true)
    ->get();

$post = $posts->first();

$post->author;
$post->author->team;
$post->author->team->organization;
```

The generated SQL will look roughly like this:

```sql
select
    `posts`.*,
    `authors`.`id` as `join_author_id`,
    `authors`.`team_id` as `join_author_team_id`,
    `authors`.`name` as `join_author_name`,
    `teams`.`id` as `join_author_team_id`,
    `teams`.`organization_id` as `join_author_team_organization_id`,
    `teams`.`name` as `join_author_team_name`,
    `organizations`.`id` as `join_author_team_organization_id`,
    `organizations`.`name` as `join_author_team_organization_name`
from `posts`
inner join `authors`
    on `authors`.`id` = `posts`.`author_id`
inner join `teams`
    on `teams`.`id` = `authors`.`team_id`
inner join `organizations`
    on `organizations`.`id` = `teams`.`organization_id`
where `posts`.`published` = 1
  and `authors`.`active` = 1
  and `teams`.`active` = 1
  and `organizations`.`active` = 1
```

Important:

- `author` must be joined before `author.team`
- `author.team` must be joined before `author.team.organization`

If you skip an earlier level, the package throws an exception instead of silently falling back to lazy loading.

## Left joins and nullable relations

For optional relations, use `type: 'left'`.

```php
$posts = Post::query()
    ->select('posts.*')
    ->joinRelation(
        relation: 'author',
        type: 'left',
        columns: ['id', 'name'],
    )
    ->get();

$post = $posts->first();

$post->author; // User model or null
```

The generated SQL will look roughly like this:

```sql
select
    `posts`.*,
    `authors`.`id` as `join_author_id`,
    `authors`.`name` as `join_author_name`
from `posts`
left join `authors`
    on `authors`.`id` = `posts`.`author_id`
```

The same applies to nested paths:

```php
Post::query()
    ->select('posts.*')
    ->joinRelation(relation: 'author', columns: ['id', 'team_id', 'name'])
    ->joinRelation(
        relation: 'author.team',
        type: 'left',
        columns: ['id', 'organization_id', 'name'],
    )
    ->get();
```

The generated SQL will look roughly like this:

```sql
select
    `posts`.*,
    `authors`.`id` as `join_author_id`,
    `authors`.`team_id` as `join_author_team_id`,
    `authors`.`name` as `join_author_name`,
    `teams`.`id` as `join_author_team_id`,
    `teams`.`organization_id` as `join_author_team_organization_id`,
    `teams`.`name` as `join_author_team_name`
from `posts`
inner join `authors`
    on `authors`.`id` = `posts`.`author_id`
left join `teams`
    on `teams`.`id` = `authors`.`team_id`
```

If the joined `team` row is missing, `author->team` becomes `null`.

## Manual hydrate mode

When the join is custom, or when you want to put the hydrated model somewhere non-standard, use `related + join + hydrate`.

This is especially useful when you already joined several tables and want to attach a model deeper in the tree yourself.

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;

$posts = Post::query()
    ->select('posts.*')
    ->joinRelation(
        relation: 'author',
        columns: ['id', 'name', 'email'],
    )
    ->joinRelation(
        related: Profile::class,
        type: 'left',
        join: function (JoinClause $join): void {
            $join->on('authors.id', '=', 'profiles.user_id');
        },
        hydrate: function (Model $model, ?Profile $profile): void {
            $model->author?->setRelation('profile', $profile);
        },
        columns: ['id', 'user_id', 'bio'],
    )
    ->get();

$post = $posts->first();

$post->author->profile;
```

The generated SQL will look roughly like this:

```sql
select
    `posts`.*,
    `authors`.`id` as `join_author_id`,
    `authors`.`name` as `join_author_name`,
    `authors`.`email` as `join_author_email`,
    `profiles`.`id` as `join_profile_id`,
    `profiles`.`user_id` as `join_profile_user_id`,
    `profiles`.`bio` as `join_profile_bio`
from `posts`
inner join `authors`
    on `authors`.`id` = `posts`.`author_id`
left join `profiles`
    on `authors`.`id` = `profiles`.`user_id`
```

If the profile is missing on a `left join`, the callback receives `null`.

## Advanced example

Here is the same style of query for a deeper chain where every level is required.

```php
$posts = Post::query()
    ->select('posts.*')
    ->joinRelation(
        relation: 'author',
        type: 'inner',
        columns: ['id', 'team_id', 'name', 'active', 'deleted_at'],
    )
    ->joinRelation(
        relation: 'author.team',
        type: 'inner',
        columns: ['id', 'organization_id', 'name', 'active', 'deleted_at'],
    )
    ->joinRelation(
        relation: 'author.team.organization',
        type: 'inner',
        columns: ['id', 'name', 'active', 'deleted_at'],
    )
    ->where('posts.active', true)
    ->whereNull('posts.deleted_at')
    ->where('authors.active', true)
    ->whereNull('authors.deleted_at')
    ->where('teams.active', true)
    ->whereNull('teams.deleted_at')
    ->where('organizations.active', true)
    ->whereNull('organizations.deleted_at')
    ->get();
```

This gives you one SQL query and fully hydrated nested relations with no follow-up lazy-loading queries.

The resulting SQL will look roughly like this:

```sql
select
    `posts`.*,
    `authors`.`id` as `join_author_id`,
    `authors`.`team_id` as `join_author_team_id`,
    `authors`.`name` as `join_author_name`,
    `authors`.`active` as `join_author_active`,
    `authors`.`deleted_at` as `join_author_deleted_at`,
    `teams`.`id` as `join_author_team_id`,
    `teams`.`organization_id` as `join_author_team_organization_id`,
    `teams`.`name` as `join_author_team_name`,
    `teams`.`active` as `join_author_team_active`,
    `teams`.`deleted_at` as `join_author_team_deleted_at`,
    `organizations`.`id` as `join_author_team_organization_id`,
    `organizations`.`name` as `join_author_team_organization_name`,
    `organizations`.`active` as `join_author_team_organization_active`,
    `organizations`.`deleted_at` as `join_author_team_organization_deleted_at`
from `posts`
inner join `authors`
    on `authors`.`id` = `posts`.`author_id`
inner join `teams`
    on `teams`.`id` = `authors`.`team_id`
inner join `organizations`
    on `organizations`.`id` = `teams`.`organization_id`
where `posts`.`active` = 1
  and `posts`.`deleted_at` is null
  and `authors`.`active` = 1
  and `authors`.`deleted_at` is null
  and `teams`.`active` = 1
  and `teams`.`deleted_at` is null
  and `organizations`.`active` = 1
  and `organizations`.`deleted_at` is null
```

That is the main point of the package:

- you keep the join-heavy query you already need
- you still get normal nested Eloquent relations
- you do it with one SQL query instead of a join plus follow-up eager-load queries

## What you save compared to `with()`

For queries that already depend on joins, a classic eager-loading approach often turns into:

1. one query for the root records
2. one query for `author`
3. one query for `author.team`
4. one query for `author.team.organization`

With `joinRelation(...)`, those joined records are hydrated from the same SQL result set, so relation access does not need those extra follow-up queries.

## How it differs from `with()`

`with()` is still great when you want classic eager loading.

This package is useful when:

- you already need SQL joins for filtering or sorting
- you want to avoid follow-up relation queries
- you still want to work with normal Eloquent relation objects

## Testing status

The package test suite covers:

- direct `BelongsTo`
- direct `HasOne`
- nested relation paths
- manual hydrate mode
- `left join => null`
- ordered nested path enforcement
- no lazy-loading fallback for hydrated relations
