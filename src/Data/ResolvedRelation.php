<?php

namespace Atldays\JoinRelation\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

readonly class ResolvedRelation
{
    /**
     * @param  string[]  $columns
     * @param  string[]  $segments
     */
    public function __construct(
        public string $alias,
        public ?string $name,
        public ?Relation $relation,
        public Model $related,
        public array $columns,
        public array $segments = [],
    ) {}

    public function keyColumn(): string
    {
        return $this->related->getKeyName();
    }

    public function relatedTable(): string
    {
        return $this->related->getTable();
    }

    public function aliasFor(string $column): string
    {
        return 'join_' . $this->alias . '_' . $column;
    }

    public function isNested(): bool
    {
        return count($this->segments) > 1;
    }

    public function parentSegments(): array
    {
        return array_slice($this->segments, 0, -1);
    }

    public function path(): ?string
    {
        if ($this->segments === []) {
            return null;
        }

        return implode('.', $this->segments);
    }

    public function parentPath(): ?string
    {
        $segments = $this->parentSegments();

        if ($segments === []) {
            return null;
        }

        return implode('.', $segments);
    }
}
