<?php

declare(strict_types=1);

use Laratusk\Supervise\Exceptions\ValidationException;
use Laratusk\Supervise\Services\ConfigValidator;

it('passes a valid horizon config', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'horizon' => ['type' => 'horizon'],
        ],
        'groups' => [],
    ]))->not->toThrow(ValidationException::class);
});

it('passes a valid queue config', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'emails' => [
                'type' => 'queue',
                'queue' => ['emails', 'notifications'],
            ],
        ],
        'groups' => [],
    ]))->not->toThrow(ValidationException::class);
});

it('passes a valid reverb config', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'reverb' => ['type' => 'reverb'],
        ],
        'groups' => [],
    ]))->not->toThrow(ValidationException::class);
});

it('throws when workers array is empty', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [],
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws when workers key is missing', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws when worker type is invalid', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'bad' => ['type' => 'invalid-type'],
        ],
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws when worker type is missing', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'bad' => [],
        ],
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws when queue worker is missing queue key', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'emails' => ['type' => 'queue'],
        ],
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws when queue array is empty', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'emails' => [
                'type' => 'queue',
                'queue' => [],
            ],
        ],
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws when numprocs is zero', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'horizon' => [
                'type' => 'horizon',
                'numprocs' => 0,
            ],
        ],
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws when numprocs is negative', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'horizon' => [
                'type' => 'horizon',
                'numprocs' => -1,
            ],
        ],
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws when tries is zero', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'emails' => [
                'type' => 'queue',
                'queue' => ['default'],
                'tries' => 0,
            ],
        ],
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws when max_time is zero', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'emails' => [
                'type' => 'queue',
                'queue' => ['default'],
                'max_time' => 0,
            ],
        ],
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('passes when sleep is zero', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'emails' => [
                'type' => 'queue',
                'queue' => ['default'],
                'sleep' => 0,
            ],
        ],
        'groups' => [],
    ]))->not->toThrow(ValidationException::class);
});

it('throws when group references a non-existent worker', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'horizon' => ['type' => 'horizon'],
        ],
        'groups' => [
            'my-group' => ['non-existent-worker'],
        ],
    ]))->toThrow(ValidationException::class);
});

it('passes when group references valid workers', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'horizon' => ['type' => 'horizon'],
            'emails' => ['type' => 'queue', 'queue' => ['emails']],
        ],
        'groups' => [
            'all' => ['horizon', 'emails'],
        ],
    ]))->not->toThrow(ValidationException::class);
});

it('returns errors via getErrors()', function (): void {
    $validator = new ConfigValidator;

    try {
        $validator->validate(['workers' => [], 'groups' => []]);
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $e) {
        expect($e->getErrors())->toBeArray()->not->toBeEmpty();
    }
});
