<?php

namespace drhino\dmarcEmailFilter;

use function clearstatcache;
use function mkdir;
use function is_dir;
use function time;
use function rand;
use function file_exists;
use function touch;
use function fopen;
use function fgets;
use function fwrite;
use function fclose;

use Exception;

final class dmarcStatic
{
    /**
     * Creates the directory if not exists.
     *
     * @param String $path Absolute directory path.
     *
     * @return Boolean TRUE when the directory (now) exists.
    */
    public static function createDirectory(String $path): Bool
    {
        // Removes all cached metadata from the local filesystem.
        clearstatcache();

        // Creates the directory or throws an E_WARNING.
        // The Warning is suppressed by prefixing with a '@'.
        @mkdir($path);

        // Again, remove the metadata to find the real status.
        clearstatcache();

        // Triggers the local filesystem to detect whether
        // a directory with the given pathname exists.
        return @is_dir($path);
    }

    /**
     * Generates an absolute file path based on the current time.
     *
     * @param String $dir Parent directory.
     *
     * @return String $path Absolute filepath.
     */
    public static function generateFilename(String $dir): String
    {
        $file = time() . '-' . rand();
        $path = "$dir/$file";

        clearstatcache();

        return $path;
    }

    /**
     * Returns a file path that does not already exist.
     *
     * @param String $dir Parent directory.
     *
     * file_exists(): Upon failure, an E_WARNING is emitted.
     *
     * @throws Exception when stuck in the while-loop.
     *
     * @return String $path Non-existing absolute filepath.
     */
    public static function generateUniqueFilename(String $dir): String
    {
        $attempts = 0;

        while (file_exists($path = self::generateFilename($dir))) {
            $attempts++;

            if ($attempts > 1000000)
                throw new Exception(
                    "Failed to create a unique filename after 1 million tries."
                );
        }

        return $path;
    }

    /**
     * Creates a new empty file in the given directory path.
     *
     * @param String $dir Directory path.
     *
     * @throws Exception when the directory doesn't exists or failed to create.
     * @throws Exception when stuck in the while-loop in generateUniqueFilename.
     * An E_WARNING could be emitted by file_exists() in generateUniqueFilename.
     * @throws Exception when the new file failed to be created.
     *
     * @return String $path New and non-existing file, absolute filepath.
     * @return String $path New and empty file, absolute filepath.
     */
    public static function createNewEmptyFile(String $dir): String
    {
        // Makes sure that directory exists.
        if (!self::createDirectory($dir))
            throw new Exception("Failed to create directory path: '$dir'.");

        // Generates a $path that does not yet exist.
        $path = self::generateUniqueFilename($dir);

        // $path is an empty slot which is used to
        // write the incoming e-mail message into.

        return $path;
        // Opening a stream in append mode creates the file.

        // Creates the new empty file.
        if (!touch($path))
            throw new Exception("Unable to create file: '$path'.");

        return $path;
    }

    /**
     * Writes the STDIN stream to a new file in the given directory.
     * Returns the full absolute file path of the written contents.
     *
     * @param String $dir Directory path to store the message into.
     *
     * @throws see createNewEmptyFile()
     * @throws Exception Unable to open file in append mode.
     * @throws Exception Unable to append contents to file.
     *
     * @return String $path Absolute file path to the written file.
     */
    public static function writeStdinToNewEmptyFile(String $dir): String
    {
        // Returns the absolute file path to the newly generated file.
        $path = self::createNewEmptyFile($dir);

        // Opens a File Pointer ($fp) in Append ('a') mode.
        if ( ($fp = @fopen($path, 'a')) === false )
            throw new Exception("Cannot open file: '$path'.");

        // Whether something has been written to the disk.
        $written = false;

        stream_set_blocking(STDIN, 0);

        // Runs until the message is fully processed.
        // The message is sent through STDIN.
        // Only keeps the last line in memory.
        while ( ($line = fgets(STDIN)) !== false ) {

            // Writes each line to the file.
            if (fwrite($fp, $line) === false) {
                fclose($fp);
                throw new Exception("Cannot write to file: '$path'.");
            }

            // Something has been written to disk.
            $written = true;
        }

        // Closes the file pointer.
        fclose($fp);

        if (!$written) {
            unlink($path);
            throw new Exception("Failed to read STDIN.");
        }

        // Returns the location to the stored message.
        return $path;
    }
}
