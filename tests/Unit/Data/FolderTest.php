<?php

use PocketLabs\Pocket\Data\Folder;

it('can create a folder from array', function () {
    $folder = Folder::fromArray([
        'id' => 'folder_123',
        'name' => 'Work Meetings',
        'is_default' => false,
        'created_at' => '2025-01-01T00:00:00Z',
        'updated_at' => '2025-01-10T00:00:00Z',
    ]);

    expect($folder->id)->toBe('folder_123')
        ->and($folder->name)->toBe('Work Meetings')
        ->and($folder->isDefault)->toBeFalse()
        ->and($folder->createdAt->format('Y-m-d'))->toBe('2025-01-01')
        ->and($folder->updatedAt->format('Y-m-d'))->toBe('2025-01-10');
});

it('can create a default folder', function () {
    $folder = Folder::fromArray([
        'id' => 'folder_456',
        'name' => 'Default',
        'is_default' => true,
        'created_at' => '2024-12-01T00:00:00Z',
        'updated_at' => '2024-12-01T00:00:00Z',
    ]);

    expect($folder->isDefault)->toBeTrue();
});

it('can create a collection of folders', function () {
    $folders = Folder::collection([
        [
            'id' => 'folder_1',
            'name' => 'Folder 1',
            'is_default' => false,
            'created_at' => '2025-01-01T00:00:00Z',
            'updated_at' => '2025-01-01T00:00:00Z',
        ],
        [
            'id' => 'folder_2',
            'name' => 'Folder 2',
            'is_default' => true,
            'created_at' => '2025-01-01T00:00:00Z',
            'updated_at' => '2025-01-01T00:00:00Z',
        ],
    ]);

    expect($folders)->toHaveCount(2)
        ->and($folders[0])->toBeInstanceOf(Folder::class)
        ->and($folders[1])->toBeInstanceOf(Folder::class);
});

it('can convert folder to array', function () {
    $folder = Folder::fromArray([
        'id' => 'folder_123',
        'name' => 'Work Meetings',
        'is_default' => false,
        'created_at' => '2025-01-01T00:00:00Z',
        'updated_at' => '2025-01-10T00:00:00Z',
    ]);

    $array = $folder->toArray();

    expect($array['id'])->toBe('folder_123')
        ->and($array['name'])->toBe('Work Meetings')
        ->and($array['is_default'])->toBeFalse();
});
