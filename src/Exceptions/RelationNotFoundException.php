<?php

namespace Atldays\JoinRelation\Exceptions;

use Illuminate\Database\Eloquent\Model;

class RelationNotFoundException extends JoinRelationException
{
    public static function forModel(Model $model, string $relation): self
    {
        return new self(sprintf(
            'Relation [%s] is not defined on model [%s].',
            $relation,
            $model::class,
        ));
    }
}
