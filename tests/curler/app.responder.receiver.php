<?php

/**
 * Curler Responder, let's say 'https://example.com/restricted/' is .htaccess protected and returns
 */

$rspns = [
    'auth_type' => $_SERVER['AUTH_TYPE'] ?? 'error',
    'header' => getallheaders(),
    'body' => file_get_contents('php://input'),
    'c_types' => [
        'json' => 'application/json',
    ]
];

$setType = $rspns['c_types'][$_GET['c_type'] ?? null] ?? null;
$cType = $setType ?? $_SERVER['CONTENT_TYPE'] ?? 'text/html';

/** Set Content-Type header auto, if requested one is listed in 'c_types' */
if ($setType)
    header(sprintf('Content-Type: %s; charset: %s', $cType, $_GET['charset'] ?? 'UTF-8'));

/** Parse received body content */
if (ctype_print($rspns['body']))
    parse_str($rspns['body'], $rspns['body_parsed']);

exit('application/json' == $cType
    ? json_encode($rspns, JSON_PRETTY_PRINT)
    : print_r($rspns, true)
);



/**
 * Curler Receiver, access the .htaccess protected ressources
 */

$curler = (new Curler)
    ->authAny('many', '123456') // auto ->authBasic() or ->authDigest()
    ->postFields([
        'lorem_ipsum' => 'Dolor Sit Amet',
    ])
    ->jsonDecode()
    ->exec('https://example.com/restricted/'); // ?c_type=json &charset=UTF-8

printf('<pre>%s<hr /><b>body</b><br />%s</pre>'
    , print_r($curler, true)
    , $curler['response']->body ?? 'body is not an object'
);
