<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Yannelli\Pocket\Data\OrganizationUser;
use Yannelli\Pocket\Data\PaginatedUsers;
use Yannelli\Pocket\Data\UserSettingsBundle;
use Yannelli\Pocket\Data\UserSettingsUpdate;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\UsersResource;

function createUsersMockClient(array $responses, array &$history = []): PocketClient
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);
    $handlerStack->push(Middleware::history($history));

    return new PocketClient(
        apiKey: 'pk_test_key',
        baseUrl: 'https://public.heypocketai.com',
        apiVersion: 'v1',
        retryTimes: 0,
        handler: $handlerStack
    );
}

function usersJsonResponse(array $data, int $status = 200): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
}

describe('UsersResource', function () {
    it('lists organization users', function () {
        $history = [];
        $client = createUsersMockClient([
            usersJsonResponse([
                'success' => true,
                'data' => [
                    [
                        'user_id' => 'usr_001',
                        'email' => 'alice@example.com',
                        'display_name' => 'Alice Liu',
                        'role' => 'admin',
                        'status' => 'active',
                        'is_active' => true,
                        'created_at' => '2025-12-01T18:42:11.000Z',
                        'updated_at' => '2026-04-08T10:14:22.000Z',
                        'member_since' => '2026-01-04T15:00:00.000Z',
                        'recording_count' => 42,
                        'latest_recording_at' => '2026-04-29T09:21:33.000Z',
                    ],
                ],
                'pagination' => [
                    'page' => 1,
                    'limit' => 25,
                    'total' => 1,
                    'total_pages' => 1,
                    'has_more' => false,
                ],
            ]),
        ], $history);

        $users = (new UsersResource($client))->list(q: 'alice', page: 1, limit: 25);

        expect($users)->toBeInstanceOf(PaginatedUsers::class)
            ->and($users->count())->toBe(1)
            ->and($users->first())->toBeInstanceOf(OrganizationUser::class)
            ->and($users->first()->userId)->toBe('usr_001')
            ->and($users->first()->displayName)->toBe('Alice Liu')
            ->and($users->first()->recordingCount)->toBe(42);

        $request = $history[0]['request'];
        parse_str($request->getUri()->getQuery(), $query);

        expect($request->getMethod())->toBe('GET')
            ->and($request->getUri()->getPath())->toBe('/api/v1/public/users')
            ->and($query['q'])->toBe('alice')
            ->and($query['limit'])->toBe('25');
    });

    it('gets a member settings bundle', function () {
        $history = [];
        $client = createUsersMockClient([
            usersJsonResponse([
                'success' => true,
                'data' => [
                    'user' => [
                        'user_id' => 'usr_001',
                        'email' => 'alice@example.com',
                        'display_name' => 'Alice Liu',
                        'role' => 'admin',
                        'status' => 'active',
                        'timezone' => 'America/Los_Angeles',
                    ],
                    'settings' => [
                        'selected_theme' => 'auto_detect',
                        'selected_model' => 'gemini35Flash',
                    ],
                    'notifications' => [
                        'highlight_time' => '22:00',
                    ],
                    'tags' => [
                        ['id' => 'tag_001', 'name' => '1:1s', 'color' => '#33aaff'],
                    ],
                ],
            ]),
        ], $history);

        $bundle = (new UsersResource($client))->settings(
            userId: 'usr_001',
            include: ['profile', 'tags'],
            includePreferences: true,
            preferencesLimit: 50,
        );

        expect($bundle)->toBeInstanceOf(UserSettingsBundle::class)
            ->and($bundle->user?->userId)->toBe('usr_001')
            ->and($bundle->settings['selected_theme'])->toBe('auto_detect')
            ->and($bundle->tags)->toHaveCount(1);

        $request = $history[0]['request'];
        parse_str($request->getUri()->getQuery(), $query);

        expect($request->getUri()->getPath())->toBe('/api/v1/public/users/usr_001/settings')
            ->and($query['include'])->toBe('profile,tags')
            ->and($query['include_preferences'])->toBe('true')
            ->and($query['preferences_limit'])->toBe('50');
    });

    it('patches a member settings bundle', function () {
        $history = [];
        $client = createUsersMockClient([
            usersJsonResponse([
                'success' => true,
                'applied' => [
                    'settings' => true,
                    'preferences_created' => 1,
                ],
                'profile_version' => 8,
            ]),
        ], $history);

        $result = (new UsersResource($client))->updateSettings('usr_001', [
            'settings' => ['selected_theme' => 'executive_summary'],
        ]);

        expect($result)->toBeInstanceOf(UserSettingsUpdate::class)
            ->and($result->success)->toBeTrue()
            ->and($result->profileVersion)->toBe(8)
            ->and($result->applied['preferences_created'])->toBe(1);

        $request = $history[0]['request'];
        $body = json_decode((string) $request->getBody(), true);

        expect($request->getMethod())->toBe('PATCH')
            ->and($request->getUri()->getPath())->toBe('/api/v1/public/users/usr_001/settings')
            ->and($body['settings']['selected_theme'])->toBe('executive_summary');
    });
});
