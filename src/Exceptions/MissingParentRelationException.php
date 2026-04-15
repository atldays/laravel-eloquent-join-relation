<?php

namespace Atldays\JoinRelation\Exceptions;

class MissingParentRelationException extends JoinRelationException
{
    public static function forPath(string $path, string $missingSegment): self
    {
        return new self(sprintf(
            'joinRelation() path [%s] requires previously loaded parent relation [%s].',
            $path,
            $missingSegment,
        ));
    }
}
