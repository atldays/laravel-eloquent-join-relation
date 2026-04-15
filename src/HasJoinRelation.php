<?php

namespace Atldays\JoinRelation;

use Atldays\JoinRelation\Data\JoinRelationConfig;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasJoinRelation
{
    /**
     * @param  Builder<Model>  $query
     * @param  string[]  $columns
     */
    public function scopeJoinRelation(
        Builder $query,
        ?string $relation = null,
        ?string $related = null,
        ?Closure $hydrate = null,
        ?Closure $join = null,
        string $type = 'inner',
        array $columns = [],
        ?bool $nullable = null
    ): void {
        $config = new JoinRelationConfig(
            relation: $relation,
            related: $related,
            hydrate: $hydrate,
            join: $join,
            type: $type,
            columns: $columns,
            nullable: $nullable,
        );

        (new JoinRelationAction($query, $this, $config))->execute();
    }
}
