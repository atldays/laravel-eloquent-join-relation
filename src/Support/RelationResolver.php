<?php

namespace Atldays\JoinRelation\Support;

use Atldays\JoinRelation\Data\JoinRelationConfig;
use Atldays\JoinRelation\Data\ResolvedRelation;
use Atldays\JoinRelation\Exceptions\InvalidJoinRelationConfigurationException;
use Atldays\JoinRelation\Exceptions\RelationNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class RelationResolver
{
    public function resolve(Model $model, JoinRelationConfig $config): ResolvedRelation
    {
        if ($config->relation !== null) {
            return $this->resolveFromRelationPath($model, $config);
        }

        if ($config->related === null) {
            throw InvalidJoinRelationConfigurationException::missingRelationOrRelated();
        }

        if ($config->hydrate === null) {
            throw InvalidJoinRelationConfigurationException::manualJoinRequiresHydrate();
        }

        /** @var Model $related */
        $related = new $config->related;
        $columns = $this->resolveColumns($related, $config->columns);

        return new ResolvedRelation(
            alias: Str::snake(Str::singular($related->getTable())),
            name: null,
            relation: null,
            related: $related,
            columns: $columns,
        );
    }

    protected function resolveFromRelationPath(Model $model, JoinRelationConfig $config): ResolvedRelation
    {
        $segments = explode('.', $config->relation);
        $currentModel = $model;
        $resolvedRelation = null;

        foreach ($segments as $segment) {
            if (!method_exists($currentModel, $segment)) {
                throw RelationNotFoundException::forModel($currentModel, $segment);
            }

            $relation = $currentModel->{$segment}();

            if (!$relation instanceof Relation) {
                throw RelationNotFoundException::forModel($currentModel, $segment);
            }

            $resolvedRelation = $relation;
            $currentModel = $relation->getRelated();
        }

        $columns = $this->resolveColumns($currentModel, $config->columns);

        return new ResolvedRelation(
            alias: str_replace('.', '_', $config->relation),
            name: end($segments) ?: null,
            relation: $resolvedRelation,
            related: $currentModel,
            columns: $columns,
            segments: $segments,
        );
    }

    /**
     * @param  string[]  $columns
     * @return string[]
     */
    protected function resolveColumns(Model $related, array $columns): array
    {
        if ($columns === []) {
            $columns = array_merge([$related->getKeyName()], $related->getFillable());
        }

        return array_values(array_unique(array_merge([$related->getKeyName()], $columns)));
    }
}
