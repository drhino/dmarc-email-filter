<?php

namespace drhino\dmarcEmailFilter;

use function gzopen;
use function gzread;
use function gzclose;
use function gzeof;
use function fopen;
use function fwrite;
use function fclose;
use function is_resource;
#use function explode;
#use function array_pop;
#use function implode;

use ZipArchive;
use Exception;
use Throwable;

final class Unpack
{
    /**
     * Unpacks the gzip compressed $source to the desired $destination.
     *
     * @param String $source location of the gzipped file.
     * @param String $destination location of the unzipped source file.
     *
     * @throws Exception when unable to read or write.
     *
     * @return void
     */
    public static function ungzip(String $source, String $destination): void
    {
        // Exceptions are re-thrown after the streams are closed.
        try {
            // gzopen throws E_WARNING.
            if ( ($gzopen = @gzopen($source, 'rb')) === false )
                throw new Exception('Unable to gzopen(rb): ' . $source);
        
            while (!@gzeof($gzopen)) {
                if ( ($buffer = @gzread($gzopen, 4096)) === false )
                    throw new Exception('Unable to gzread(4096): ' . $source);

                if (!isset($fopen))
                    if ( ($fopen = @fopen($destination, 'ab')) === false )
                        throw new Exception('Unable to fopen(ab): ' . $destination);

                if (fwrite($fopen, $buffer) === false)
                    throw new Exception('Unable to fwrite(): ' . $destination);
            }
        } catch (Throwable $e) {}

        // Always closes the streams (on success or failure).
        isset($fopen) && is_resource($fopen) && fclose($fopen);
        isset($gzopen) && is_resource($gzopen) && gzclose($gzopen);

        if (isset($e)) throw $e;
    }

    /**
     * Extracts a compressed zip-file.
     *
     * @param String $source input zip file.
     * @param String $destination to unzipped contents.
     *
     * @throws Exception on failure.
     *
     * @return void
     */
    public static function unzip(String $source, String $destination): void
    {
        try {
            $zip = new ZipArchive;

            $status = $zip->open($source, ZipArchive::CHECKCONS);

            // When $status is not TRUE, one of the error code constants is used:
            // https://www.php.net/manual/en/ziparchive.open.php
            if ($status !== true)
                throw new Exception('Failed to open ZipArchive: ' . $source, $status);

            if (!$zip->extractTo($destination))
                throw new Exception('Failed to extract: ' . $source . ' to: ' . $destination);
        }
        catch (Throwable $e) {}

        // Closes the zip-file when it was successfully opened.
        isset($zip) && isset($status) && (true === $status) && $zip->close();

        if (isset($e)) throw $e;
    }

    /**
     * Detects the type of archive from the file extension
     *   and unpacks to the same location, without the file extension.
     *   On success, the archive is removed.
     *
     * @param String $source path to archive.
     *
     * @throws Exception Unknown file extension.
     *
     * @return String $destination path to unpacked contents.
     */
    /*public static function unpack(String $source): String
    {
        $explode = explode('.', $source);
        $extension = array_pop($explode);
        $destination = implode('.', $explode);

        switch ($extension) {
            case 'gz':
                self::ungzip($source, $destination);
            break;

            case 'zip':
                self::unzip($source, $destination);
            break;

            default:
                throw new Exception('Unknown file extension: ' . $source);
        }

        unlink($source);

        return $destination;
    }*/
}
