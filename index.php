#!/usr/bin/php
<?php

use drhino\dmarcEmailFilter\Unpack;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Header\HeaderConsts;

require __DIR__ . '/vendor/autoload.php';

// Generates a temporary filename.
while (
    file_exists($tmp = sys_get_temp_dir() . '/' . time() . '-' . rand())
 && file_exists($tmp . '.xml')
 && is_dir($tmp . '-dir')
);

stream_set_blocking(STDIN, 0);

try {
    $message = (new MailMimeParser)->parse(STDIN, true);
    $meta    = [
        'from'    => $message->getHeaderValue(HeaderConsts::FROM),
        'to'      => $message->getHeader(HeaderConsts::TO),
        'subject' => $message->getHeaderValue(HeaderConsts::SUBJECT),
    ];

    if (empty($meta['from'])) throw new Exception('Failed to read from STDIN');

    $meta['to'] = $meta['to']->getAddresses()[0]->getEmail();

    $att = $message->getAttachmentPart(0);
    $att->saveContent($tmp);

    switch ($ct = $att->getHeaderValue(HeaderConsts::CONTENT_TYPE))
    {
        case 'application/gzip':
            Unpack::ungzip($tmp, $tmp . '.xml');
        break;

        case 'application/zip':
            Unpack::unzip($tmp, $tmp . '-dir');
            rename($tmp . '-dir/' . substr($att->getFilename(), 0, -3) . 'xml', $tmp . '.xml');
            rmdir($tmp . '-dir');
        break;

        default: throw new Exception('Unknown Content-Type: ' . $ct);
    }
} catch (Throwable $e) {}

$message = null;
file_exists($tmp) && unlink($tmp);
is_dir($tmp . '-dir') && rmdir($tmp . '-dir');

if (isset($e)) throw $e;

print_r($meta);

$xml = file_get_contents($tmp . '.xml');

echo $xml;
echo PHP_EOL;

unlink($tmp . '.xml');
