<?php

namespace Atldays\JoinRelation\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Publisher extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'network_id',
        'name',
        'active',
        'deleted_at',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class, 'network_id');
    }
}
