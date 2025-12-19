<?php

use Yannelli\Pocket\Data\ActionItem;
use Yannelli\Pocket\Enums\ActionItemPriority;
use Yannelli\Pocket\Enums\ActionItemStatus;

it('can create an action item from array', function () {
    $actionItem = ActionItem::fromArray([
        'id' => 'ai_001',
        'title' => 'Review budget proposal',
        'description' => 'Review and approve the Q1 budget',
        'status' => 'pending',
        'priority' => 'high',
        'due_date' => '2025-01-20',
    ]);

    expect($actionItem->id)->toBe('ai_001')
        ->and($actionItem->title)->toBe('Review budget proposal')
        ->and($actionItem->description)->toBe('Review and approve the Q1 budget')
        ->and($actionItem->status)->toBe(ActionItemStatus::Pending)
        ->and($actionItem->priority)->toBe(ActionItemPriority::High)
        ->and($actionItem->dueDate->format('Y-m-d'))->toBe('2025-01-20');
});

it('can create an action item without optional fields', function () {
    $actionItem = ActionItem::fromArray([
        'id' => 'ai_002',
        'title' => 'Simple task',
    ]);

    expect($actionItem->description)->toBeNull()
        ->and($actionItem->status)->toBe(ActionItemStatus::Pending)
        ->and($actionItem->priority)->toBe(ActionItemPriority::Medium)
        ->and($actionItem->dueDate)->toBeNull();
});

it('can check if action item is pending', function () {
    $pending = ActionItem::fromArray([
        'id' => 'ai_1',
        'title' => 'Task',
        'status' => 'pending',
    ]);

    $completed = ActionItem::fromArray([
        'id' => 'ai_2',
        'title' => 'Task',
        'status' => 'completed',
    ]);

    expect($pending->isPending())->toBeTrue()
        ->and($pending->isCompleted())->toBeFalse()
        ->and($completed->isPending())->toBeFalse()
        ->and($completed->isCompleted())->toBeTrue();
});

it('can check if action item is overdue', function () {
    $overdue = ActionItem::fromArray([
        'id' => 'ai_1',
        'title' => 'Task',
        'status' => 'pending',
        'due_date' => '2020-01-01',
    ]);

    $future = ActionItem::fromArray([
        'id' => 'ai_2',
        'title' => 'Task',
        'status' => 'pending',
        'due_date' => '2030-01-01',
    ]);

    $completed = ActionItem::fromArray([
        'id' => 'ai_3',
        'title' => 'Task',
        'status' => 'completed',
        'due_date' => '2020-01-01',
    ]);

    expect($overdue->isOverdue())->toBeTrue()
        ->and($future->isOverdue())->toBeFalse()
        ->and($completed->isOverdue())->toBeFalse();
});

it('can create a collection of action items', function () {
    $items = ActionItem::collection([
        ['id' => 'ai_1', 'title' => 'Task 1'],
        ['id' => 'ai_2', 'title' => 'Task 2'],
    ]);

    expect($items)->toHaveCount(2)
        ->and($items[0])->toBeInstanceOf(ActionItem::class);
});
