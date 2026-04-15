<?php

namespace Atldays\JoinRelation\Data;

use Closure;
use Illuminate\Database\Eloquent\Model;

readonly class JoinRelationConfig
{
    /**
     * @param  class-string<Model>|null  $related
     * @param  string[]  $columns
     */
    public function __construct(
        public ?string $relation = null,
        public ?string $related = null,
        public ?Closure $hydrate = null,
        public ?Closure $join = null,
        public string $type = 'inner',
        public array $columns = [],
        public ?bool $nullable = null,
    ) {}

    public function isNullable(): bool
    {
        return $this->nullable ?? $this->type === 'left';
    }
}
