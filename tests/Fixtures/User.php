<?php

namespace Atldays\JoinRelation\Tests\Fixtures;

use Atldays\JoinRelation\HasJoinRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    use HasJoinRelation;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'email',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'user_id');
    }
}
