<?php

declare(strict_types=1);

use Laratusk\Supervise\Enums\WorkerType;

it('has the correct horizon value', function (): void {
    expect(WorkerType::Horizon->value)->toBe('horizon');
});

it('has the correct queue value', function (): void {
    expect(WorkerType::Queue->value)->toBe('queue');
});

it('has the correct reverb value', function (): void {
    expect(WorkerType::Reverb->value)->toBe('reverb');
});

it('can be created from horizon string', function (): void {
    expect(WorkerType::from('horizon'))->toBe(WorkerType::Horizon);
});

it('can be created from queue string', function (): void {
    expect(WorkerType::from('queue'))->toBe(WorkerType::Queue);
});

it('can be created from reverb string', function (): void {
    expect(WorkerType::from('reverb'))->toBe(WorkerType::Reverb);
});

it('returns null for invalid value with tryFrom', function (): void {
    expect(WorkerType::tryFrom('invalid'))->toBeNull();
});

it('has all expected cases', function (): void {
    $cases = WorkerType::cases();
    expect($cases)->toHaveCount(3);

    $values = array_map(fn (WorkerType $t) => $t->value, $cases);
    expect($values)->toContain('horizon', 'queue', 'reverb');
});
