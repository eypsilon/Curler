<?php \error_reporting(E_ALL);

/**
 * To use this example, copy the './tests/curler'
 * directory to where composers  './vendor' directory is
 *
 * For demo purposes only
 *
 * $ ~/terminal/in/./tests/curler
 * php -S localhost:8000
 * http://localhost:8000
 */

use Many\Http\Curler;
use Many\Exception\AppCallbackException;

require_once \dirname(\dirname(__DIR__)) . '/vendor/autoload.php';
require_once \dirname(__FILE__) . '/callback.functions.php';


/** @var string (optional) default URL */
$defaultUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb';

/** @var array Images to test "img to string" converter, will use $defaultUrl */
$imgList = [
    '/8/80/Andaman.jpg/200px-Andaman.jpg',
    '/2/21/Aurora_and_sunset.jpg/200px-Aurora_and_sunset.jpg',
    '/9/93/Sunset_at_Egipura%2C_Bangalore.jpg/200px-Sunset_at_Egipura%2C_Bangalore.jpg',
    '/3/3e/After_The_Storm_(3594761019).jpg/200px-After_The_Storm_(3594761019).jpg',
];

/** @var string File with json content */
$jsonFile = 'https://raw.githubusercontent.com/eypsilon/Curler/master/tests/curler/www.example.json';

/** @var array some URLs to load */
$loadUrls = [
    'http://example.com/',
    'https://raw.githubusercontent.com/eypsilon/browser-reload/master/LICENSE',
];


/**
 * @var array Set Config
 */
Curler::setConfig([
    'response_only'  => false,
    'curl_trace'     => true, // (default) false
    'exceptions'     => true, // (default) false
    'meta'           => false,
    'request_info'   => false,
    'curl_info'      => false,
    'default_url'    => null,
    'date_format'    => 'Y-m-d H:i:s.u',
    'image_to_data'  => [], // 'image/jpeg', ...
    'default_header' => [
        'x-app-curler' => 'Many/Curler',
        'Content-Type' => 'application/json',
    ],
    'default_options' => [
        CURLOPT_USERAGENT => 'AwesomeUser',
    ],
    'default_callback' => [],
]);


/**
 * @var Curler extended example with multiple callbacks (order matters)
 */
try {
    $curled['curler'] = (new Curler)
        // ->disableDefaultUrl()
        // ->responseOnly()
        // ->requestInfo()
        // ->curlInfo()

        // callbacks, see "./callback.functions.php"
        ->callback('curlCallback')
        ->callback('curlCallbackTwo')
        ->callback('curlCallbackThree')

        // using a class
        ->callback('CallbackClass::run')           // class::run()
        ->callback('CallbackClass::class', 'init') // (new class)->init()
        ->callback(CallbackClass::class, 'init')   // (new class)->init()

        // using a closure
        ->callback(function($response) {
            // do stuff with $response here and
            return curlConv($response, 'closure_callback');
        })

        // pre validate content
        // ->callback('appendString', ' ...')
        ->jsonDecode()
        ->jsonEncode()
        ->callbackIf(['is_string'], 'curlConv', 'done_too')

        // callback shorties
        ->jsonDecode()                             // ->callback('json_decode')
        ->jsonEncode(JSON_PRETTY_PRINT)            // ->callback('json_encode', JSON_PRETTY_PRINT)
        ->htmlChars()                              // ->htmlSpecialChars()

        ->exec($jsonFile);
} catch(AppCallbackException $e) {
    $curled['exception']['curler'] = $e->getMessage();
}


/**
 * @var array Load multiple URLs
 */
foreach($loadUrls as $url) {
    try {
        $host = \parse_url($url, PHP_URL_HOST);
        $curled['load_urls'][] = (new Curler)
            ->htmlChars()
            ->callback('trim')
            ->callback('substr', 0, 17)
            ->callback('appendString', '… ' . Curler::dateMicroSeconds())
            ->get($url);
    } catch(AppCallbackException $e) {
        $curled['exception']['load_urls'][$host] = $e->getMessage();
    }
}


/**
 * @var Curler Load images, change configs to get datetime for images, set default URL and image converter
 */
Curler::setConfig([
    'meta' => true,
    'default_url' => $defaultUrl,
    'image_to_data' => ['image/jpeg', /* 'image/png', */],
]);

$c = new Curler;
$printImages = [];
foreach($imgList as $img) {
    try {
        if ($src = $c->get($img)) {
            $curled['images'][] = $src;
            $printImages[] = \sprintf('<img src="%s" alt="" style="height:125px" />', $src['response']);
        }
    } catch(AppCallbackException $e) {
        $curled['exception']['images'][$img] = $e->getMessage();
    }
}


/**
 * Template Engin © 1994 eypsilon
 */
?><!DOCTYPE html>
<html><head><meta charset="utf-8" />
<title>Many/Curler | local-dev-many-title</title>
<meta name="description" content="Many/Curler Example Page" />
</head><body>
<?php
// out
\printf('%s<pre>%s</pre>'
    , \implode(PHP_EOL, $printImages) // print images
    , \print_r(\array_merge($curled, [
        'getCurlCount' => Curler::getCurlCount(),
        'getCurlTrace' => Curler::getCurlTrace(),
        'getConfig'    => Curler::getConfig(),
        'getOptions'   => Curler::getOptions(),
    ]), true)
);
// advanced benchmarks
\printf("<pre>start: %s\nend:   %s\ndiff:  %s\nmem:   %s\npeak:  %s</pre>"
    , $started = Curler::dateMicroSeconds($_SERVER['REQUEST_TIME_FLOAT'], 'H:i:s.u')
    , $current = Curler::dateMicroSeconds(null, 'H:i:s.u') // current microtime(true)
    , Curler::dateMicroDiff($started, $current, '%s.%f')
    , Curler::readableBytes(\memory_get_usage())
    , Curler::readableBytes(\memory_get_peak_usage())
);
?>
<hr /><div style="text-align:center">
<h1>Many/Curler</h1>
<p>another one curls the dust</p></div>
</body></html>
