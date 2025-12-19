<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PocketLabs\Pocket\Data\Folder;
use PocketLabs\Pocket\PocketClient;
use PocketLabs\Pocket\Resources\FoldersResource;

function createFoldersMockClient(array $responses, array &$history = []): PocketClient
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);
    $handlerStack->push(Middleware::history($history));

    return new PocketClient(
        apiKey: 'pk_test_key',
        baseUrl: 'https://app.heypocket.com',
        apiVersion: 'v1',
        handler: $handlerStack
    );
}

function foldersJsonResponse(array $data, int $status = 200): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
}

describe('FoldersResource', function () {
    it('can list all folders', function () {
        $history = [];
        $client = createFoldersMockClient([
            foldersJsonResponse([
                'success' => true,
                'data' => [
                    [
                        'id' => 'folder_123',
                        'name' => 'Work Meetings',
                        'is_default' => false,
                        'created_at' => '2025-01-01T00:00:00Z',
                        'updated_at' => '2025-01-10T00:00:00Z',
                    ],
                    [
                        'id' => 'folder_456',
                        'name' => 'Default',
                        'is_default' => true,
                        'created_at' => '2024-12-01T00:00:00Z',
                        'updated_at' => '2024-12-01T00:00:00Z',
                    ],
                ],
            ]),
        ], $history);

        $resource = new FoldersResource($client);
        $folders = $resource->list();

        expect($folders)->toHaveCount(2)
            ->and($folders[0])->toBeInstanceOf(Folder::class)
            ->and($folders[0]->id)->toBe('folder_123')
            ->and($folders[0]->name)->toBe('Work Meetings')
            ->and($folders[0]->isDefault)->toBeFalse()
            ->and($folders[1]->id)->toBe('folder_456')
            ->and($folders[1]->isDefault)->toBeTrue();

        // Verify the request
        $request = $history[0]['request'];
        expect($request->getMethod())->toBe('GET')
            ->and($request->getUri()->getPath())->toBe('/api/v1/public/folders')
            ->and($request->getHeader('Authorization')[0])->toBe('Bearer pk_test_key');
    });

    it('can find a folder by id', function () {
        $history = [];
        $client = createFoldersMockClient([
            foldersJsonResponse([
                'success' => true,
                'data' => [
                    [
                        'id' => 'folder_123',
                        'name' => 'Work Meetings',
                        'is_default' => false,
                        'created_at' => '2025-01-01T00:00:00Z',
                        'updated_at' => '2025-01-10T00:00:00Z',
                    ],
                    [
                        'id' => 'folder_456',
                        'name' => 'Default',
                        'is_default' => true,
                        'created_at' => '2024-12-01T00:00:00Z',
                        'updated_at' => '2024-12-01T00:00:00Z',
                    ],
                ],
            ]),
        ], $history);

        $resource = new FoldersResource($client);
        $folder = $resource->find('folder_456');

        expect($folder)->not->toBeNull()
            ->and($folder->id)->toBe('folder_456')
            ->and($folder->name)->toBe('Default');
    });

    it('returns null when folder not found by id', function () {
        $history = [];
        $client = createFoldersMockClient([
            foldersJsonResponse([
                'success' => true,
                'data' => [
                    [
                        'id' => 'folder_123',
                        'name' => 'Work Meetings',
                        'is_default' => false,
                        'created_at' => '2025-01-01T00:00:00Z',
                        'updated_at' => '2025-01-10T00:00:00Z',
                    ],
                ],
            ]),
        ], $history);

        $resource = new FoldersResource($client);
        $folder = $resource->find('nonexistent');

        expect($folder)->toBeNull();
    });

    it('can find a folder by name', function () {
        $history = [];
        $client = createFoldersMockClient([
            foldersJsonResponse([
                'success' => true,
                'data' => [
                    [
                        'id' => 'folder_123',
                        'name' => 'Work Meetings',
                        'is_default' => false,
                        'created_at' => '2025-01-01T00:00:00Z',
                        'updated_at' => '2025-01-10T00:00:00Z',
                    ],
                    [
                        'id' => 'folder_456',
                        'name' => 'Personal',
                        'is_default' => false,
                        'created_at' => '2024-12-01T00:00:00Z',
                        'updated_at' => '2024-12-01T00:00:00Z',
                    ],
                ],
            ]),
        ], $history);

        $resource = new FoldersResource($client);
        $folder = $resource->findByName('Personal');

        expect($folder)->not->toBeNull()
            ->and($folder->id)->toBe('folder_456')
            ->and($folder->name)->toBe('Personal');
    });

    it('can get the default folder', function () {
        $history = [];
        $client = createFoldersMockClient([
            foldersJsonResponse([
                'success' => true,
                'data' => [
                    [
                        'id' => 'folder_123',
                        'name' => 'Work Meetings',
                        'is_default' => false,
                        'created_at' => '2025-01-01T00:00:00Z',
                        'updated_at' => '2025-01-10T00:00:00Z',
                    ],
                    [
                        'id' => 'folder_456',
                        'name' => 'Default',
                        'is_default' => true,
                        'created_at' => '2024-12-01T00:00:00Z',
                        'updated_at' => '2024-12-01T00:00:00Z',
                    ],
                ],
            ]),
        ], $history);

        $resource = new FoldersResource($client);
        $folder = $resource->default();

        expect($folder)->not->toBeNull()
            ->and($folder->id)->toBe('folder_456')
            ->and($folder->isDefault)->toBeTrue();
    });

    it('returns null when no default folder exists', function () {
        $history = [];
        $client = createFoldersMockClient([
            foldersJsonResponse([
                'success' => true,
                'data' => [
                    [
                        'id' => 'folder_123',
                        'name' => 'Work Meetings',
                        'is_default' => false,
                        'created_at' => '2025-01-01T00:00:00Z',
                        'updated_at' => '2025-01-10T00:00:00Z',
                    ],
                ],
            ]),
        ], $history);

        $resource = new FoldersResource($client);
        $folder = $resource->default();

        expect($folder)->toBeNull();
    });
});
