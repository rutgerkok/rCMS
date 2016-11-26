<?php

namespace Rcms\Core;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * A class to make working with multiple files in the file system easier.
 */
final class BulkFileSystem {

    /**
     * Recursively removes all files and subdirectories in a directory.
     * @param string $dir The directory to empty.
     */
    public function clearDirectory($dir) {
        $directoryIterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $entriesIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($entriesIterator as $file) {
            $file->isDir() ? rmdir($file) : unlink($file);
        }
    }

    /**
     * Copies all files from one directory to another directory.
     * @param string $sourceDirectory The source directory.
     * @param string $targetDirectory The target directory.
     */
    public function copyFiles($sourceDirectory, $targetDirectory) {
        $dir = openDir($sourceDirectory);
        @mkDir($targetDirectory);
        while (false !== ($file = readDir($dir))) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            if (is_dir($sourceDirectory . '/' . $file)) {
                $this->copyFiles($sourceDirectory . '/' . $file, $targetDirectory . '/' . $file);
            } else {
                copy($sourceDirectory . '/' . $file, $targetDirectory . '/' . $file);
            }
        }
        closeDir($dir);
    }

}
