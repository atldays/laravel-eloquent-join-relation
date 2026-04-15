<?php

namespace Atldays\JoinRelation\Tests\Fixtures;

use Atldays\JoinRelation\HasJoinRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    use HasJoinRelation;

    public $timestamps = false;

    protected $fillable = [
        'advertiser_id',
        'name',
        'active',
        'deleted_at',
    ];

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class, 'advertiser_id');
    }
}
