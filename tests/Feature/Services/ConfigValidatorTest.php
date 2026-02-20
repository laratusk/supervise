<?php

declare(strict_types=1);

use Laratusk\Supervise\Exceptions\ValidationException;
use Laratusk\Supervise\Services\ConfigValidator;

it('passes a valid worker config with command', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'horizon' => ['command' => 'php artisan horizon'],
        ],
        'groups' => [],
    ]))->not->toThrow(ValidationException::class);
});

it('passes a valid worker with command and options', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'emails' => [
                'command' => 'php artisan queue:work redis --queue=emails,notifications',
            ],
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

it('throws when worker command is missing', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'bad' => [],
        ],
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws when numprocs is zero', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'horizon' => [
                'command' => 'php artisan horizon',
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
                'command' => 'php artisan horizon',
                'numprocs' => -1,
            ],
        ],
        'groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws when group references a non-existent worker', function (): void {
    $validator = new ConfigValidator;

    expect(fn () => $validator->validate([
        'workers' => [
            'horizon' => ['command' => 'php artisan horizon'],
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
            'horizon' => ['command' => 'php artisan horizon'],
            'emails' => ['command' => 'php artisan queue:work --queue=emails'],
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
