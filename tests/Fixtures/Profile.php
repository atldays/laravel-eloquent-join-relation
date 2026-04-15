<?php

namespace Atldays\JoinRelation\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'bio',
    ];
}
