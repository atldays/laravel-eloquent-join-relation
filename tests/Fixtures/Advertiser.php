<?php

namespace Atldays\JoinRelation\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Advertiser extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'publisher_id',
        'source_publisher_id',
        'name',
        'active',
        'deleted_at',
    ];

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class, 'publisher_id');
    }

    public function sourcePublisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class, 'source_publisher_id');
    }
}
