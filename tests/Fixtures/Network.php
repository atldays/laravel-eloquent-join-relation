<?php

namespace Atldays\JoinRelation\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'active',
        'deleted_at',
    ];
}
