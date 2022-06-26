<?php

// https://github.com/zbateson/mail-mime-parser
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Header\HeaderConsts;
// https://github.com/mtibben/html2text
// use Html2Text\Html2Text;

require __DIR__ . '/../vendor/autoload.php';

$path = __DIR__ . '/../_queue/example-message.txt';

$message = (new MailMimeParser)->parse(fopen($path, 'r'), true);

$parsed = [
    /*
    'from'    => [
        'name' => $message->getHeader(HeaderConsts::FROM)->getPersonName(),
        'mail' => $message->getHeaderValue(HeaderConsts::FROM),
    ],*/
    'from'    => $message->getHeaderValue(HeaderConsts::FROM),
    'to'      => $message->getHeader(HeaderConsts::TO)->getAddresses()[0]->getEmail(),
    'subject' => $message->getHeaderValue(HeaderConsts::SUBJECT),
  //'content' => (new Html2Text($message->getHtmlContent()))->getText(),
];

print_r($parsed);

$att = $message->getAttachmentPart(0);

switch ($att->getHeaderValue(HeaderConsts::CONTENT_TYPE))
{
    case 'application/gzip':
        $ext = '.xml.gz';
    break;

    default:
        $ext = '.unknown';
    break;
}

$att->saveContent($path . $ext);

$message = null;
