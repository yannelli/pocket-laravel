<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Yannelli\Pocket\Data\PaginatedRecordings;
use Yannelli\Pocket\Data\Recording;
use Yannelli\Pocket\Enums\RecordingState;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\RecordingsResource;

function createMockClient(array $responses, array &$history = []): PocketClient
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

function jsonResponse(array $data, int $status = 200): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
}

describe('RecordingsResource', function () {
    it('can list recordings', function () {
        $history = [];
        $client = createMockClient([
            jsonResponse([
                'success' => true,
                'data' => [
                    [
                        'id' => 'rec_123',
                        'title' => 'Test Recording',
                        'folder_id' => 'folder_456',
                        'duration' => 3600,
                        'state' => 'completed',
                        'language' => 'en',
                        'created_at' => '2025-01-15T10:30:00Z',
                        'updated_at' => '2025-01-15T11:00:00Z',
                        'tags' => [
                            ['id' => 'tag_1', 'name' => 'Important', 'color' => '#ff0000'],
                        ],
                    ],
                    [
                        'id' => 'rec_456',
                        'title' => 'Another Recording',
                        'folder_id' => null,
                        'duration' => 1800,
                        'state' => 'transcribing',
                        'language' => 'en',
                        'created_at' => '2025-01-14T09:00:00Z',
                        'updated_at' => '2025-01-14T09:30:00Z',
                        'tags' => [],
                    ],
                ],
                'pagination' => [
                    'page' => 1,
                    'limit' => 20,
                    'total' => 2,
                    'total_pages' => 1,
                    'has_more' => false,
                ],
            ]),
        ], $history);

        $resource = new RecordingsResource($client);
        $result = $resource->list();

        expect($result)->toBeInstanceOf(PaginatedRecordings::class)
            ->and($result->count())->toBe(2)
            ->and($result->total())->toBe(2)
            ->and($result->hasMore())->toBeFalse()
            ->and($result->first()->id)->toBe('rec_123')
            ->and($result->first()->title)->toBe('Test Recording')
            ->and($result->first()->state)->toBe(RecordingState::Completed)
            ->and($result->first()->tags)->toHaveCount(1);

        // Verify the request
        $request = $history[0]['request'];
        expect($request->getMethod())->toBe('GET')
            ->and($request->getUri()->getPath())->toBe('/api/v1/public/recordings')
            ->and($request->getHeader('Authorization')[0])->toBe('Bearer pk_test_key');
    });

    it('can list recordings with filters', function () {
        $history = [];
        $client = createMockClient([
            jsonResponse([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'page' => 2,
                    'limit' => 50,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_more' => false,
                ],
            ]),
        ], $history);

        $resource = new RecordingsResource($client);
        $resource->list(
            folderId: 'folder_123',
            startDate: '2025-01-01',
            endDate: '2025-01-31',
            tagIds: ['tag_1', 'tag_2'],
            page: 2,
            limit: 50
        );

        $request = $history[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);

        expect($query['folder_id'])->toBe('folder_123')
            ->and($query['start_date'])->toBe('2025-01-01')
            ->and($query['end_date'])->toBe('2025-01-31')
            ->and($query['tag_ids'])->toBe('tag_1,tag_2')
            ->and($query['page'])->toBe('2')
            ->and($query['limit'])->toBe('50');
    });

    it('can list recordings with DateTime objects', function () {
        $history = [];
        $client = createMockClient([
            jsonResponse([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'page' => 1,
                    'limit' => 20,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_more' => false,
                ],
            ]),
        ], $history);

        $resource = new RecordingsResource($client);
        $resource->list(
            startDate: new DateTimeImmutable('2025-01-01'),
            endDate: new DateTimeImmutable('2025-01-31')
        );

        $request = $history[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);

        expect($query['start_date'])->toBe('2025-01-01')
            ->and($query['end_date'])->toBe('2025-01-31');
    });

    it('can get a single recording', function () {
        $history = [];
        $client = createMockClient([
            jsonResponse([
                'success' => true,
                'data' => [
                    'id' => 'rec_123',
                    'title' => 'Meeting Recording',
                    'folder_id' => 'folder_456',
                    'duration' => 3600,
                    'state' => 'completed',
                    'language' => 'en',
                    'created_at' => '2025-01-15T10:30:00Z',
                    'updated_at' => '2025-01-15T11:00:00Z',
                    'tags' => [],
                    'transcript' => [
                        'text' => 'Hello everyone, welcome to the meeting.',
                        'segments' => [
                            ['start' => 0, 'end' => 5.5, 'text' => 'Hello everyone', 'speaker' => 'Speaker 1'],
                            ['start' => 5.5, 'end' => 10.0, 'text' => 'welcome to the meeting.', 'speaker' => 'Speaker 1'],
                        ],
                    ],
                    'summary' => [
                        'title' => 'Team Meeting Summary',
                        'sections' => [
                            ['heading' => 'Key Points', 'content' => 'The team discussed project updates.'],
                        ],
                    ],
                    'action_items' => [
                        [
                            'id' => 'ai_001',
                            'title' => 'Review the proposal',
                            'description' => 'Review and approve the Q1 proposal',
                            'status' => 'pending',
                            'priority' => 'high',
                            'due_date' => '2025-01-20',
                        ],
                    ],
                ],
            ]),
        ], $history);

        $resource = new RecordingsResource($client);
        $recording = $resource->get('rec_123');

        expect($recording)->toBeInstanceOf(Recording::class)
            ->and($recording->id)->toBe('rec_123')
            ->and($recording->title)->toBe('Meeting Recording')
            ->and($recording->hasTranscript())->toBeTrue()
            ->and($recording->transcript->text)->toBe('Hello everyone, welcome to the meeting.')
            ->and($recording->transcript->segments)->toHaveCount(2)
            ->and($recording->hasSummary())->toBeTrue()
            ->and($recording->summary->title)->toBe('Team Meeting Summary')
            ->and($recording->hasActionItems())->toBeTrue()
            ->and($recording->actionItems)->toHaveCount(1)
            ->and($recording->actionItems[0]->title)->toBe('Review the proposal');

        // Verify the request
        $request = $history[0]['request'];
        expect($request->getMethod())->toBe('GET')
            ->and($request->getUri()->getPath())->toBe('/api/v1/public/recordings/rec_123');
    });

    it('can get a recording without optional includes', function () {
        $history = [];
        $client = createMockClient([
            jsonResponse([
                'success' => true,
                'data' => [
                    'id' => 'rec_123',
                    'title' => 'Meeting Recording',
                    'folder_id' => null,
                    'duration' => 3600,
                    'state' => 'completed',
                    'language' => 'en',
                    'created_at' => '2025-01-15T10:30:00Z',
                    'updated_at' => '2025-01-15T11:00:00Z',
                    'tags' => [],
                ],
            ]),
        ], $history);

        $resource = new RecordingsResource($client);
        $resource->get(
            id: 'rec_123',
            includeTranscript: false,
            includeSummary: false,
            includeActionItems: false
        );

        $request = $history[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);

        expect($query['include_transcript'])->toBe('false')
            ->and($query['include_summary'])->toBe('false')
            ->and($query['include_action_items'])->toBe('false');
    });

    it('can filter recordings by folder', function () {
        $history = [];
        $client = createMockClient([
            jsonResponse([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'page' => 1,
                    'limit' => 20,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_more' => false,
                ],
            ]),
        ], $history);

        $resource = new RecordingsResource($client);
        $resource->inFolder('folder_123');

        $request = $history[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);

        expect($query['folder_id'])->toBe('folder_123');
    });

    it('can filter recordings by tags', function () {
        $history = [];
        $client = createMockClient([
            jsonResponse([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'page' => 1,
                    'limit' => 20,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_more' => false,
                ],
            ]),
        ], $history);

        $resource = new RecordingsResource($client);
        $resource->withTags(['tag_1', 'tag_2']);

        $request = $history[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);

        expect($query['tag_ids'])->toBe('tag_1,tag_2');
    });

    it('can filter recordings by date range', function () {
        $history = [];
        $client = createMockClient([
            jsonResponse([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'page' => 1,
                    'limit' => 20,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_more' => false,
                ],
            ]),
        ], $history);

        $resource = new RecordingsResource($client);
        $resource->betweenDates('2025-01-01', '2025-01-31');

        $request = $history[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);

        expect($query['start_date'])->toBe('2025-01-01')
            ->and($query['end_date'])->toBe('2025-01-31');
    });

    it('can iterate all recordings with pagination', function () {
        $history = [];
        $client = createMockClient([
            jsonResponse([
                'success' => true,
                'data' => [
                    [
                        'id' => 'rec_1',
                        'title' => 'Recording 1',
                        'folder_id' => null,
                        'duration' => 100,
                        'state' => 'completed',
                        'language' => 'en',
                        'created_at' => '2025-01-15T10:30:00Z',
                        'updated_at' => '2025-01-15T11:00:00Z',
                        'tags' => [],
                    ],
                ],
                'pagination' => [
                    'page' => 1,
                    'limit' => 1,
                    'total' => 2,
                    'total_pages' => 2,
                    'has_more' => true,
                ],
            ]),
            jsonResponse([
                'success' => true,
                'data' => [
                    [
                        'id' => 'rec_2',
                        'title' => 'Recording 2',
                        'folder_id' => null,
                        'duration' => 200,
                        'state' => 'completed',
                        'language' => 'en',
                        'created_at' => '2025-01-14T10:30:00Z',
                        'updated_at' => '2025-01-14T11:00:00Z',
                        'tags' => [],
                    ],
                ],
                'pagination' => [
                    'page' => 2,
                    'limit' => 1,
                    'total' => 2,
                    'total_pages' => 2,
                    'has_more' => false,
                ],
            ]),
        ], $history);

        $resource = new RecordingsResource($client);
        $recordings = iterator_to_array($resource->all());

        expect($recordings)->toHaveCount(2)
            ->and($recordings[0]->id)->toBe('rec_1')
            ->and($recordings[1]->id)->toBe('rec_2')
            ->and($history)->toHaveCount(2);
    });

    it('limits recordings to 100 per page', function () {
        $history = [];
        $client = createMockClient([
            jsonResponse([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'page' => 1,
                    'limit' => 100,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_more' => false,
                ],
            ]),
        ], $history);

        $resource = new RecordingsResource($client);
        $resource->list(limit: 200); // Try to request 200

        $request = $history[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);

        expect($query['limit'])->toBe('100'); // Should be capped at 100
    });
});
