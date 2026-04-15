<?php

namespace Atldays\JoinRelation\Hydration;

use Atldays\JoinRelation\Data\JoinRelationConfig;
use Atldays\JoinRelation\Data\ResolvedRelation;
use Atldays\JoinRelation\Exceptions\MissingParentRelationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class RelationHydrator
{
    /**
     * @param  Collection<int, Model>  $models
     * @return Collection<int, Model>
     */
    public function hydrate(
        Collection $models,
        ResolvedRelation $resolvedRelation,
        JoinRelationConfig $config,
    ): Collection {
        return $models->each(function (Model $model) use ($resolvedRelation, $config): void {
            $attributes = [];

            foreach ($resolvedRelation->columns as $column) {
                $attributes[$column] = $model->getAttributeValue($resolvedRelation->aliasFor($column));
            }

            $key = $attributes[$resolvedRelation->keyColumn()] ?? null;
            $related = null;

            if (!($key === null && $config->isNullable())) {
                $related = $resolvedRelation->related->newInstance([], $key !== null);
                $related->forceFill($attributes);
            }

            if ($config->hydrate !== null) {
                ($config->hydrate)($model, $related);

                return;
            }

            $this->attachResolvedRelation($model, $resolvedRelation, $related);
        });
    }

    protected function attachResolvedRelation(Model $model, ResolvedRelation $resolvedRelation, ?Model $related): void
    {
        if ($resolvedRelation->name === null) {
            return;
        }

        if (!$resolvedRelation->isNested()) {
            $model->setRelation($resolvedRelation->name, $related);

            return;
        }

        $parent = $model;
        $walkedSegments = [];

        foreach ($resolvedRelation->parentSegments() as $segment) {
            $walkedSegments[] = $segment;

            if (!$parent->relationLoaded($segment) || $parent->getRelation($segment) === null) {
                throw MissingParentRelationException::forPath(
                    $resolvedRelation->path() ?? $resolvedRelation->name,
                    implode('.', $walkedSegments),
                );
            }

            /** @var Model $parent */
            $parent = $parent->getRelation($segment);
        }

        $parent->setRelation($resolvedRelation->name, $related);
    }
}
