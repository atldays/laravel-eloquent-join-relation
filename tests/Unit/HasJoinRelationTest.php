<?php

namespace Atldays\JoinRelation\Tests\Unit;

use Atldays\JoinRelation\Exceptions\InvalidJoinRelationConfigurationException;
use Atldays\JoinRelation\Exceptions\MissingParentRelationException;
use Atldays\JoinRelation\Exceptions\RelationNotFoundException;
use Atldays\JoinRelation\Exceptions\UnsupportedRelationTypeException;
use Atldays\JoinRelation\Tests\Fixtures\Advertiser;
use Atldays\JoinRelation\Tests\Fixtures\Network;
use Atldays\JoinRelation\Tests\Fixtures\Offer;
use Atldays\JoinRelation\Tests\Fixtures\Post;
use Atldays\JoinRelation\Tests\Fixtures\Profile;
use Atldays\JoinRelation\Tests\Fixtures\Publisher;
use Atldays\JoinRelation\Tests\Fixtures\User;
use Atldays\JoinRelation\Tests\TestCase;
use Illuminate\Database\Eloquent\LazyLoadingViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class HasJoinRelationTest extends TestCase
{
    public function test_it_joins_a_belongs_to_relation_by_name(): void
    {
        $user = User::query()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        Post::query()->create([
            'user_id' => $user->id,
            'title' => 'Hello world',
        ]);

        $post = Post::query()
            ->select('posts.*')
            ->joinRelation(relation: 'author', columns: ['name', 'email'])
            ->firstOrFail();

        $this->assertTrue($post->relationLoaded('author'));
        $this->assertSame($user->id, $post->author->id);
        $this->assertSame('Jane Doe', $post->author->name);
        $this->assertSame('jane@example.com', $post->author->email);
    }

    public function test_it_sets_relation_to_null_for_missing_left_join_match(): void
    {
        Post::query()->create([
            'user_id' => null,
            'title' => 'Hello world',
        ]);

        $post = Post::query()
            ->select('posts.*')
            ->joinRelation(relation: 'author', type: 'left', columns: ['name'])
            ->firstOrFail();

        $this->assertTrue($post->relationLoaded('author'));
        $this->assertNull($post->author);
    }

    public function test_it_throws_for_unknown_relation(): void
    {
        $this->expectException(RelationNotFoundException::class);

        Post::query()
            ->select('posts.*')
            ->joinRelation(relation: 'missingRelation')
            ->first();
    }

    public function test_it_throws_for_unsupported_relation_type(): void
    {
        $this->expectException(UnsupportedRelationTypeException::class);

        User::query()
            ->select('users.*')
            ->joinRelation(relation: 'posts')
            ->first();
    }

    public function test_it_joins_a_has_one_relation_by_name(): void
    {
        $user = User::query()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        Profile::query()->create([
            'user_id' => $user->id,
            'bio' => 'Laravel developer',
        ]);

        $resolvedUser = User::query()
            ->select('users.*')
            ->joinRelation(relation: 'profile', columns: ['user_id', 'bio'])
            ->firstOrFail();

        $this->assertTrue($resolvedUser->relationLoaded('profile'));
        $this->assertSame($user->id, $resolvedUser->profile->user_id);
        $this->assertSame('Laravel developer', $resolvedUser->profile->bio);
    }

    public function test_it_sets_has_one_relation_to_null_for_missing_left_join_match(): void
    {
        User::query()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $resolvedUser = User::query()
            ->select('users.*')
            ->joinRelation(relation: 'profile', type: 'left', columns: ['user_id', 'bio'])
            ->firstOrFail();

        $this->assertTrue($resolvedUser->relationLoaded('profile'));
        $this->assertNull($resolvedUser->profile);
    }

    public function test_it_supports_manual_hydration_for_nested_joined_models(): void
    {
        $user = User::query()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $postId = Post::query()->create([
            'user_id' => $user->id,
            'title' => 'Hello world',
        ])->getKey();

        Profile::query()->create([
            'user_id' => $user->id,
            'bio' => 'Laravel developer',
        ]);

        $post = Post::query()
            ->select('posts.*')
            ->joinRelation(relation: 'author', columns: ['id', 'name', 'email'])
            ->joinRelation(
                related: Profile::class,
                hydrate: function (Model $model, ?Profile $profile): void {
                    $model->author?->setRelation('profile', $profile);
                },
                join: function (JoinClause $join): void {
                    $join->on('users.id', '=', 'profiles.user_id');
                },
                type: 'left',
                columns: ['id', 'user_id', 'bio'],
            )
            ->whereKey($postId)
            ->firstOrFail();

        $this->assertTrue($post->relationLoaded('author'));
        $this->assertTrue($post->author->relationLoaded('profile'));
        $this->assertSame('Laravel developer', $post->author->profile->bio);
    }

    public function test_it_requires_hydrate_callback_in_manual_mode(): void
    {
        $this->expectException(InvalidJoinRelationConfigurationException::class);

        Post::query()
            ->select('posts.*')
            ->joinRelation(
                related: Profile::class,
                join: fn (JoinClause $join) => $join->on('posts.user_id', '=', 'profiles.user_id'),
            )
            ->first();
    }

    public function test_it_supports_advanced_multi_join_hydration_with_filters(): void
    {
        $network = Network::query()->create([
            'name' => 'Primary Network',
            'active' => true,
            'deleted_at' => null,
        ]);

        $publisher = Publisher::query()->create([
            'network_id' => $network->id,
            'name' => 'Primary Publisher',
            'active' => true,
            'deleted_at' => null,
        ]);

        $advertiser = Advertiser::query()->create([
            'publisher_id' => $publisher->id,
            'name' => 'Primary Advertiser',
            'active' => true,
            'deleted_at' => null,
        ]);

        $matchingOffer = Offer::query()->create([
            'advertiser_id' => $advertiser->id,
            'name' => 'Primary Offer',
            'active' => true,
            'deleted_at' => null,
        ]);

        $disabledNetwork = Network::query()->create([
            'name' => 'Disabled Network',
            'active' => false,
            'deleted_at' => null,
        ]);

        $disabledPublisher = Publisher::query()->create([
            'network_id' => $disabledNetwork->id,
            'name' => 'Disabled Publisher',
            'active' => true,
            'deleted_at' => null,
        ]);

        $disabledAdvertiser = Advertiser::query()->create([
            'publisher_id' => $disabledPublisher->id,
            'name' => 'Disabled Advertiser',
            'active' => true,
            'deleted_at' => null,
        ]);

        Offer::query()->create([
            'advertiser_id' => $disabledAdvertiser->id,
            'name' => 'Filtered Offer',
            'active' => true,
            'deleted_at' => null,
        ]);

        $offers = Offer::query()
            ->select('offers.*')
            ->joinRelation(
                relation: 'advertiser',
                type: 'inner',
                columns: ['id', 'publisher_id', 'name', 'active', 'deleted_at'],
            )
            ->joinRelation(
                relation: 'advertiser.publisher',
                type: 'inner',
                columns: ['id', 'network_id', 'name', 'active', 'deleted_at'],
            )
            ->joinRelation(
                relation: 'advertiser.publisher.network',
                type: 'inner',
                columns: ['id', 'name', 'active', 'deleted_at'],
            )
            ->where('offers.active', true)
            ->whereNull('offers.deleted_at')
            ->where('advertisers.active', true)
            ->whereNull('advertisers.deleted_at')
            ->where('publishers.active', true)
            ->whereNull('publishers.deleted_at')
            ->where('networks.active', true)
            ->whereNull('networks.deleted_at')
            ->get();

        $this->assertCount(1, $offers);

        $offer = $offers->sole();

        $this->assertSame($matchingOffer->id, $offer->id);
        $this->assertTrue($offer->relationLoaded('advertiser'));
        $this->assertSame('Primary Advertiser', $offer->advertiser->name);
        $this->assertTrue($offer->advertiser->relationLoaded('publisher'));
        $this->assertSame('Primary Publisher', $offer->advertiser->publisher->name);
        $this->assertTrue($offer->advertiser->publisher->relationLoaded('network'));
        $this->assertSame('Primary Network', $offer->advertiser->publisher->network->name);
    }

    public function test_it_requires_ordered_path_hydration(): void
    {
        $network = Network::query()->create([
            'name' => 'Primary Network',
            'active' => true,
            'deleted_at' => null,
        ]);

        $publisher = Publisher::query()->create([
            'network_id' => $network->id,
            'name' => 'Primary Publisher',
            'active' => true,
            'deleted_at' => null,
        ]);

        $advertiser = Advertiser::query()->create([
            'publisher_id' => $publisher->id,
            'name' => 'Primary Advertiser',
            'active' => true,
            'deleted_at' => null,
        ]);

        Offer::query()->create([
            'advertiser_id' => $advertiser->id,
            'name' => 'Primary Offer',
            'active' => true,
            'deleted_at' => null,
        ]);

        $this->expectException(MissingParentRelationException::class);

        Offer::query()
            ->select('offers.*')
            ->joinRelation(
                relation: 'advertiser.publisher',
                type: 'inner',
                columns: ['id', 'network_id', 'name', 'active', 'deleted_at'],
            )
            ->firstOrFail();
    }

    public function test_it_does_not_trigger_additional_queries_for_nested_relations(): void
    {
        $offer = $this->resolveAdvancedOfferScenario();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $offer->advertiser;
        $offer->advertiser->publisher;
        $offer->advertiser->publisher->network;

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_it_does_not_lazy_load_nested_relations_when_prevent_lazy_loading_is_enabled(): void
    {
        Model::preventLazyLoading();

        try {
            $offer = $this->resolveAdvancedOfferScenario();

            $this->assertSame('Primary Advertiser', $offer->advertiser->name);
            $this->assertSame('Primary Publisher', $offer->advertiser->publisher->name);
            $this->assertSame('Primary Network', $offer->advertiser->publisher->network->name);
        } catch (LazyLoadingViolationException $exception) {
            $this->fail('Nested relations should already be hydrated and must not lazy load.');
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    protected function resolveAdvancedOfferScenario(): Offer
    {
        $network = Network::query()->create([
            'name' => 'Primary Network',
            'active' => true,
            'deleted_at' => null,
        ]);

        $publisher = Publisher::query()->create([
            'network_id' => $network->id,
            'name' => 'Primary Publisher',
            'active' => true,
            'deleted_at' => null,
        ]);

        $advertiser = Advertiser::query()->create([
            'publisher_id' => $publisher->id,
            'name' => 'Primary Advertiser',
            'active' => true,
            'deleted_at' => null,
        ]);

        $matchingOffer = Offer::query()->create([
            'advertiser_id' => $advertiser->id,
            'name' => 'Primary Offer',
            'active' => true,
            'deleted_at' => null,
        ]);

        $disabledNetwork = Network::query()->create([
            'name' => 'Disabled Network',
            'active' => false,
            'deleted_at' => null,
        ]);

        $disabledPublisher = Publisher::query()->create([
            'network_id' => $disabledNetwork->id,
            'name' => 'Disabled Publisher',
            'active' => true,
            'deleted_at' => null,
        ]);

        $disabledAdvertiser = Advertiser::query()->create([
            'publisher_id' => $disabledPublisher->id,
            'name' => 'Disabled Advertiser',
            'active' => true,
            'deleted_at' => null,
        ]);

        Offer::query()->create([
            'advertiser_id' => $disabledAdvertiser->id,
            'name' => 'Filtered Offer',
            'active' => true,
            'deleted_at' => null,
        ]);

        return Offer::query()
            ->select('offers.*')
            ->joinRelation(
                relation: 'advertiser',
                type: 'inner',
                columns: ['id', 'publisher_id', 'name', 'active', 'deleted_at'],
            )
            ->joinRelation(
                relation: 'advertiser.publisher',
                type: 'inner',
                columns: ['id', 'network_id', 'name', 'active', 'deleted_at'],
            )
            ->joinRelation(
                relation: 'advertiser.publisher.network',
                type: 'inner',
                columns: ['id', 'name', 'active', 'deleted_at'],
            )
            ->where('offers.active', true)
            ->whereNull('offers.deleted_at')
            ->where('advertisers.active', true)
            ->whereNull('advertisers.deleted_at')
            ->where('publishers.active', true)
            ->whereNull('publishers.deleted_at')
            ->where('networks.active', true)
            ->whereNull('networks.deleted_at')
            ->whereKey($matchingOffer->id)
            ->sole();
    }
}
