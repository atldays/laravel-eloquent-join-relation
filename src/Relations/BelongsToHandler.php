<?php

namespace Atldays\JoinRelation\Relations;

use Atldays\JoinRelation\Data\JoinRelationConfig;
use Atldays\JoinRelation\Data\ResolvedRelation;
use Atldays\JoinRelation\Exceptions\UnsupportedRelationTypeException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\JoinClause;

class BelongsToHandler implements RelationHandler
{
    public function applyJoin(
        Builder $query,
        ResolvedRelation $resolvedRelation,
        JoinRelationConfig $config,
    ): void {
        $relation = $resolvedRelation->relation;

        if (!$relation instanceof BelongsTo) {
            throw UnsupportedRelationTypeException::forRelation($resolvedRelation->name, $relation);
        }

        $query->join(
            $resolvedRelation->relatedTable(),
            function (JoinClause $join) use ($relation, $config): void {
                $join->on(
                    $relation->getQualifiedOwnerKeyName(),
                    '=',
                    $relation->getQualifiedForeignKeyName(),
                );

                if ($config->join !== null) {
                    ($config->join)($join);
                }
            },
            type: $config->type,
        );
    }

    public function applySelect(Builder $query, ResolvedRelation $resolvedRelation): void
    {
        $query->addSelect(array_map(
            fn (string $column): string => sprintf(
                '%s.%s as %s',
                $resolvedRelation->relatedTable(),
                $column,
                $resolvedRelation->aliasFor($column),
            ),
            $resolvedRelation->columns,
        ));
    }
}
