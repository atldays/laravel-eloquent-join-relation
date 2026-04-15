<?php

namespace Atldays\JoinRelation;

use Atldays\JoinRelation\Data\JoinRelationConfig;
use Atldays\JoinRelation\Data\ResolvedRelation;
use Atldays\JoinRelation\Exceptions\MissingParentRelationException;
use Atldays\JoinRelation\Hydration\RelationHydrator;
use Atldays\JoinRelation\Relations\BelongsToHandler;
use Atldays\JoinRelation\Relations\CustomJoinHandler;
use Atldays\JoinRelation\Relations\HasOneHandler;
use Atldays\JoinRelation\Relations\RelationHandler;
use Atldays\JoinRelation\Support\RelationResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;

class JoinRelationAction
{
    /**
     * @param  Builder<Model>  $query
     */
    public function __construct(
        protected Builder $query,
        protected Model $model,
        protected JoinRelationConfig $config,
    ) {}

    public function execute(): void
    {
        $resolvedRelation = (new RelationResolver)->resolve($this->model, $this->config);
        $handler = $this->resolveHandler($resolvedRelation->relation);

        $this->assertOrderedPathCanBeJoined($resolvedRelation);

        $handler->applyJoin($this->query, $resolvedRelation, $this->config);
        $handler->applySelect($this->query, $resolvedRelation);

        $this->query->afterQuery(
            fn ($models) => (new RelationHydrator)->hydrate($models, $resolvedRelation, $this->config)
        );
    }

    protected function resolveHandler(?Relation $relation): RelationHandler
    {
        return match (true) {
            $relation === null => new CustomJoinHandler,
            $relation instanceof HasOne => new HasOneHandler,
            default => new BelongsToHandler,
        };
    }

    protected function assertOrderedPathCanBeJoined(ResolvedRelation $resolvedRelation): void
    {
        if (!$resolvedRelation->isNested() || $resolvedRelation->relation === null) {
            return;
        }

        $parentTable = $resolvedRelation->relation->getParent()->getTable();
        $joins = $this->query->getQuery()->joins ?? [];

        foreach ($joins as $join) {
            if ($join->table === $parentTable) {
                return;
            }
        }

        throw MissingParentRelationException::forPath(
            $resolvedRelation->path() ?? $resolvedRelation->name ?? $parentTable,
            $resolvedRelation->parentPath() ?? $parentTable,
        );
    }
}
