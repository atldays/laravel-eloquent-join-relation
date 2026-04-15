<?php

namespace Atldays\JoinRelation\Relations;

use Atldays\JoinRelation\Data\JoinRelationConfig;
use Atldays\JoinRelation\Data\ResolvedRelation;
use Illuminate\Database\Eloquent\Builder;

interface RelationHandler
{
    public function applyJoin(
        Builder $query,
        ResolvedRelation $resolvedRelation,
        JoinRelationConfig $config,
    ): void;

    public function applySelect(Builder $query, ResolvedRelation $resolvedRelation): void;
}
