<?php

namespace Atldays\JoinRelation\Exceptions;

class InvalidJoinRelationConfigurationException extends JoinRelationException
{
    public static function missingRelationOrRelated(): self
    {
        return new self('joinRelation() requires either [relation] or [related].');
    }

    public static function manualJoinRequiresHydrate(): self
    {
        return new self('joinRelation() manual mode requires a [hydrate] callback.');
    }

    public static function manualJoinRequiresJoin(): self
    {
        return new self('joinRelation() manual mode requires a custom [join] callback.');
    }
}
