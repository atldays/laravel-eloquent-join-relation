<?php

namespace Atldays\JoinRelation\Exceptions;

use Illuminate\Database\Eloquent\Relations\Relation;

class UnsupportedRelationTypeException extends JoinRelationException
{
    public static function forRelation(string $name, Relation $relation): self
    {
        return new self(sprintf(
            'Relation [%s] with type [%s] is not supported by joinRelation().',
            $name,
            $relation::class,
        ));
    }
}
