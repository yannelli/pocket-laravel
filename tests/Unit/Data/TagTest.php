<?php

use Yannelli\Pocket\Data\Tag;

it('can create a tag from array', function () {
    $tag = Tag::fromArray([
        'id' => 'tag_123',
        'name' => 'Important',
        'color' => '#ff0000',
        'usage_count' => 15,
    ]);

    expect($tag->id)->toBe('tag_123')
        ->and($tag->name)->toBe('Important')
        ->and($tag->color)->toBe('#ff0000')
        ->and($tag->usageCount)->toBe(15);
});

it('can create a tag without usage count', function () {
    $tag = Tag::fromArray([
        'id' => 'tag_456',
        'name' => 'Follow-up',
        'color' => '#00ff00',
    ]);

    expect($tag->usageCount)->toBeNull();
});

it('can create a collection of tags', function () {
    $tags = Tag::collection([
        ['id' => 'tag_1', 'name' => 'Tag 1', 'color' => '#111111'],
        ['id' => 'tag_2', 'name' => 'Tag 2', 'color' => '#222222'],
    ]);

    expect($tags)->toHaveCount(2)
        ->and($tags[0])->toBeInstanceOf(Tag::class)
        ->and($tags[1])->toBeInstanceOf(Tag::class);
});

it('can convert tag to array', function () {
    $tag = new Tag('tag_123', 'Important', '#ff0000', 15);

    expect($tag->toArray())->toMatchArray([
        'id' => 'tag_123',
        'name' => 'Important',
        'color' => '#ff0000',
        'usage_count' => 15,
    ]);
});

it('can serialize tag to json', function () {
    $tag = new Tag('tag_123', 'Important', '#ff0000');

    $json = json_encode($tag);

    expect($json)->toContain('tag_123')
        ->and($json)->toContain('Important');
});
