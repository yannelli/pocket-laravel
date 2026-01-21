<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Yannelli\Pocket\Data\Tag;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\TagsResource;

function createTagsMockClient(array $responses, array &$history = []): PocketClient
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);
    $handlerStack->push(Middleware::history($history));

    return new PocketClient(
        apiKey: 'pk_test_key',
        baseUrl: 'https://https://public.heypocket.com',
        apiVersion: 'v1',
        handler: $handlerStack
    );
}

function tagsJsonResponse(array $data, int $status = 200): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
}

describe('TagsResource', function () {
    it('can list all tags', function () {
        $history = [];
        $client = createTagsMockClient([
            tagsJsonResponse([
                'success' => true,
                'data' => [
                    [
                        'id' => 'tag_123',
                        'name' => 'Important',
                        'color' => '#ff0000',
                        'usage_count' => 15,
                    ],
                    [
                        'id' => 'tag_456',
                        'name' => 'Follow-up',
                        'color' => '#00ff00',
                        'usage_count' => 8,
                    ],
                    [
                        'id' => 'tag_789',
                        'name' => 'Archive',
                        'color' => '#0000ff',
                        'usage_count' => 3,
                    ],
                ],
            ]),
        ], $history);

        $resource = new TagsResource($client);
        $tags = $resource->list();

        expect($tags)->toHaveCount(3)
            ->and($tags[0])->toBeInstanceOf(Tag::class)
            ->and($tags[0]->id)->toBe('tag_123')
            ->and($tags[0]->name)->toBe('Important')
            ->and($tags[0]->color)->toBe('#ff0000')
            ->and($tags[0]->usageCount)->toBe(15);

        // Verify the request
        $request = $history[0]['request'];
        expect($request->getMethod())->toBe('GET')
            ->and($request->getUri()->getPath())->toBe('/api/v1/public/tags')
            ->and($request->getHeader('Authorization')[0])->toBe('Bearer pk_test_key');
    });

    it('can find a tag by id', function () {
        $history = [];
        $client = createTagsMockClient([
            tagsJsonResponse([
                'success' => true,
                'data' => [
                    ['id' => 'tag_123', 'name' => 'Important', 'color' => '#ff0000', 'usage_count' => 15],
                    ['id' => 'tag_456', 'name' => 'Follow-up', 'color' => '#00ff00', 'usage_count' => 8],
                ],
            ]),
        ], $history);

        $resource = new TagsResource($client);
        $tag = $resource->find('tag_456');

        expect($tag)->not->toBeNull()
            ->and($tag->id)->toBe('tag_456')
            ->and($tag->name)->toBe('Follow-up');
    });

    it('returns null when tag not found by id', function () {
        $history = [];
        $client = createTagsMockClient([
            tagsJsonResponse([
                'success' => true,
                'data' => [
                    ['id' => 'tag_123', 'name' => 'Important', 'color' => '#ff0000', 'usage_count' => 15],
                ],
            ]),
        ], $history);

        $resource = new TagsResource($client);
        $tag = $resource->find('nonexistent');

        expect($tag)->toBeNull();
    });

    it('can find a tag by name', function () {
        $history = [];
        $client = createTagsMockClient([
            tagsJsonResponse([
                'success' => true,
                'data' => [
                    ['id' => 'tag_123', 'name' => 'Important', 'color' => '#ff0000', 'usage_count' => 15],
                    ['id' => 'tag_456', 'name' => 'Follow-up', 'color' => '#00ff00', 'usage_count' => 8],
                ],
            ]),
        ], $history);

        $resource = new TagsResource($client);
        $tag = $resource->findByName('Follow-up');

        expect($tag)->not->toBeNull()
            ->and($tag->id)->toBe('tag_456');
    });

    it('can get most used tags', function () {
        $history = [];
        $client = createTagsMockClient([
            tagsJsonResponse([
                'success' => true,
                'data' => [
                    ['id' => 'tag_1', 'name' => 'Tag 1', 'color' => '#111111', 'usage_count' => 100],
                    ['id' => 'tag_2', 'name' => 'Tag 2', 'color' => '#222222', 'usage_count' => 80],
                    ['id' => 'tag_3', 'name' => 'Tag 3', 'color' => '#333333', 'usage_count' => 60],
                    ['id' => 'tag_4', 'name' => 'Tag 4', 'color' => '#444444', 'usage_count' => 40],
                    ['id' => 'tag_5', 'name' => 'Tag 5', 'color' => '#555555', 'usage_count' => 20],
                ],
            ]),
        ], $history);

        $resource = new TagsResource($client);
        $tags = $resource->mostUsed(3);

        expect($tags)->toHaveCount(3)
            ->and($tags[0]->name)->toBe('Tag 1')
            ->and($tags[1]->name)->toBe('Tag 2')
            ->and($tags[2]->name)->toBe('Tag 3');
    });

    it('handles empty tags list', function () {
        $history = [];
        $client = createTagsMockClient([
            tagsJsonResponse([
                'success' => true,
                'data' => [],
            ]),
        ], $history);

        $resource = new TagsResource($client);
        $tags = $resource->list();

        expect($tags)->toBeEmpty();
    });
});
