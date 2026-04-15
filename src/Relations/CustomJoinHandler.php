<?php

namespace Atldays\JoinRelation\Relations;

use Atldays\JoinRelation\Data\JoinRelationConfig;
use Atldays\JoinRelation\Data\ResolvedRelation;
use Atldays\JoinRelation\Exceptions\InvalidJoinRelationConfigurationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class CustomJoinHandler implements RelationHandler
{
    public function applyJoin(
        Builder $query,
        ResolvedRelation $resolvedRelation,
        JoinRelationConfig $config,
    ): void {
        if ($config->join === null) {
            throw InvalidJoinRelationConfigurationException::manualJoinRequiresJoin();
        }

        $query->join(
            $resolvedRelation->relatedTable(),
            fn (JoinClause $join) => ($config->join)($join),
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
