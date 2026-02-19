<?php

declare(strict_types=1);

namespace Laratusk\Supervise\Services;

use Laratusk\Supervise\Exceptions\CompileException;

class SymlinkManager
{
    /**
     * Create symlinks for each source file in the target directory.
     *
     * @param  list<string>  $sourceFiles  Absolute paths to the .conf files to symlink
     * @param  string  $targetDir  The directory where symlinks will be created
     * @return list<string> The absolute paths of the created symlinks
     *
     * @throws CompileException
     */
    public function link(array $sourceFiles, string $targetDir): array
    {
        if (! is_dir($targetDir)) {
            throw new CompileException("Target directory '{$targetDir}' does not exist or is not a directory.");
        }

        if (! is_writable($targetDir)) {
            throw new CompileException("Target directory '{$targetDir}' is not writable.");
        }

        $linked = [];

        foreach ($sourceFiles as $sourceFile) {
            if (! file_exists($sourceFile)) {
                throw new CompileException("Source file '{$sourceFile}' does not exist.");
            }

            $filename = basename($sourceFile);
            $targetPath = $targetDir.DIRECTORY_SEPARATOR.$filename;

            if (is_link($targetPath)) {
                unlink($targetPath);
            }

            if (! symlink($sourceFile, $targetPath)) {
                throw new CompileException("Failed to create symlink '{$targetPath}' → '{$sourceFile}'.");
            }

            $linked[] = $targetPath;
        }

        return $linked;
    }
}
