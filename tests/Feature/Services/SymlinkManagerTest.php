<?php

declare(strict_types=1);

use Laratusk\Supervise\Exceptions\CompileException;
use Laratusk\Supervise\Services\SymlinkManager;

beforeEach(function (): void {
    $this->sourceDir = $this->makeTempDir();
    $this->targetDir = $this->makeTempDir();
});

afterEach(function (): void {
    $this->removeDir($this->sourceDir);
    $this->removeDir($this->targetDir);
});

it('creates symlinks for source files', function (): void {
    $sourceFile = $this->sourceDir.'/horizon.conf';
    file_put_contents($sourceFile, '[program:horizon]');

    $manager = new SymlinkManager;
    $linked = $manager->link([$sourceFile], $this->targetDir);

    expect($linked)->toHaveCount(1);
    expect(is_link($this->targetDir.'/horizon.conf'))->toBeTrue();
    expect(readlink($this->targetDir.'/horizon.conf'))->toBe($sourceFile);
});

it('creates multiple symlinks', function (): void {
    $file1 = $this->sourceDir.'/horizon.conf';
    $file2 = $this->sourceDir.'/emails.conf';
    file_put_contents($file1, '[program:horizon]');
    file_put_contents($file2, '[program:emails]');

    $manager = new SymlinkManager;
    $linked = $manager->link([$file1, $file2], $this->targetDir);

    expect($linked)->toHaveCount(2);
    expect(is_link($this->targetDir.'/horizon.conf'))->toBeTrue();
    expect(is_link($this->targetDir.'/emails.conf'))->toBeTrue();
});

it('recreates an existing symlink', function (): void {
    $sourceFile = $this->sourceDir.'/horizon.conf';
    file_put_contents($sourceFile, '[program:horizon]');

    // Create an existing symlink pointing somewhere else
    $oldTarget = $this->sourceDir.'/old.conf';
    file_put_contents($oldTarget, 'old');
    symlink($oldTarget, $this->targetDir.'/horizon.conf');

    expect(is_link($this->targetDir.'/horizon.conf'))->toBeTrue();
    expect(readlink($this->targetDir.'/horizon.conf'))->toBe($oldTarget);

    $manager = new SymlinkManager;
    $manager->link([$sourceFile], $this->targetDir);

    expect(readlink($this->targetDir.'/horizon.conf'))->toBe($sourceFile);
});

it('throws when source file does not exist', function (): void {
    $manager = new SymlinkManager;

    expect(fn (): array => $manager->link(['/nonexistent/path/horizon.conf'], $this->targetDir))
        ->toThrow(CompileException::class);
});

it('throws when target directory does not exist', function (): void {
    $sourceFile = $this->sourceDir.'/horizon.conf';
    file_put_contents($sourceFile, '[program:horizon]');

    $manager = new SymlinkManager;

    expect(fn (): array => $manager->link([$sourceFile], '/nonexistent/target/dir'))
        ->toThrow(CompileException::class);
});

it('returns linked paths in the same order as input', function (): void {
    $file1 = $this->sourceDir.'/a.conf';
    $file2 = $this->sourceDir.'/b.conf';
    file_put_contents($file1, 'a');
    file_put_contents($file2, 'b');

    $manager = new SymlinkManager;
    $linked = $manager->link([$file1, $file2], $this->targetDir);

    expect($linked[0])->toBe($this->targetDir.'/a.conf');
    expect($linked[1])->toBe($this->targetDir.'/b.conf');
});
