<?php

use Yannelli\Pocket\Data\Pagination;

it('can create pagination from array', function () {
    $pagination = Pagination::fromArray([
        'page' => 1,
        'limit' => 20,
        'total' => 150,
        'total_pages' => 8,
        'has_more' => true,
    ]);

    expect($pagination->page)->toBe(1)
        ->and($pagination->limit)->toBe(20)
        ->and($pagination->total)->toBe(150)
        ->and($pagination->totalPages)->toBe(8)
        ->and($pagination->hasMore)->toBeTrue();
});

it('can check if first page', function () {
    $first = Pagination::fromArray([
        'page' => 1,
        'limit' => 20,
        'total' => 100,
        'total_pages' => 5,
        'has_more' => true,
    ]);

    $second = Pagination::fromArray([
        'page' => 2,
        'limit' => 20,
        'total' => 100,
        'total_pages' => 5,
        'has_more' => true,
    ]);

    expect($first->isFirstPage())->toBeTrue()
        ->and($second->isFirstPage())->toBeFalse();
});

it('can check if last page', function () {
    $middle = Pagination::fromArray([
        'page' => 2,
        'limit' => 20,
        'total' => 100,
        'total_pages' => 5,
        'has_more' => true,
    ]);

    $last = Pagination::fromArray([
        'page' => 5,
        'limit' => 20,
        'total' => 100,
        'total_pages' => 5,
        'has_more' => false,
    ]);

    expect($middle->isLastPage())->toBeFalse()
        ->and($last->isLastPage())->toBeTrue();
});

it('can get next page', function () {
    $hasMore = Pagination::fromArray([
        'page' => 2,
        'limit' => 20,
        'total' => 100,
        'total_pages' => 5,
        'has_more' => true,
    ]);

    $noMore = Pagination::fromArray([
        'page' => 5,
        'limit' => 20,
        'total' => 100,
        'total_pages' => 5,
        'has_more' => false,
    ]);

    expect($hasMore->nextPage())->toBe(3)
        ->and($noMore->nextPage())->toBeNull();
});

it('can get previous page', function () {
    $first = Pagination::fromArray([
        'page' => 1,
        'limit' => 20,
        'total' => 100,
        'total_pages' => 5,
        'has_more' => true,
    ]);

    $second = Pagination::fromArray([
        'page' => 2,
        'limit' => 20,
        'total' => 100,
        'total_pages' => 5,
        'has_more' => true,
    ]);

    expect($first->previousPage())->toBeNull()
        ->and($second->previousPage())->toBe(1);
});
